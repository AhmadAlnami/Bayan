<?php

namespace App\Http\Controllers;

use App\Services\InsightsService;
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

        $insights = (new InsightsService)->calculate($user);

        $budgets = $user->budgets()
            ->with('category')
            ->get()
            ->map(function ($b) use ($user) {
                return $this->formatBudgetWithProgress($b, $user);
            })->values();

        $budgetWarnings = [];
        foreach ($budgets as $b) {
            if ($b['spent'] > $b['amount'] && $b['amount'] > 0) {
                $locale = app()->getLocale();
                $typeLabel = match ($b['type']) {
                    'daily' => $locale === 'en' ? 'Daily' : 'اليومية',
                    'weekly' => $locale === 'en' ? 'Weekly' : 'الأسبوعية',
                    'monthly' => $locale === 'en' ? 'Monthly' : 'الشهرية',
                    'category' => $locale === 'en' ? ($b['category']['name_en'] ?? 'Category') : ($b['category']['name'] ?? 'تصنيف'),
                    default => '',
                };
                $budgetWarnings[] = [
                    'type' => $b['type'],
                    'type_label' => $typeLabel,
                    'spent' => $b['spent'],
                    'amount' => $b['amount'],
                    'progress' => $b['progress'],
                ];
            }
        }

        $today = now()->format('Y-m-d');
        $todayExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('transaction_date', $today)
            ->sum('amount');
        $todayCount = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('transaction_date', $today)
            ->count();

        $weekExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [
                now()->startOfWeek()->format('Y-m-d').' 00:00:00',
                now()->endOfWeek()->format('Y-m-d').' 23:59:59',
            ])
            ->sum('amount');
        $weekCount = $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [
                now()->startOfWeek()->format('Y-m-d').' 00:00:00',
                now()->endOfWeek()->format('Y-m-d').' 23:59:59',
            ])
            ->count();

        $daysPassed = max(1, (int) now()->day);
        $avgDailyThisMonth = (float) round($thisMonthExpenses / $daysPassed, 2);

        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();
        $lastMonthDays = max(1, (int) now()->subMonth()->daysInMonth);
        $lastMonthExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$lastMonthStart->format('Y-m-d'), $lastMonthEnd->format('Y-m-d')])
            ->sum('amount');
        $lastMonthAvgDaily = $lastMonthExpenses > 0 ? round($lastMonthExpenses / $lastMonthDays, 2) : 0;

        $trendPct = $lastMonthAvgDaily > 0 ? round((($avgDailyThisMonth - $lastMonthAvgDaily) / $lastMonthAvgDaily) * 100) : 0;

        $summary = [
            'today_expenses' => (float) $todayExpenses,
            'today_count' => $todayCount,
            'week_expenses' => (float) $weekExpenses,
            'week_count' => $weekCount,
            'avg_daily_this_month' => $avgDailyThisMonth,
            'trend_label' => $trendPct > 0 ? 'up' : ($trendPct < 0 ? 'down' : 'same'),
            'trend_pct' => abs($trendPct),
        ];

        $savingsGoals = $user->savingsGoals()
            ->orderByDesc('created_at')
            ->limit(4)
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'name_en' => $g->name_en,
                'target_amount' => (float) $g->target_amount,
                'current_amount' => (float) $g->current_amount,
                'progress' => $g->progress,
                'remaining' => $g->remaining,
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
            'monthlyChart' => $monthlyChart,
            'dailyChart' => $dailyChart,
            'recentTransactions' => $recentTransactions->values(),
            'insights' => $insights,
            'budgets' => $budgets,
            'budget_warnings' => $budgetWarnings,
            'summary' => $summary,
            'savings_goals' => $savingsGoals,
        ]);
    }

    private function formatBudgetWithProgress($budget, $user): array
    {
        $spent = $this->calculateBudgetSpent($budget, $user);
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

    private function calculateBudgetSpent($budget, $user): float
    {
        $query = $user->transactions()->where('type', 'expense');

        switch ($budget->type) {
            case 'daily':
                $query->whereDate('transaction_date', now());
                break;

            case 'weekly':
                $query->whereBetween('transaction_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ]);
                break;

            case 'monthly':
                $query->whereBetween('transaction_date', [
                    now()->startOfMonth(),
                    now()->endOfMonth(),
                ]);
                break;

            case 'category':
                $query->where('category_id', $budget->category_id)
                    ->whereBetween('transaction_date', [
                        now()->startOfMonth(),
                        now()->endOfMonth(),
                    ]);
                break;
        }

        return (float) $query->sum('amount');
    }
}
