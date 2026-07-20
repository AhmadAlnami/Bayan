<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - {{ $periodLabel }} - {{ $reportLabel }}</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
    <style>
        @page {
            size: A4;
            margin: 12mm 14mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1a1a2e;
            font-size: 13px;
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #00a86b;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 26px;
            color: #1a1a2e;
            margin-bottom: 4px;
        }

        .header .brand {
            color: #00a86b;
        }

        .header .subtitle {
            font-size: 15px;
            color: #555;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: #f8fafb;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 14px;
            text-align: center;
        }

        .summary-card .label {
            font-size: 11px;
            color: #666;
            margin-bottom: 4px;
        }

        .summary-card .value {
            font-size: 20px;
            font-weight: 700;
        }

        .summary-card .value.expense { color: #dc2626; }
        .summary-card .value.income { color: #16a34a; }
        .summary-card .value.net-positive { color: #16a34a; }
        .summary-card .value.net-negative { color: #dc2626; }

        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }

        .chart-box h3 {
            font-size: 14px;
            margin-bottom: 10px;
            color: #333;
        }

        .chart-box canvas {
            max-height: 240px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #1a1a2e;
        }

        .top-expenses {
            margin-bottom: 20px;
        }

        .top-expense-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: #f8fafb;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 6px;
        }

        .top-expense-item .desc {
            font-weight: 500;
            color: #333;
        }

        .top-expense-item .meta {
            font-size: 11px;
            color: #888;
        }

        .top-expense-item .amount {
            font-weight: 700;
            color: #dc2626;
            white-space: nowrap;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background: #f1f5f9;
            text-align: {{ $locale === 'ar' ? 'right' : 'left' }};
            padding: 9px 12px;
            font-size: 11px;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }

        .type-icon {
            display: inline-block;
            width: 16px;
            text-align: center;
            font-weight: bold;
        }

        .type-icon.expense { color: #dc2626; }
        .type-icon.income { color: #16a34a; }

        .amount-cell {
            font-weight: 600;
            white-space: nowrap;
        }

        .amount-cell.expense { color: #dc2626; }
        .amount-cell.income { color: #16a34a; }

        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
        }

        .category-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .footer {
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            font-size: 11px;
            color: #999;
        }

        .text-muted {
            color: #888;
            font-size: 11px;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }
        }

        .print-btn {
            position: fixed;
            top: 16px;
            {{ $locale === 'ar' ? 'left' : 'right' }}: 16px;
            z-index: 999;
            background: #00a86b;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 24px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
    </style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">
    {{ $locale === 'ar' ? 'طباعة / حفظ PDF' : 'Print / Save PDF' }}
</button>

<div class="header">
    <h1><span class="brand">{{ config('app.name') }}</span> - {{ $periodLabel }}</h1>
    <p class="subtitle">{{ $reportLabel }}</p>
</div>

@php
    $totals = $report['totals'];
    $catBreakdown = $report['category_breakdown'];
    $transactions = $report['transactions'];
    $topExpenses = $report['top_expenses'];
    $chart = $report['chart'];
    $comparison = $report['comparison'];
    $expenseLabel = $locale === 'ar' ? 'مصروفات' : 'Expenses';
    $incomeLabel = $locale === 'ar' ? 'دخل' : 'Income';
    $netLabel = $locale === 'ar' ? 'الصافي' : 'Net';
    $countLabel = $locale === 'ar' ? 'عدد المعاملات' : 'Transactions';
    $categoryLabel = $locale === 'ar' ? 'توزيع المصروفات' : 'Expense Breakdown';
    $topExpLabel = $locale === 'ar' ? 'أعلى المصروفات' : 'Top Expenses';
    $tableLabel = $locale === 'ar' ? 'المعاملات' : 'Transactions';
    $dateLabel = $locale === 'ar' ? 'التاريخ' : 'Date';
    $descLabel = $locale === 'ar' ? 'الوصف' : 'Description';
    $catHeaderLabel = $locale === 'ar' ? 'التصنيف' : 'Category';
    $amountLabel = $locale === 'ar' ? 'المبلغ' : 'Amount';
    $sarLabel = $locale === 'ar' ? 'ر.س' : 'SAR';
    $footerText = $locale === 'ar' ? 'تم التصدير بواسطة بيان' : 'Exported by Bayan';
    $vsLabel = $locale === 'ar' ? 'مقارنة بالفترة السابقة' : 'vs Previous Period';

    function fmt($n, $locale) {
        return $n == (int)$n ? number_format($n) : number_format($n, 2);
    }
@endphp

<div class="summary">
    <div class="summary-card">
        <div class="label">{{ $expenseLabel }}</div>
        <div class="value expense">{{ fmt($totals['expenses'], $locale) }} {{ $sarLabel }}</div>
        @if ($comparison['expense_change'] !== 0)
            <div class="text-muted">{{ $vsLabel }}: {{ $comparison['expense_change'] > 0 ? '+' : '' }}{{ $comparison['expense_change'] }}%</div>
        @endif
    </div>
    <div class="summary-card">
        <div class="label">{{ $incomeLabel }}</div>
        <div class="value income">{{ fmt($totals['income'], $locale) }} {{ $sarLabel }}</div>
        @if ($comparison['income_change'] !== 0)
            <div class="text-muted">{{ $vsLabel }}: {{ $comparison['income_change'] > 0 ? '+' : '' }}{{ $comparison['income_change'] }}%</div>
        @endif
    </div>
    <div class="summary-card">
        <div class="label">{{ $netLabel }}</div>
        <div class="value {{ $totals['net'] >= 0 ? 'net-positive' : 'net-negative' }}">{{ fmt($totals['net'], $locale) }} {{ $sarLabel }}</div>
    </div>
    <div class="summary-card">
        <div class="label">{{ $countLabel }}</div>
        <div class="value">{{ $totals['count'] }}</div>
    </div>
</div>

@php
    $chartLabels = [];
    $chartExpenses = [];
    $chartIncome = [];
    $hasIncome = false;
    foreach ($chart as $pt) {
        $chartLabels[] = $locale === 'ar' && isset($pt['label_ar']) ? $pt['label_ar'] : $pt['label'];
        $chartExpenses[] = $pt['expenses'];
        $chartIncome[] = $pt['income'] ?? 0;
        if (($pt['income'] ?? 0) > 0) $hasIncome = true;
    }

    $pieLabels = [];
    $pieValues = [];
    $pieColors = [];
    foreach ($catBreakdown as $c) {
        $pieLabels[] = $locale === 'ar' ? $c['name'] : $c['name_en'];
        $pieValues[] = $c['total'];
        $pieColors[] = $c['color'];
    }
@endphp

<div class="charts-row">
    <div class="chart-box">
        <h3>{{ $expenseLabel }}@if($hasIncome) & {{ $incomeLabel }}@endif</h3>
        <canvas id="barChart"></canvas>
    </div>
    <div class="chart-box">
        <h3>{{ $categoryLabel }}</h3>
        @if (count($pieValues) > 0)
            <canvas id="pieChart"></canvas>
        @else
            <p class="text-muted" style="text-align:center;padding:40px;">{{ $locale === 'ar' ? 'لا توجد بيانات' : 'No data' }}</p>
        @endif
    </div>
</div>

@if (count($topExpenses) > 0)
<div class="top-expenses">
    <h3 class="section-title">{{ $topExpLabel }}</h3>
    @foreach ($topExpenses as $exp)
    <div class="top-expense-item">
        <div>
            <div class="desc">{{ $exp['description'] ?: ($locale === 'ar' ? 'بدون وصف' : 'No description') }}</div>
            <div class="meta">
                {{ $exp['transaction_date'] }}
                @if ($exp['category'])
                    &middot; <span class="category-badge"><span class="category-dot" style="background:{{ $exp['category']['color'] }}"></span>{{ $locale === 'ar' ? $exp['category']['name'] : $exp['category']['name_en'] }}</span>
                @endif
            </div>
        </div>
        <span class="amount">{{ fmt($exp['amount'], $locale) }} {{ $sarLabel }}</span>
    </div>
    @endforeach
</div>
@endif

<table>
    <thead>
        <tr>
            <th>{{ $dateLabel }}</th>
            <th>{{ $descLabel }}</th>
            <th>{{ $catHeaderLabel }}</th>
            <th style="text-align: {{ $locale === 'ar' ? 'left' : 'right' }};">{{ $amountLabel }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($transactions as $tx)
        <tr>
            <td style="white-space:nowrap;">{{ $tx['transaction_date'] }}</td>
            <td>
                <span class="type-icon {{ $tx['type'] }}">{{ $tx['type'] === 'expense' ? ($locale === 'ar' ? '—' : '−') : '+' }}</span>
                {{ $tx['description'] ?: '—' }}
            </td>
            <td>
                @if ($tx['category'])
                    <span class="category-badge"><span class="category-dot" style="background:{{ $tx['category']['color'] }}"></span>{{ $locale === 'ar' ? $tx['category']['name'] : $tx['category']['name_en'] }}</span>
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
            <td class="amount-cell {{ $tx['type'] }}" style="text-align: {{ $locale === 'ar' ? 'left' : 'right' }}; white-space:nowrap;">
                {{ fmt($tx['amount'], $locale) }} {{ $sarLabel }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    {{ $footerText }} &mdash; {{ now()->format('Y-m-d H:i') }}
</div>

<script>
    const isRtl = document.dir === 'rtl';

    const barCtx = document.getElementById('barChart').getContext('2d');
    const barDatasets = [{
        label: '{{ $expenseLabel }}',
        data: @json($chartExpenses),
        backgroundColor: '#dc2626',
        borderRadius: 4,
    }];
    @if ($hasIncome)
    barDatasets.push({
        label: '{{ $incomeLabel }}',
        data: @json($chartIncome),
        backgroundColor: '#16a34a',
        borderRadius: 4,
    });
    @endif

    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: barDatasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: @json($hasIncome), position: 'bottom' } },
            scales: {
                x: { reverse: isRtl, grid: { display: false } },
                y: { beginAtZero: true },
            },
        },
    });

    @if (count($pieValues) > 0)
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: @json($pieLabels),
            datasets: [{
                data: @json($pieValues),
                backgroundColor: @json($pieColors),
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 10, font: { size: 10 } },
                },
            },
        },
    });
    @endif
</script>

</body>
</html>
