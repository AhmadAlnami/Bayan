<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Reports', href: '/reports' }],
    };
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import PieChart from '@/components/charts/PieChart.svelte';
    import BarChart from '@/components/charts/BarChart.svelte';
    import PullToRefresh from '@/components/PullToRefresh.svelte';
    import TrendingUp from 'lucide-svelte/icons/trending-up';
    import TrendingDown from 'lucide-svelte/icons/trending-down';
    import DollarSign from 'lucide-svelte/icons/dollar-sign';
    import ArrowUpRight from 'lucide-svelte/icons/arrow-up-right';
    import ArrowDownLeft from 'lucide-svelte/icons/arrow-down-left';
    import PiggyBank from 'lucide-svelte/icons/piggy-bank';
    import ChevronLeft from 'lucide-svelte/icons/chevron-left';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    import FileDown from 'lucide-svelte/icons/file-down';
    import { t, localizedName, localeState } from '@/lib/locale.svelte';

    const { locale } = localeState();

    let {
        period = 'monthly',
        date = '',
        report,
    }: {
        period: string;
        date: string;
        report: {
            label: string;
            start?: string;
            end?: string;
            totals: { expenses: number; income: number; net: number; count: number };
            prev_totals: { expenses: number; income: number; net: number };
            comparison: { expense_change: number; income_change: number };
            category_breakdown: { name: string; name_en: string; color: string; total: number; percentage: number }[];
            transactions: { id: number; type: string; amount: number; description: string; transaction_date: string; category: any }[];
            top_expenses: { id: number; amount: number; description: string; transaction_date: string; category: any }[];
            chart: { label: string; label_ar?: string; expenses: number; income?: number }[];
        };
    } = $props();

    let activeTab = period;

    const tabs = [
        { key: 'daily', title: () => t('reports.daily') },
        { key: 'weekly', title: () => t('reports.weekly') },
        { key: 'monthly', title: () => t('reports.monthly') },
        { key: 'yearly', title: () => t('reports.yearly') },
    ];

    function switchTab(key: string) {
        const today = new Date().toISOString().slice(0, 10);
        router.visit(`/reports?period=${key}&date=${today}`, { preserveScroll: true, preserveState: false });
    }

    function navigate(dir: 'prev' | 'next') {
        const d = new Date(date);
        const days = dir === 'prev' ? -1 : 1;

        switch (period) {
            case 'daily':
                d.setDate(d.getDate() + days);
                break;
            case 'weekly':
                d.setDate(d.getDate() + days * 7);
                break;
            case 'monthly':
                d.setMonth(d.getMonth() + days);
                break;
            case 'yearly':
                d.setFullYear(d.getFullYear() + days);
                break;
        }

        const newDate = d.toISOString().slice(0, 10);
        router.visit(`/reports?period=${period}&date=${newDate}`, { preserveScroll: true, preserveState: false });
    }

    function formatAmount(amount: number): string {
        const numLocale = locale.value === 'ar' ? 'ar-SA' : 'en-US';
        return new Intl.NumberFormat(numLocale).format(amount) + ' ' + t('common.sar');
    }

    function formatDate(dateStr: string): string {
        const d = new Date(dateStr);
        const numLocale = locale.value === 'ar' ? 'ar-SA' : 'en-US';
        return d.toLocaleDateString(numLocale, { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function getChartLabel(item: { label: string; label_ar?: string }): string {
        return locale.value === 'ar' && item.label_ar ? item.label_ar : item.label;
    }

    function getCategoryName(cat: any): string {
        if (!cat) return t('transactions.no_category_label');
        return localizedName(cat);
    }

    const hasData = $derived(report.totals.count > 0);

    const pieLabels = $derived(report.category_breakdown.map((c) => localizedName(c)));
    const pieValues = $derived(report.category_breakdown.map((c) => c.total));
    const pieColors = $derived(report.category_breakdown.map((c) => c.color));

    const barLabels = $derived(report.chart.map((d) => getChartLabel(d)));
    const barValues = $derived(report.chart.map((d) => d.expenses));
    const barValuesIncome = $derived(report.chart.map((d) => d.income ?? 0));

    function getChangeColor(change: number, inverse = false): string {
        if (change === 0) return 'text-muted-foreground';
        if (inverse) return change > 0 ? 'text-brand-green-dark dark:text-brand-green' : 'text-destructive';
        return change > 0 ? 'text-destructive' : 'text-brand-green-dark dark:text-brand-green';
    }

    function exportPdf() {
        window.open(`/reports/print?period=${period}&date=${date}`, '_blank');
    }
</script>

<AppHead title={t('reports.title')} />

<PullToRefresh>
    <div class="min-w-0 space-y-4 p-4 sm:space-y-6 sm:p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold sm:text-2xl">{t('reports.title')}</h1>
                <p class="text-sm text-muted-foreground">{report.label}</p>
            </div>
            <Button
                variant="outline"
                size="sm"
                class="rounded-full gap-1.5"
                onclick={exportPdf}
                disabled={!hasData}
            >
                <FileDown class="size-4" />
                <span class="hidden sm:inline">{t('reports.export_pdf')}</span>
            </Button>
        </div>

        <div class="flex items-center gap-3">
            <button
                onclick={() => navigate('prev')}
                class="rounded-full border border-hairline p-1.5 hover:bg-muted transition-colors"
            >
                {#if locale.value === 'ar'}
                    <ChevronRight class="size-4" />
                {:else}
                    <ChevronLeft class="size-4" />
                {/if}
            </button>

            <div class="flex flex-1 gap-1 overflow-x-auto rounded-full bg-muted p-0.5">
                {#each tabs as tab}
                    <button
                        onclick={() => switchTab(tab.key)}
                        class="shrink-0 rounded-full px-3 py-1.5 text-sm font-medium transition-all {activeTab === tab.key ? 'bg-card text-ink shadow-sm dark:text-on-dark' : 'text-muted-foreground'}"
                    >
                        {tab.title()}
                    </button>
                {/each}
            </div>

            <button
                onclick={() => navigate('next')}
                class="rounded-full border border-hairline p-1.5 hover:bg-muted transition-colors"
            >
                {#if locale.value === 'ar'}
                    <ChevronLeft class="size-4" />
                {:else}
                    <ChevronRight class="size-4" />
                {/if}
            </button>
        </div>

        {#if !hasData}
            <div class="rounded-xl border border-dashed border-hairline p-8 text-center">
                <p class="text-sm text-muted-foreground">{t('reports.empty')}</p>
            </div>
        {:else}
            <div>
                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border border-hairline bg-card p-3 dark:bg-card sm:p-4">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <TrendingUp class="size-3.5 text-destructive" />
                            <span>{t('reports.total_expenses')}</span>
                        </div>
                        <p class="mt-1 text-lg font-bold">{formatAmount(report.totals.expenses)}</p>
                    </div>

                    <div class="rounded-xl border border-hairline bg-card p-3 dark:bg-card sm:p-4">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <TrendingDown class="size-3.5 text-brand-green-dark dark:text-brand-green" />
                            <span>{t('reports.total_income')}</span>
                        </div>
                        <p class="mt-1 text-lg font-bold">{formatAmount(report.totals.income)}</p>
                    </div>

                    <div class="rounded-xl border border-hairline bg-card p-3 dark:bg-card sm:p-4">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <PiggyBank class="size-3.5 text-accent-blue" />
                            <span>{t('reports.net')}</span>
                        </div>
                        <p class="mt-1 text-lg font-bold {report.totals.net >= 0 ? 'text-brand-green-dark dark:text-brand-green' : 'text-destructive'}">
                            {formatAmount(report.totals.net)}
                        </p>
                    </div>

                    <div class="rounded-xl border border-hairline bg-card p-3 dark:bg-card sm:p-4">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <DollarSign class="size-3.5 text-accent-purple" />
                            <span>{t('reports.transactions_count')}</span>
                        </div>
                        <p class="mt-1 text-lg font-bold">{report.totals.count}</p>
                    </div>
                </div>

                <div class="mb-4 rounded-xl border border-hairline bg-card p-4 dark:bg-card">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold">
                        <ArrowUpRight class="size-4 text-accent-orange" />
                        {t('reports.comparison')}
                    </h3>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <div>
                            <span class="text-muted-foreground">{t('dashboard.legend_expenses')}: </span>
                            <span class="font-medium {getChangeColor(report.comparison.expense_change)}">
                                {report.comparison.expense_change > 0 ? '+' : ''}{report.comparison.expense_change}%
                            </span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">{t('dashboard.legend_income')}: </span>
                            <span class="font-medium {getChangeColor(report.comparison.income_change, true)}">
                                {report.comparison.income_change > 0 ? '+' : ''}{report.comparison.income_change}%
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mb-4 rounded-xl border border-hairline bg-card p-4 dark:bg-card">
                    <div class="h-64 sm:h-72">
                        <BarChart labels={barLabels} values={barValues} label={t('dashboard.legend_expenses')} color="#ef4444" values2={barValuesIncome} label2={t('dashboard.legend_income')} color2="#22c55e" />
                    </div>
                </div>

                <div class="mb-4 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card">
                        <h3 class="mb-3 text-sm font-semibold">{t('reports.category_breakdown')}</h3>
                        {#if pieValues.length > 0}
                            <div class="h-48 sm:h-56">
                                <PieChart labels={pieLabels} values={pieValues} colors={pieColors} />
                            </div>
                        {:else}
                            <p class="text-sm text-muted-foreground">{t('reports.empty')}</p>
                        {/if}
                    </div>

                    <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card">
                        <h3 class="mb-3 text-sm font-semibold">{t('reports.top_expenses')}</h3>
                        {#if report.top_expenses.length > 0}
                            <div class="space-y-2">
                                {#each report.top_expenses as exp}
                                    <div class="flex items-center justify-between gap-2 rounded-md bg-muted/50 p-2 text-sm">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-medium">{exp.description || t('transactions.no_category_label')}</p>
                                            <p class="text-xs text-muted-foreground">{formatDate(exp.transaction_date)} &middot; {getCategoryName(exp.category)}</p>
                                        </div>
                                        <span class="shrink-0 font-semibold text-destructive">{formatAmount(exp.amount)}</span>
                                    </div>
                                {/each}
                            </div>
                        {:else}
                            <p class="text-sm text-muted-foreground">{t('reports.empty')}</p>
                        {/if}
                    </div>
                </div>

                <div class="rounded-xl border border-hairline bg-card dark:bg-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-hairline bg-muted/50">
                                    <th class="px-4 py-3 text-left font-medium text-muted-foreground">{t('transactions.date')}</th>
                                    <th class="px-4 py-3 text-left font-medium text-muted-foreground">{t('transactions.description')}</th>
                                    <th class="px-4 py-3 text-left font-medium text-muted-foreground">{t('transactions.category')}</th>
                                    <th class="px-4 py-3 text-left font-medium text-muted-foreground">{t('transactions.amount')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {#each report.transactions as tx}
                                    <tr class="border-b border-hairline last:border-0">
                                        <td class="px-4 py-2.5 text-muted-foreground whitespace-nowrap">{formatDate(tx.transaction_date)}</td>
                                        <td class="px-4 py-2.5">
                                            <div class="flex items-center gap-1.5">
                                                {#if tx.type === 'expense'}
                                                    <ArrowUpRight class="size-3.5 text-destructive shrink-0" />
                                                {:else}
                                                    <ArrowDownLeft class="size-3.5 text-brand-green-dark dark:text-brand-green shrink-0" />
                                                {/if}
                                                <span class="truncate">{tx.description || '—'}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            {#if tx.category}
                                                <span class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs">
                                                    <span class="size-1.5 rounded-full shrink-0" style="background-color: {tx.category.color}"></span>
                                                    {getCategoryName(tx.category)}
                                                </span>
                                            {:else}
                                                <span class="text-muted-foreground text-xs">—</span>
                                            {/if}
                                        </td>
                                        <td class="px-4 py-2.5 font-medium whitespace-nowrap {tx.type === 'expense' ? 'text-destructive' : 'text-brand-green-dark dark:text-brand-green'}">
                                            {formatAmount(tx.amount)}
                                        </td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        {/if}
    </div>
</PullToRefresh>
