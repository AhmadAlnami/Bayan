<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Budget;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $budgets = $user->budgets()
            ->with('category')
            ->get()
            ->map(fn ($b) => $this->formatBudgetWithProgress($b, $user));

        $categories = Category::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })
            ->where('type', 'expense')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'name_en' => $c->name_en,
                'color' => $c->color,
            ]);

        return Inertia::render('Budget', [
            'budgets' => $budgets->values(),
            'categories' => $categories->values(),
            'bills' => $this->getFormattedBills($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'category'])],
            'amount' => 'required|numeric|min:0.01',
            'category_id' => 'nullable|required_if:type,category|exists:categories,id',
        ]);

        $user = $request->user();

        Budget::updateOrCreate(
            [
                'user_id' => $user->id,
                'type' => $validated['type'],
                'category_id' => $validated['type'] === 'category' ? $validated['category_id'] : null,
            ],
            ['amount' => $validated['amount']]
        );

        $message = app()->getLocale() === 'en' ? 'Budget saved.' : 'تم حفظ الميزانية';

        return Redirect::back()->with('toast', ['type' => 'success', 'message' => $message]);
    }

    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            return Redirect::back()->with('toast', [
                'type' => 'error',
                'message' => app()->getLocale() === 'en' ? 'You are not authorized to do this.' : 'غير مصرح لك بتنفيذ هذا الإجراء',
            ]);
        }

        $budget->delete();

        $message = app()->getLocale() === 'en' ? 'Budget deleted.' : 'تم حذف الميزانية';

        return Redirect::back()->with('toast', ['type' => 'success', 'message' => $message]);
    }

    private function formatBudgetWithProgress(Budget $budget, $user): array
    {
        $spent = $this->calculateSpent($budget, $user);
        $amount = (float) $budget->amount;
        $progress = $amount > 0 ? min(round(($spent / $amount) * 100, 1), 100) : 0;

        return [
            'id' => $budget->id,
            'type' => $budget->type,
            'amount' => $amount,
            'spent' => $spent,
            'progress' => $progress,
            'category' => $budget->category ? [
                'id' => $budget->category->id,
                'name' => $budget->category->name,
                'name_en' => $budget->category->name_en,
                'color' => $budget->category->color,
            ] : null,
        ];
    }

    private function calculateSpent(Budget $budget, $user): float
    {
        $query = $user->transactions()->where('type', 'expense');

        switch ($budget->type) {
            case 'daily':
                $query->whereDate('transaction_date', Carbon::today());
                break;

            case 'weekly':
                $query->whereBetween('transaction_date', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ]);
                break;

            case 'monthly':
                $query->whereBetween('transaction_date', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ]);
                break;

            case 'category':
                $query->where('category_id', $budget->category_id)
                    ->whereBetween('transaction_date', [
                        Carbon::now()->startOfMonth(),
                        Carbon::now()->endOfMonth(),
                    ]);
                break;
        }

        return (float) $query->sum('amount');
    }

    private function getFormattedBills($user): array
    {
        return $user->bills()->orderBy('due_day')->get()->map(fn ($b) => [
            'id' => $b->id,
            'name' => $b->name,
            'name_en' => $b->name_en,
            'amount' => (float) $b->amount,
            'category' => $b->category,
            'category_en' => $b->category_en,
            'due_day' => $b->due_day,
            'due_month' => $b->due_month,
            'reminder_days' => $b->reminder_days,
            'recurrence' => $b->recurrence,
            'is_active' => $b->is_active,
            'last_paid_at' => $b->last_paid_at?->format('Y-m-d'),
            'is_due_soon' => $b->isDueSoon(),
        ])->values()->toArray();
    }

    public function storeBill(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'nullable|string|max:50',
            'due_day' => 'required|integer|min:0|max:31',
            'due_month' => 'nullable|integer|min:1|max:12',
            'reminder_days' => 'nullable|integer|min:0|max:30',
            'recurrence' => 'required|in:monthly,weekly,yearly',
        ]);

        $request->user()->bills()->create([
            'name' => $validated['name'],
            'name_en' => $validated['name'],
            'amount' => $validated['amount'],
            'category' => $validated['category'] ?? 'أخرى',
            'category_en' => $validated['category'] ?? 'Other',
            'due_day' => $validated['due_day'],
            'due_month' => $validated['due_month'] ?? null,
            'reminder_days' => $validated['reminder_days'] ?? 3,
            'recurrence' => $validated['recurrence'],
        ]);

        return Redirect::route('budgets')->with('toast', [
            'type' => 'success',
            'message' => app()->getLocale() === 'en' ? 'Bill added!' : 'تمت إضافة الفاتورة!',
        ]);
    }

    public function updateBill(Request $request, Bill $bill): RedirectResponse
    {
        if ($bill->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'due_day' => 'required|integer|min:0|max:31',
            'due_month' => 'nullable|integer|min:1|max:12',
            'reminder_days' => 'nullable|integer|min:0|max:30',
            'recurrence' => 'required|in:monthly,weekly,yearly',
            'category' => 'nullable|string|max:50',
        ]);

        if (isset($validated['category'])) {
            $validated['category_en'] = $validated['category'];
        }
        $bill->update($validated);

        return Redirect::route('budgets')->with('toast', [
            'type' => 'success',
            'message' => app()->getLocale() === 'en' ? 'Bill updated!' : 'تم تحديث الفاتورة!',
        ]);
    }

    public function destroyBill(Request $request, Bill $bill): RedirectResponse
    {
        if ($bill->user_id !== $request->user()->id) {
            abort(403);
        }

        $bill->delete();

        return Redirect::route('budgets')->with('toast', [
            'type' => 'success',
            'message' => app()->getLocale() === 'en' ? 'Bill deleted!' : 'تم حذف الفاتورة!',
        ]);
    }

    public function payBill(Request $request, Bill $bill): RedirectResponse
    {
        if ($bill->user_id !== $request->user()->id) {
            abort(403);
        }

        $bill->update(['last_paid_at' => now()]);

        $billsCategory = Category::where('type', 'expense')
            ->whereNull('user_id')
            ->where(function ($q) {
                $q->where('name', 'فواتير')->orWhere('name_en', 'Bills');
            })
            ->first();

        $request->user()->transactions()->create([
            'amount' => $bill->amount,
            'description' => $bill->name,
            'transaction_date' => Carbon::today(),
            'type' => 'expense',
            'category_id' => $billsCategory?->id,
        ]);

        return Redirect::route('budgets')->with('toast', [
            'type' => 'success',
            'message' => app()->getLocale() === 'en' ? 'Bill paid!' : 'تم تسديد الفاتورة!',
        ]);
    }
}
