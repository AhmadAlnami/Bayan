<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $period = $request->query('period', 'monthly');
        $date = $request->query('date', now()->format('Y-m-d'));

        $data = match ($period) {
            'daily' => $this->dailyReport($user, $date),
            'weekly' => $this->weeklyReport($user, $date),
            'yearly' => $this->yearlyReport($user, $date),
            default => $this->monthlyReport($user, $date),
        };

        return Inertia::render('Reports', [
            'period' => $period,
            'date' => $date,
            'report' => $data,
        ]);
    }

    public function print(Request $request)
    {
        $user = $request->user();
        $period = $request->query('period', 'monthly');
        $date = $request->query('date', now()->format('Y-m-d'));

        $data = match ($period) {
            'daily' => $this->dailyReport($user, $date),
            'weekly' => $this->weeklyReport($user, $date),
            'yearly' => $this->yearlyReport($user, $date),
            default => $this->monthlyReport($user, $date),
        };

        $periodLabels = [
            'daily' => ['ar' => 'تقرير يومي', 'en' => 'Daily Report'],
            'weekly' => ['ar' => 'تقرير أسبوعي', 'en' => 'Weekly Report'],
            'monthly' => ['ar' => 'تقرير شهري', 'en' => 'Monthly Report'],
            'yearly' => ['ar' => 'تقرير سنوي', 'en' => 'Yearly Report'],
        ];

        $locale = app()->getLocale();

        return view('reports.print', [
            'report' => $data,
            'period' => $period,
            'periodLabel' => $periodLabels[$period][$locale] ?? $periodLabels[$period]['ar'],
            'reportLabel' => $data['label'],
            'locale' => $locale,
        ]);
    }

    private function dailyReport($user, string $date): array
    {
        $target = Carbon::parse($date);
        $dayStart = $target->copy()->startOfDay();
        $dayEnd = $target->copy()->endOfDay();
        $prevDayStart = $target->copy()->subWeek()->startOfDay();
        $prevDayEnd = $target->copy()->subWeek()->endOfDay();

        $totals = $this->getTotals($user, $dayStart, $dayEnd);
        $prevTotals = $this->getTotals($user, $prevDayStart, $prevDayEnd);

        $categoryBreakdown = $this->getCategoryBreakdown($user, $dayStart, $dayEnd);
        $transactions = $this->getTransactions($user, $dayStart, $dayEnd);
        $topExpenses = $this->getTopExpenses($user, $dayStart, $dayEnd);
        $chart = $this->getDailyChart($user, $target);

        return [
            'label' => $target->format('Y-m-d'),
            'totals' => $totals,
            'prev_totals' => $prevTotals,
            'comparison' => $this->getComparison($totals, $prevTotals),
            'category_breakdown' => $categoryBreakdown,
            'transactions' => $transactions,
            'top_expenses' => $topExpenses,
            'chart' => $chart,
        ];
    }

    private function weeklyReport($user, string $date): array
    {
        $target = Carbon::parse($date);
        $startOfWeek = $target->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay();
        $endOfWeek = $startOfWeek->copy()->addDays(6)->endOfDay();
        $prevStart = $startOfWeek->copy()->subWeek();
        $prevEnd = $endOfWeek->copy()->subWeek();

        $totals = $this->getTotals($user, $startOfWeek, $endOfWeek);
        $prevTotals = $this->getTotals($user, $prevStart, $prevEnd);

        $categoryBreakdown = $this->getCategoryBreakdown($user, $startOfWeek, $endOfWeek);
        $transactions = $this->getTransactions($user, $startOfWeek, $endOfWeek);
        $topExpenses = $this->getTopExpenses($user, $startOfWeek, $endOfWeek);
        $chart = $this->getWeeklyChart($user, $startOfWeek);

        return [
            'label' => $startOfWeek->format('Y-m-d').' ~ '.$endOfWeek->format('Y-m-d'),
            'start' => $startOfWeek->format('Y-m-d'),
            'end' => $endOfWeek->format('Y-m-d'),
            'totals' => $totals,
            'prev_totals' => $prevTotals,
            'comparison' => $this->getComparison($totals, $prevTotals),
            'category_breakdown' => $categoryBreakdown,
            'transactions' => $transactions,
            'top_expenses' => $topExpenses,
            'chart' => $chart,
        ];
    }

    private function monthlyReport($user, string $date): array
    {
        $target = Carbon::parse($date);
        $startOfMonth = $target->copy()->startOfMonth()->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth()->endOfDay();
        $prevStart = $startOfMonth->copy()->subMonth()->startOfMonth()->startOfDay();
        $prevEnd = $startOfMonth->copy()->subMonth()->endOfMonth()->endOfDay();

        $totals = $this->getTotals($user, $startOfMonth, $endOfMonth);
        $prevTotals = $this->getTotals($user, $prevStart, $prevEnd);

        $categoryBreakdown = $this->getCategoryBreakdown($user, $startOfMonth, $endOfMonth);
        $transactions = $this->getTransactions($user, $startOfMonth, $endOfMonth);
        $topExpenses = $this->getTopExpenses($user, $startOfMonth, $endOfMonth);
        $chart = $this->getMonthlyChart($user, $startOfMonth);

        return [
            'label' => $startOfMonth->translatedFormat('F Y'),
            'start' => $startOfMonth->format('Y-m-d'),
            'end' => $endOfMonth->format('Y-m-d'),
            'totals' => $totals,
            'prev_totals' => $prevTotals,
            'comparison' => $this->getComparison($totals, $prevTotals),
            'category_breakdown' => $categoryBreakdown,
            'transactions' => $transactions,
            'top_expenses' => $topExpenses,
            'chart' => $chart,
        ];
    }

    private function yearlyReport($user, string $date): array
    {
        $target = Carbon::parse($date);
        $startOfYear = $target->copy()->startOfYear()->startOfDay();
        $endOfYear = $startOfYear->copy()->endOfYear()->endOfDay();
        $prevStart = $startOfYear->copy()->subYear()->startOfYear()->startOfDay();
        $prevEnd = $startOfYear->copy()->subYear()->endOfYear()->endOfDay();

        $totals = $this->getTotals($user, $startOfYear, $endOfYear);
        $prevTotals = $this->getTotals($user, $prevStart, $prevEnd);

        $categoryBreakdown = $this->getCategoryBreakdown($user, $startOfYear, $endOfYear);
        $transactions = $this->getTransactions($user, $startOfYear, $endOfYear);
        $topExpenses = $this->getTopExpenses($user, $startOfYear, $endOfYear);
        $chart = $this->getYearlyChart($user, $startOfYear);

        return [
            'label' => $startOfYear->format('Y'),
            'start' => $startOfYear->format('Y-m-d'),
            'end' => $endOfYear->format('Y-m-d'),
            'totals' => $totals,
            'prev_totals' => $prevTotals,
            'comparison' => $this->getComparison($totals, $prevTotals),
            'category_breakdown' => $categoryBreakdown,
            'transactions' => $transactions,
            'top_expenses' => $topExpenses,
            'chart' => $chart,
        ];
    }

    private function getTotals($user, Carbon $start, Carbon $end): array
    {
        $expenses = (float) $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        $income = (float) $user->transactions()
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        return [
            'expenses' => $expenses,
            'income' => $income,
            'net' => $income - $expenses,
            'count' => $user->transactions()
                ->whereBetween('transaction_date', [$start, $end])
                ->count(),
        ];
    }

    private function getComparison(array $current, array $previous): array
    {
        $expenseChange = $previous['expenses'] > 0
            ? round((($current['expenses'] - $previous['expenses']) / $previous['expenses']) * 100)
            : 0;

        $incomeChange = $previous['income'] > 0
            ? round((($current['income'] - $previous['income']) / $previous['income']) * 100)
            : 0;

        return [
            'expense_change' => $expenseChange,
            'income_change' => $incomeChange,
        ];
    }

    private function getCategoryBreakdown($user, Carbon $start, Carbon $end): array
    {
        $totalExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        $results = $user->transactions()
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.transaction_date', [$start, $end])
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, categories.name_en, categories.color, SUM(transactions.amount) as total')
            ->groupBy('categories.id', 'categories.name', 'categories.name_en', 'categories.color')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->name,
                'name_en' => $item->name_en,
                'color' => $item->color,
                'total' => (float) $item->total,
                'percentage' => $totalExpenses > 0 ? round(($item->total / $totalExpenses) * 100) : 0,
            ]);

        return $results->values()->toArray();
    }

    private function getTransactions($user, Carbon $start, Carbon $end): array
    {
        return $user->transactions()
            ->with('category')
            ->whereBetween('transaction_date', [$start, $end])
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
                    'name' => $t->category->name,
                    'name_en' => $t->category->name_en,
                    'color' => $t->category->color,
                ] : null,
            ])->toArray();
    }

    private function getTopExpenses($user, Carbon $start, Carbon $end): array
    {
        return $user->transactions()
            ->with('category')
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$start, $end])
            ->orderByDesc('amount')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'amount' => $t->amount,
                'description' => $t->description,
                'transaction_date' => $t->transaction_date->format('Y-m-d'),
                'category' => $t->category ? [
                    'name' => $t->category->name,
                    'name_en' => $t->category->name_en,
                    'color' => $t->category->color,
                ] : null,
            ])->toArray();
    }

    private function getDailyChart($user, Carbon $date): array
    {
        $hourly = $user->transactions()
            ->whereDate('transaction_date', $date)
            ->selectRaw("cast(strftime('%H', created_at) as integer) as hour")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses")
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income")
            ->groupBy(DB::raw("strftime('%H', created_at)"))
            ->orderBy('hour')
            ->get();

        $chart = [];
        for ($h = 0; $h < 24; $h++) {
            $found = $hourly->firstWhere('hour', $h);
            $chart[] = [
                'label' => sprintf('%02d:00', $h),
                'expenses' => (float) ($found->expenses ?? 0),
                'income' => (float) ($found->income ?? 0),
            ];
        }

        return $chart;
    }

    private function getWeeklyChart($user, Carbon $startOfWeek): array
    {
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $daysAr = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

        $daily = $user->transactions()
            ->whereBetween('transaction_date', [
                $startOfWeek->copy()->startOfDay(),
                $startOfWeek->copy()->addDays(6)->endOfDay(),
            ])
            ->selectRaw('date(transaction_date) as tx_date')
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses")
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income")
            ->groupBy(DB::raw('date(transaction_date)'))
            ->orderBy(DB::raw('date(transaction_date)'))
            ->get();

        $chart = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $startOfWeek->copy()->addDays($i);
            $found = $daily->firstWhere('tx_date', $d->format('Y-m-d'));
            $chart[] = [
                'label' => $days[$i],
                'label_ar' => $daysAr[$i],
                'expenses' => (float) ($found->expenses ?? 0),
                'income' => (float) ($found->income ?? 0),
            ];
        }

        return $chart;
    }

    private function getMonthlyChart($user, Carbon $startOfMonth): array
    {
        $daysInMonth = $startOfMonth->daysInMonth;

        $daily = $user->transactions()
            ->whereBetween('transaction_date', [
                $startOfMonth->copy()->startOfDay(),
                $startOfMonth->copy()->endOfMonth()->endOfDay(),
            ])
            ->selectRaw('date(transaction_date) as tx_date')
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses")
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income")
            ->groupBy(DB::raw('date(transaction_date)'))
            ->orderBy(DB::raw('date(transaction_date)'))
            ->get();

        $chart = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = $startOfMonth->copy()->addDays($d - 1)->format('Y-m-d');
            $found = $daily->firstWhere('tx_date', $date);
            $chart[] = [
                'label' => (string) $d,
                'expenses' => (float) ($found->expenses ?? 0),
                'income' => (float) ($found->income ?? 0),
            ];
        }

        return $chart;
    }

    private function getYearlyChart($user, Carbon $startOfYear): array
    {
        $monthly = $user->transactions()
            ->whereBetween('transaction_date', [
                $startOfYear->copy()->startOfDay(),
                $startOfYear->copy()->endOfYear()->endOfDay(),
            ])
            ->selectRaw("strftime('%Y-%m', transaction_date) as month")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses")
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income")
            ->groupBy(DB::raw("strftime('%Y-%m', transaction_date)"))
            ->orderBy('month')
            ->get();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthsAr = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

        $chart = [];
        for ($m = 1; $m <= 12; $m++) {
            $key = $startOfYear->format('Y').'-'.sprintf('%02d', $m);
            $found = $monthly->firstWhere('month', $key);
            $chart[] = [
                'label' => $months[$m - 1],
                'label_ar' => $monthsAr[$m - 1],
                'expenses' => (float) ($found->expenses ?? 0),
                'income' => (float) ($found->income ?? 0),
            ];
        }

        return $chart;
    }
}
