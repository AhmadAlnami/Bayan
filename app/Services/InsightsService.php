<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InsightsService
{
    public function calculate(User $user): array
    {
        return [
            'monthComparison' => $this->monthComparison($user),
            'topCategory' => $this->topCategory($user),
            'dailyAverage' => $this->dailyAverage($user),
            'weekdayBreakdown' => $this->weekdayBreakdown($user),
            'savingsRate' => $this->savingsRate($user),
            'spendingPace' => $this->spendingPace($user),
        ];
    }

    private function monthComparison(User $user): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $thisMonthExpenses = $user->transactions()->where('type', 'expense')->where('transaction_date', '>=', $thisMonth)->sum('amount');
        $lastMonthExpenses = $user->transactions()->where('type', 'expense')->whereBetween('transaction_date', [$lastMonth, $lastMonthEnd])->sum('amount');

        $thisMonthIncome = $user->transactions()->where('type', 'income')->where('transaction_date', '>=', $thisMonth)->sum('amount');
        $lastMonthIncome = $user->transactions()->where('type', 'income')->whereBetween('transaction_date', [$lastMonth, $lastMonthEnd])->sum('amount');

        $expenseChange = $lastMonthExpenses > 0 ? round((($thisMonthExpenses - $lastMonthExpenses) / $lastMonthExpenses) * 100) : 0;
        $incomeChange = $lastMonthIncome > 0 ? round((($thisMonthIncome - $lastMonthIncome) / $lastMonthIncome) * 100) : 0;

        return [
            'thisMonthExpenses' => (float) $thisMonthExpenses,
            'lastMonthExpenses' => (float) $lastMonthExpenses,
            'expenseChange' => $expenseChange,
            'thisMonthIncome' => (float) $thisMonthIncome,
            'lastMonthIncome' => (float) $lastMonthIncome,
            'incomeChange' => $incomeChange,
        ];
    }

    private function topCategory(User $user): array
    {
        $result = $user->transactions()
            ->where('transactions.type', 'expense')
            ->where('transaction_date', '>=', now()->startOfMonth())
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, categories.name_en, categories.color, SUM(transactions.amount) as total')
            ->groupBy('categories.id', 'categories.name', 'categories.name_en', 'categories.color')
            ->orderByDesc('total')
            ->first();

        if (! $result) {
            return ['name' => '—', 'name_en' => '—', 'color' => '#6b7280', 'total' => 0, 'percentage' => 0];
        }

        $totalExpenses = $user->transactions()->where('type', 'expense')->where('transaction_date', '>=', now()->startOfMonth())->sum('amount');

        return [
            'name' => $result->name,
            'name_en' => $result->name_en,
            'color' => $result->color,
            'total' => (float) $result->total,
            'percentage' => $totalExpenses > 0 ? round(($result->total / $totalExpenses) * 100) : 0,
        ];
    }

    private function dailyAverage(User $user): array
    {
        $monthStart = now()->startOfMonth();
        $daysPassed = max(1, now()->day);
        $monthExpenses = $user->transactions()->where('type', 'expense')->where('transaction_date', '>=', $monthStart)->sum('amount');
        $dailyAvg = round($monthExpenses / $daysPassed, 2);

        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();
        $lastMonthDays = Carbon::parse($lastMonthStart)->daysInMonth;
        $lastMonthExpenses = $user->transactions()->where('type', 'expense')->whereBetween('transaction_date', [$lastMonthStart, $lastMonthEnd])->sum('amount');
        $lastDailyAvg = $lastMonthExpenses > 0 ? round($lastMonthExpenses / $lastMonthDays, 2) : 0;

        $projected = round($dailyAvg * now()->daysInMonth, 2);

        return [
            'today' => (float) $dailyAvg,
            'lastMonth' => (float) $lastDailyAvg,
            'projected' => $projected,
        ];
    }

    private function weekdayBreakdown(User $user): array
    {
        $dayNames = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
        $dayNamesEn = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        $result = $user->transactions()
            ->where('type', 'expense')
            ->where('transaction_date', '>=', now()->subMonths(3))
            ->selectRaw("CAST(strftime('%w', transaction_date) AS INTEGER) as dow")
            ->selectRaw('SUM(amount) as total')
            ->selectRaw('COUNT(*) as count')
            ->groupBy(DB::raw("strftime('%w', transaction_date)"))
            ->orderBy('dow')
            ->get();

        $weekdays = [];
        for ($i = 0; $i < 7; $i++) {
            $found = $result->firstWhere('dow', $i);
            $weekdays[] = [
                'name' => $dayNames[$i],
                'nameEn' => $dayNamesEn[$i],
                'total' => (float) ($found->total ?? 0),
                'count' => (int) ($found->count ?? 0),
            ];
        }

        $totals = array_column($weekdays, 'total');
        $maxDay = ! empty($totals) ? array_search(max($totals), $totals) : null;
        $minDay = ! empty(array_filter($totals)) ? array_search(min(array_filter($totals, fn ($v) => $v > 0) ?: [0]), $totals) : null;

        return [
            'days' => $weekdays,
            'highest' => $maxDay !== null && $maxDay !== false ? $weekdays[$maxDay] : null,
            'lowest' => $minDay !== null && $minDay !== false ? $weekdays[$minDay] : null,
        ];
    }

    private function savingsRate(User $user): array
    {
        $monthStart = now()->startOfMonth();
        $income = $user->transactions()->where('type', 'income')->where('transaction_date', '>=', $monthStart)->sum('amount');
        $expenses = $user->transactions()->where('type', 'expense')->where('transaction_date', '>=', $monthStart)->sum('amount');
        $saved = $income - $expenses;
        $rate = $income > 0 ? round(($saved / $income) * 100) : 0;

        return [
            'saved' => (float) $saved,
            'rate' => $rate,
            'income' => (float) $income,
            'expenses' => (float) $expenses,
        ];
    }

    private function spendingPace(User $user): array
    {
        $daysInMonth = now()->daysInMonth;
        $dayOfMonth = now()->day;
        $pctElapsed = round(($dayOfMonth / $daysInMonth) * 100);

        $monthExpenses = $user->transactions()->where('type', 'expense')->where('transaction_date', '>=', now()->startOfMonth())->sum('amount');
        $lastMonthTotal = $user->transactions()->where('type', 'expense')->whereBetween('transaction_date', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->sum('amount');

        $pctSpent = $lastMonthTotal > 0 ? round(($monthExpenses / $lastMonthTotal) * 100) : 0;
        $status = $pctSpent > $pctElapsed ? 'fast' : ($pctSpent < $pctElapsed - 15 ? 'slow' : 'normal');

        return [
            'pctElapsed' => $pctElapsed,
            'pctSpent' => $pctSpent,
            'status' => $status,
        ];
    }
}
