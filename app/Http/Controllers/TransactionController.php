<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Services\AutoCategorizer;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request, string $type = 'expense'): Response
    {
        $transactions = $request->user()
            ->transactions()
            ->with('category')
            ->where('type', $type)
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => $t->amount,
                'description' => $t->description,
                'transaction_date' => $t->transaction_date->format('Y-m-d'),
                'category' => $t->category ? [
                    'id' => $t->category->id,
                    'name' => $t->category->name,
                    'name_en' => $t->category->name_en,
                    'color' => $t->category->color,
                ] : null,
            ]);

        $categories = Category::where(function ($q) use ($request) {
            $q->whereNull('user_id')->orWhere('user_id', $request->user()->id);
        })
            ->where('type', $type)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'name_en' => $c->name_en,
                'color' => $c->color,
            ]);

        return Inertia::render('Transactions', [
            'type' => $type,
            'transactions' => $transactions->values(),
            'categories' => $categories->values(),
        ]);
    }

    public function quickStore(Request $request): RedirectResponse
    {
        $request->validate([
            'text' => 'required|string|min:2',
            'type' => 'required|in:expense,income',
        ]);

        $text = $request->text;
        $amount = 0;
        $description = '';

        if (preg_match('/^(\d+(?:\.\d+)?)\s*(.+)$/u', $text, $matches)) {
            $amount = (float) $matches[1];
            $description = trim($matches[2]);
        } else {
            $description = $text;
        }

        if ($amount <= 0) {
            return Redirect::back()->with('toast', ['type' => 'error', 'message' => app()->getLocale() === 'en' ? 'Please enter the amount first, e.g. 45 coffee' : 'الرجاء إدخال المبلغ أولاً، مثال: 45 ريال قهوة']);
        }

        $autoCategorizer = new AutoCategorizer;
        $categoryId = $autoCategorizer->categorize($description, $request->type);

        if (! $categoryId) {
            $categoryId = Category::where('type', $request->type)->whereNull('user_id')->where('name', 'أخرى')->first()?->id;
        }

        $request->user()->transactions()->create([
            'amount' => $amount, 'description' => $description ?: 'معاملة سريعة',
            'transaction_date' => Carbon::today(), 'category_id' => $categoryId, 'type' => $request->type,
        ]);

        $locale = app()->getLocale();
        $category = Category::find($categoryId);
        $categoryName = $locale === 'en'
            ? ($category?->name_en ?? '')
            : ($category?->name ?? '');

        $message = $locale === 'en'
            ? 'Added: '.$description.($categoryName ? ' - '.$categoryName : '')
            : 'تمت الإضافة: '.$description.($categoryName ? ' - '.$categoryName : '');

        if ($request->type === 'expense') {
            $budgetWarning = $this->checkBudgetExceeded($request->user());
            if ($budgetWarning) {
                $message .= ' ⚠️ '.$budgetWarning;
            }

            $anomalyWarning = $this->checkAnomaly($request->user(), $categoryId, $amount, $locale);
            if ($anomalyWarning) {
                $message .= ' '.$anomalyWarning;
            }
        }

        return Redirect::back()->with('toast', ['type' => 'success', 'message' => $message]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01', 'description' => 'required|string|max:255',
            'transaction_date' => 'required|date', 'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:expense,income',
        ]);

        if (empty($validated['category_id'])) {
            $autoCategorizer = new AutoCategorizer;
            $validated['category_id'] = $autoCategorizer->categorize($validated['description'], $validated['type']);
        }

        $request->user()->transactions()->create($validated);

        $locale = app()->getLocale();
        $categoryName = $locale === 'en'
            ? (Category::find($validated['category_id'])?->name_en ?? '')
            : (Category::find($validated['category_id'])?->name ?? '');

        $message = $locale === 'en'
            ? 'Added: '.$validated['description'].($categoryName ? ' - '.$categoryName : '')
            : 'تمت الإضافة: '.$validated['description'].($categoryName ? ' - '.$categoryName : '');

        if ($validated['type'] === 'expense') {
            $budgetWarning = $this->checkBudgetExceeded($request->user());
            if ($budgetWarning) {
                $message .= ' ⚠️ '.$budgetWarning;
            }

            $anomalyWarning = $this->checkAnomaly($request->user(), $validated['category_id'], $validated['amount'], $locale);
            if ($anomalyWarning) {
                $message .= ' '.$anomalyWarning;
            }
        }

        return Redirect::back()->with('toast', ['type' => 'success', 'message' => $message]);
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return Redirect::back()->with('toast', ['type' => 'error', 'message' => app()->getLocale() === 'en' ? 'You are not authorized to do this.' : 'غير مصرح لك بتنفيذ هذا الإجراء']);
        }
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01', 'description' => 'required|string|max:255',
            'transaction_date' => 'required|date', 'category_id' => 'nullable|exists:categories,id',
        ]);
        $transaction->update($validated);

        $message = app()->getLocale() === 'en' ? 'Updated.' : 'تم التعديل';

        return Redirect::back()->with('toast', ['type' => 'success', 'message' => $message]);
    }

    private function checkBudgetExceeded($user): ?string
    {
        $budgets = $user->budgets()->get();
        if ($budgets->isEmpty()) {
            return null;
        }

        $locale = app()->getLocale();

        foreach ($budgets as $budget) {
            $spent = match ($budget->type) {
                'daily' => $user->transactions()->where('type', 'expense')->whereDate('transaction_date', Carbon::today())->sum('amount'),
                'weekly' => $user->transactions()->where('type', 'expense')->whereBetween('transaction_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('amount'),
                'monthly' => $user->transactions()->where('type', 'expense')->whereBetween('transaction_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('amount'),
                'category' => $user->transactions()->where('type', 'expense')->where('category_id', $budget->category_id)->whereBetween('transaction_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->sum('amount'),
                default => 0,
            };

            if ($spent > $budget->amount) {
                $typeName = match ($budget->type) {
                    'daily' => $locale === 'en' ? 'daily' : 'اليومية',
                    'weekly' => $locale === 'en' ? 'weekly' : 'الأسبوعية',
                    'monthly' => $locale === 'en' ? 'monthly' : 'الشهرية',
                    'category' => $locale === 'en' ? ($budget->category?->name_en ?? 'category') : ($budget->category?->name ?? 'التصنيف'),
                    default => '',
                };

                return $locale === 'en'
                    ? "Over {$typeName} budget!"
                    : "تجاوزت الميزانية {$typeName}!";
            }
        }

        return null;
    }

    private function checkAnomaly($user, $categoryId, float $amount, string $locale): ?string
    {
        if (! $categoryId || $amount <= 0) {
            return null;
        }

        $thirtyDaysAgo = Carbon::now()->subDays(30)->format('Y-m-d');
        $avg = $user->transactions()
            ->where('type', 'expense')
            ->where('category_id', $categoryId)
            ->where('transaction_date', '>=', $thirtyDaysAgo)
            ->avg('amount');

        if (! $avg || $avg <= 0) {
            return null;
        }

        if ($amount > ($avg * 3) && $amount > 100) {
            $category = Category::find($categoryId);
            $categoryName = $locale === 'en'
                ? ($category?->name_en ?? 'this category')
                : ($category?->name ?? 'هذا التصنيف');
            $formattedAvg = number_format((float) $avg, $amount >= 1000 ? 0 : 2);

            return $locale === 'en'
                ? '⚠️ This amount is unusual! You usually spend '.$formattedAvg.' on '.$categoryName
                : '⚠️ هذا المبلغ غير معتاد! عادة تصرف '.$formattedAvg.' على '.$categoryName;
        }

        return null;
    }

    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            return Redirect::back()->with('toast', ['type' => 'error', 'message' => app()->getLocale() === 'en' ? 'You are not authorized to do this.' : 'غير مصرح لك بتنفيذ هذا الإجراء']);
        }
        $transaction->delete();

        $message = app()->getLocale() === 'en' ? 'Deleted.' : 'تم الحذف';

        return Redirect::back()->with('toast', ['type' => 'success', 'message' => $message]);
    }
}
