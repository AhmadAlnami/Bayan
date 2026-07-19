<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();

        $monthlyTotals = $user->transactions()
            ->where('transaction_date', '>=', $sixMonthsAgo)
            ->selectRaw("strftime('%Y-%m', transaction_date) as month")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses")
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income")
            ->groupBy(DB::raw("strftime('%Y-%m', transaction_date)"))
            ->orderBy('month')
            ->get();

        $monthlyChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $key = $month->format('Y-m');
            $found = $monthlyTotals->firstWhere('month', $key);
            $monthlyChart[] = [
                'month' => $month->translatedFormat('M'),
                'expenses' => (float) ($found->expenses ?? 0),
                'income' => (float) ($found->income ?? 0),
            ];
        }

        $dailyExpenses = $user->transactions()
            ->where('type', 'expense')
            ->where('transaction_date', '>=', $currentMonth)
            ->selectRaw("strftime('%d', transaction_date) as day")
            ->selectRaw('SUM(amount) as total')
            ->groupBy(DB::raw("strftime('%d', transaction_date)"))
            ->orderBy('day')
            ->get();

        $daysInMonth = (int) now()->daysInMonth;
        $dailyChart = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $day = str_pad((string) $d, 2, '0', STR_PAD_LEFT);
            $found = $dailyExpenses->firstWhere('day', $day);
            $dailyChart[] = [
                'day' => (string) $d,
                'total' => (float) ($found->total ?? 0),
            ];
        }

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
            'monthlyChart' => $monthlyChart,
            'dailyChart' => $dailyChart,
            'recentTransactions' => $recentTransactions->values(),
        ]);
    }
}
