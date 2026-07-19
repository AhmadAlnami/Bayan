<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentMonth = now()->startOfMonth();

        $thisMonthExpenses = $user->transactions()
            ->where('type', 'expense')
            ->where('transaction_date', '>=', $currentMonth)
            ->sum('amount');

        $thisMonthIncome = $user->transactions()
            ->where('type', 'income')
            ->where('transaction_date', '>=', $currentMonth)
            ->sum('amount');

        $categoryBreakdown = $user->transactions()
            ->where('transactions.type', 'expense')
            ->where('transaction_date', '>=', $currentMonth)
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, categories.name_en, categories.color, SUM(transactions.amount) as total')
            ->groupBy('categories.id', 'categories.name', 'categories.name_en', 'categories.color')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) use ($thisMonthExpenses) {
                $item->percentage = $thisMonthExpenses > 0
                    ? round(($item->total / $thisMonthExpenses) * 100)
                    : 0;

                return $item;
            });

        $recentTransactions = $user->transactions()
            ->with('category')
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => $t->amount,
                'description' => $t->description,
                'transaction_date' => $t->transaction_date->format('Y-m-d'),
                'category' => $t->category ? [
                    'name' => $t->category->name,
                    'name_en' => $t->category->name_en,
                    'color' => $t->category->color,
                ] : null,
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_expenses' => $user->transactions()->where('type', 'expense')->sum('amount'),
                'total_income' => $user->transactions()->where('type', 'income')->sum('amount'),
                'balance' => $user->transactions()->where('type', 'income')->sum('amount') - $user->transactions()->where('type', 'expense')->sum('amount'),
                'transaction_count' => $user->transactions()->count(),
                'this_month_expenses' => $thisMonthExpenses,
                'this_month_income' => $thisMonthIncome,
            ],
            'categoryBreakdown' => $categoryBreakdown->values(),
            'recentTransactions' => $recentTransactions->values(),
        ]);
    }
}
