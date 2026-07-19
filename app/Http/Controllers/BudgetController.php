<?php

namespace App\Http\Controllers;

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
}
