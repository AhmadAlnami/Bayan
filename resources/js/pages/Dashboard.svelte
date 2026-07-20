<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
        ],
    };
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import ArrowUpRight from 'lucide-svelte/icons/arrow-up-right';
    import ArrowDownLeft from 'lucide-svelte/icons/arrow-down-left';
    import Send from 'lucide-svelte/icons/send';
    import Wallet from 'lucide-svelte/icons/wallet';
    import Target from 'lucide-svelte/icons/target';
    import TrendingDown from 'lucide-svelte/icons/trending-down';
    import TrendingUp from 'lucide-svelte/icons/trending-up';
    import Calendar from 'lucide-svelte/icons/calendar';
    import AlertTriangle from 'lucide-svelte/icons/alert-triangle';
    import FileText from 'lucide-svelte/icons/file-text';
    import PieChart from '@/components/charts/PieChart.svelte';
    import BarChart from '@/components/charts/BarChart.svelte';
    import LineChart from '@/components/charts/LineChart.svelte';
    import PullToRefresh from '@/components/PullToRefresh.svelte';
    import { t, localizedName, localeState } from '@/lib/locale.svelte';

    const { locale } = localeState();

    let {
        stats = { total_expenses: 0, total_income: 0, balance: 0, transaction_count: 0, this_month_expenses: 0, this_month_income: 0 },
        categoryBreakdown = [] as { name: string; name_en: string; total: number; color: string; percentage: number }[],
        monthlyChart = [] as { month: string; expenses: number; income: number }[],
        dailyChart = [] as { day: string; total: number }[],
        recentTransactions = [] as any[],
        insights = null as any,
        budgets = [] as any[],
        budget_warnings = [] as { type: string; type_label: string; spent: number; amount: number; progress: number }[],
        summary = null as { today_expenses: number; today_count: number; week_expenses: number; week_count: number; avg_daily_this_month: number; trend_label: string; trend_pct: number } | null,
        savings_goals = [] as { id: number; name: string; name_en: string; target_amount: number; current_amount: number; progress: number; remaining: number }[],
    } = $props();

    let chatMessages = $state([{ role: 'assistant', content: t('dashboard.chat_greeting') }]);
    let chatInput = $state('');

    let quickText = $state('');
    let quickType = $state<'expense' | 'income'>('expense');
    let quickLoading = $state(false);

    function quickAdd() {
        if (!quickText.trim()) return;
        quickLoading = true;
        const desc = quickText;
        quickText = '';

        router.post('/transactions/quick', { text: desc, type: quickType }, {
            preserveScroll: true,
            onFinish: () => { quickLoading = false; },
        });
    }

    function sendChatMessage() {
        if (!chatInput.trim()) return;
        chatMessages = [...chatMessages, { role: 'user', content: chatInput }];
        chatInput = '';
        setTimeout(() => { chatMessages = [...chatMessages, { role: 'assistant', content: t('dashboard.chat_coming_soon') }]; }, 1000);
    }

    function formatAmount(amount: number): string {
        const numLocale = locale.value === 'ar' ? 'ar-SA' : 'en-US';
        return new Intl.NumberFormat(numLocale).format(amount) + ' ' + t('common.sar');
    }

    const pieLabels = $derived(categoryBreakdown.map(c => localizedName(c)));
    const pieValues = $derived(categoryBreakdown.map(c => c.total));
    const pieColors = $derived(categoryBreakdown.map(c => c.color));
    const barLabels = $derived(dailyChart.map(d => d.day));
    const barValues = $derived(dailyChart.map(d => d.total));
    const lineLabels = $derived(monthlyChart.map(m => m.month));
    const lineExpenses = $derived(monthlyChart.map(m => m.expenses));
    const lineIncome = $derived(monthlyChart.map(m => m.income));
</script>

<AppHead title={t('dashboard.title')} />

<PullToRefresh>
    <div class="min-w-0 space-y-4 p-4 sm:space-y-6 sm:p-6">
        <div>
        <h1 class="text-xl font-semibold sm:text-2xl">{t('dashboard.title')}</h1>
        <p class="text-sm text-muted-foreground">{t('dashboard.welcome')}</p>
    </div>

    <div class="rounded-xl border border-hairline bg-card p-3 sm:p-4 dark:bg-card">
        <form onsubmit={(e) => { e.preventDefault(); quickAdd(); }} class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div class="flex items-center gap-1 rounded-full bg-muted p-0.5">
                <button
                    type="button"
                    onclick={() => quickType = 'expense'}
                    class="rounded-full px-3 py-1.5 text-xs font-medium transition-all sm:text-sm {quickType === 'expense' ? 'bg-destructive text-white' : 'text-muted-foreground'}"
                >
                    {t('transactions.expenses_tab')}
                </button>
                <button
                    type="button"
                    onclick={() => quickType = 'income'}
                    class="rounded-full px-3 py-1.5 text-xs font-medium transition-all sm:text-sm {quickType === 'income' ? 'bg-brand-green text-brand-teal-deep' : 'text-muted-foreground'}"
                >
                    {t('transactions.income_tab')}
                </button>
            </div>
            <div class="flex flex-1 gap-2">
                <Input
                    placeholder={quickType === 'expense' ? t('transactions.quick_add_expense') : t('transactions.quick_add_income')}
                    class="flex-1"
                    bind:value={quickText}
                />
                <Button type="submit" size="icon" class="shrink-0 rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90" disabled={quickLoading || !quickText.trim()}>
                    <Send class="size-4" />
                </Button>
            </div>
        </form>
    </div>

    <div class="grid gap-3 sm:gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-hairline bg-card p-3 sm:p-4 dark:bg-card animate-fade-in-up animate-stagger-1">
            <div class="flex items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-destructive/10">
                    <TrendingDown class="size-4 text-destructive" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-muted-foreground sm:text-sm">{t('dashboard.month_expenses')}</p>
                    <p class="text-base font-bold text-destructive sm:text-xl">{formatAmount(stats.this_month_expenses)}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-hairline bg-card p-3 sm:p-4 dark:bg-card animate-fade-in-up animate-stagger-3">
            <div class="flex items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-green-soft dark:bg-brand-green/10">
                    <TrendingUp class="size-4 text-brand-green-dark dark:text-brand-green" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-muted-foreground sm:text-sm">{t('dashboard.month_income')}</p>
                    <p class="text-base font-bold text-brand-green-dark sm:text-xl dark:text-brand-green">{formatAmount(stats.this_month_income)}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-hairline bg-card p-3 sm:p-4 dark:bg-card animate-fade-in-up animate-stagger-4">
            <div class="flex items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent-blue/10">
                    <Wallet class="size-4 text-accent-blue" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-muted-foreground sm:text-sm">{t('dashboard.remaining')}</p>
                    <p class="text-base font-bold {stats.balance >= 0 ? 'text-accent-blue' : 'text-destructive'} sm:text-xl">{formatAmount(stats.balance)}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-hairline bg-card p-3 sm:p-4 dark:bg-card animate-fade-in-up animate-stagger-5">
            <div class="flex items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent-orange/10">
                    <Calendar class="size-4 text-accent-orange" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-muted-foreground sm:text-sm">{t('dashboard.transactions_count')}</p>
                    <p class="text-base font-bold sm:text-xl">{new Intl.NumberFormat(locale.value === 'ar' ? 'ar-SA' : 'en-US').format(stats.transaction_count)}</p>
                </div>
            </div>
        </div>
    </div>

    {#if insights}
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-hairline bg-card p-3 sm:p-4 dark:bg-card animate-fade-in-up">
                <p class="text-xs text-muted-foreground">{t('insights.monthCompare')}</p>
                <p class="text-lg font-bold {insights.monthComparison.expenseChange > 0 ? 'text-destructive' : 'text-brand-green-dark dark:text-brand-green'}">
                    {insights.monthComparison.expenseChange > 0 ? '+' : ''}{insights.monthComparison.expenseChange}%
                </p>
                <p class="text-[10px] text-muted-foreground">{insights.monthComparison.expenseChange > 0 ? t('insights.dashboardExpenses') : t('insights.dashboardExpensesDown')}</p>
            </div>
            <div class="rounded-xl border border-hairline bg-card p-3 sm:p-4 dark:bg-card animate-fade-in-up animate-stagger-1">
                <p class="text-xs text-muted-foreground">{t('insights.dashboardPace')}</p>
                <p class="text-lg font-bold {insights.spendingPace.status === 'fast' ? 'text-destructive' : 'text-brand-green-dark dark:text-brand-green'}">
                    {insights.spendingPace.pctSpent}%
                </p>
                <p class="text-[10px] text-muted-foreground">{t('insights.dashboardPaceDesc')}</p>
            </div>
            <div class="rounded-xl border border-hairline bg-card p-3 sm:p-4 dark:bg-card animate-fade-in-up animate-stagger-2">
                <p class="text-xs text-muted-foreground">{t('insights.savingsRate')}</p>
                <p class="text-lg font-bold text-brand-green-dark dark:text-brand-green">{insights.savingsRate.rate}%</p>
                <p class="text-[10px] text-muted-foreground">{formatAmount(insights.savingsRate.saved)} {t('insights.dashboardSavings')}</p>
            </div>
        </div>
    {/if}

    {#if budget_warnings.length > 0}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {#each budget_warnings as w}
                <div class="flex items-start gap-3 rounded-xl border border-hairline bg-destructive/5 p-3 sm:p-4 dark:bg-destructive/10 animate-fade-in-up">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-destructive/10">
                        <AlertTriangle class="size-4 text-destructive" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-destructive">
                            {t('dashboard.budget_warning')}: {w.type_label}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {formatAmount(w.spent)} / {formatAmount(w.amount)}
                        </p>
                        <div class="mt-1.5 h-1.5 w-full rounded-full bg-muted">
                            <div class="h-1.5 rounded-full bg-destructive" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            {/each}
        </div>
    {/if}

    {#if budgets.length > 0}
        <div class="rounded-xl border border-hairline bg-card p-3 dark:bg-card sm:p-6">
            <div class="mb-3 flex items-center gap-2 sm:mb-4">
                <Target class="size-4 text-brand-green-dark dark:text-brand-green" />
                <h3 class="text-sm font-semibold sm:text-base">{t('budgets.title')}</h3>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {#each budgets.slice(0, 3) as b}
                    <div class="rounded-lg border border-hairline p-3 animate-fade-in-up">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-muted-foreground">
                                {b.type === 'daily' ? t('budgets.daily') : b.type === 'weekly' ? t('budgets.weekly') : b.type === 'monthly' ? t('budgets.monthly') : localizedName(b.category)}
                            </span>
                            <span class="text-xs font-semibold {b.progress >= 100 ? 'text-destructive' : 'text-brand-green-dark dark:text-brand-green'}">
                                {b.progress}%
                            </span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-muted sm:h-2">
                            <div class="h-1.5 rounded-full transition-all sm:h-2 {b.progress >= 100 ? 'bg-destructive' : b.progress >= 80 ? 'bg-accent-orange' : 'bg-brand-green'}" style="width: {Math.min(b.progress, 100)}%"></div>
                        </div>
                        <div class="mt-1 flex justify-between text-[10px] text-muted-foreground">
                            <span>{t('budgets.spent')}: {formatAmount(b.spent || 0)}</span>
                            <span>{formatAmount(b.amount)}</span>
                        </div>
                    </div>
                {/each}
            </div>
        </div>
    {/if}

    {#if summary}
        <div class="rounded-xl border border-hairline bg-card p-3 sm:p-6 dark:bg-card animate-fade-in-up">
            <div class="mb-3 flex items-center gap-2 sm:mb-4">
                <FileText class="size-4 text-accent-blue" />
                <h3 class="text-sm font-semibold sm:text-base">{t('dashboard.summary_title')}</h3>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-hairline p-3">
                    <div class="flex items-center gap-2 mb-1">
                        <Calendar class="size-3.5 text-muted-foreground" />
                        <span class="text-xs text-muted-foreground">{t('dashboard.summary_today')}</span>
                    </div>
                    <p class="text-lg font-bold">{formatAmount(summary.today_expenses)}</p>
                    <p class="text-[10px] text-muted-foreground">{t('dashboard.summary_transactions')}: {summary.today_count}</p>
                </div>
                <div class="rounded-lg border border-hairline p-3">
                    <div class="flex items-center gap-2 mb-1">
                        <Calendar class="size-3.5 text-muted-foreground" />
                        <span class="text-xs text-muted-foreground">{t('dashboard.summary_week')}</span>
                    </div>
                    <p class="text-lg font-bold">{formatAmount(summary.week_expenses)}</p>
                    <p class="text-[10px] text-muted-foreground">{t('dashboard.summary_transactions')}: {summary.week_count}</p>
                </div>
                <div class="rounded-lg border border-hairline p-3">
                    <div class="flex items-center gap-2 mb-1">
                        <TrendingUp class="size-3.5 text-muted-foreground" />
                        <span class="text-xs text-muted-foreground">{t('dashboard.summary_avg_daily')}</span>
                    </div>
                    <p class="text-lg font-bold">{formatAmount(summary.avg_daily_this_month)}</p>
                    <p class="text-[10px] text-muted-foreground">{t('dashboard.summary_this_month')}</p>
                </div>
                <div class="rounded-lg border border-hairline p-3">
                    <div class="flex items-center gap-2 mb-1">
                        {#if summary.trend_label === 'up'}
                            <TrendingUp class="size-3.5 text-destructive" />
                        {:else if summary.trend_label === 'down'}
                            <TrendingDown class="size-3.5 text-brand-green-dark dark:text-brand-green" />
                        {:else}
                            <Calendar class="size-3.5 text-muted-foreground" />
                        {/if}
                        <span class="text-xs text-muted-foreground">{t('dashboard.summary_trend')}</span>
                    </div>
                    <p class="text-lg font-bold {summary.trend_label === 'up' ? 'text-destructive' : 'text-brand-green-dark dark:text-brand-green'}">
                        {summary.trend_label === 'up' ? '+' : summary.trend_label === 'down' ? '-' : ''}{summary.trend_pct}%
                    </p>
                    <p class="text-[10px] text-muted-foreground">{t('dashboard.summary_vs_last')}</p>
                </div>
            </div>
        </div>
    {/if}

    {#if savings_goals.length > 0}
        <div class="rounded-xl border border-hairline bg-card p-3 sm:p-4 dark:bg-card">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold">{t('savings.title')}</h3>
                <a href="/savings" class="text-xs text-brand-green-dark dark:text-brand-green font-medium">{t('savings.add_goal')} &rarr;</a>
            </div>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                {#each savings_goals as goal}
                    <a href="/savings" class="rounded-lg border border-hairline bg-muted/30 p-2 hover:bg-muted/50 transition-colors">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-medium">{locale.value === 'ar' ? goal.name : goal.name_en}</span>
                            <span class="text-muted-foreground">{goal.progress}%</span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-muted">
                            <div class="h-1.5 rounded-full {goal.progress >= 100 ? 'bg-brand-green' : 'bg-accent-blue'}" style="width: {goal.progress}%"></div>
                        </div>
                    </a>
                {/each}
            </div>
        </div>
    {/if}

    <div class="rounded-xl border border-hairline bg-card p-3 sm:p-6 dark:bg-card">
        <h3 class="mb-2 text-sm font-semibold sm:mb-3 sm:text-base">{t('dashboard.trends')}</h3>
        <div class="h-56 sm:h-64">
            <LineChart
                labels={lineLabels}
                expenseValues={lineExpenses}
                incomeValues={lineIncome}
                expenseLabel={t('dashboard.legend_expenses')}
                incomeLabel={t('dashboard.legend_income')}
            />
        </div>
    </div>

    <div class="grid gap-3 sm:gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-hairline bg-card p-3 sm:p-6 dark:bg-card">
            <h3 class="mb-2 text-sm font-semibold sm:mb-3 sm:text-base">{t('dashboard.daily')}</h3>
            <div class="h-48 sm:h-56">
                <BarChart labels={barLabels} values={barValues} color="#e04444" />
            </div>
        </div>
        <div class="rounded-xl border border-hairline bg-card p-3 sm:p-6 dark:bg-card">
            <h3 class="mb-2 text-sm font-semibold sm:mb-3 sm:text-base">{t('dashboard.breakdown')}</h3>
            <div class="mx-auto h-48 w-full max-w-[200px] sm:h-56 sm:max-w-[260px]">
                <PieChart labels={pieLabels} values={pieValues} colors={pieColors} />
            </div>
        </div>
    </div>

    <div class="grid gap-3 sm:gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-hairline bg-card p-3 sm:p-6 dark:bg-card">
            <h3 class="mb-3 text-sm font-semibold sm:text-base">{t('dashboard.categories')}</h3>
            {#if categoryBreakdown.length === 0}
                <p class="py-4 text-center text-sm text-muted-foreground">{t('dashboard.no_data')}</p>
            {:else}
                <div class="space-y-2 sm:space-y-3">
                    {#each categoryBreakdown as cat}
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="size-2.5 shrink-0 rounded-full sm:size-3" style="background-color: {cat.color}"></div>
                            <span class="min-w-0 flex-1 truncate text-xs sm:text-sm">{localizedName(cat)}</span>
                            <span class="text-xs font-medium sm:text-sm">{formatAmount(cat.total)}</span>
                            <span class="w-8 text-right text-[10px] text-muted-foreground sm:w-10 sm:text-xs">{cat.percentage}%</span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-muted sm:h-2">
                            <div class="h-1.5 rounded-full transition-all sm:h-2" style="width: {cat.percentage}%; background-color: {cat.color}"></div>
                        </div>
                    {/each}
                </div>
            {/if}
        </div>

        <div class="flex flex-col rounded-xl border border-hairline bg-card dark:bg-card">
            <div class="border-b border-hairline p-3 sm:p-4">
                <h3 class="text-sm font-semibold sm:text-base">{t('dashboard.chat')}</h3>
                <p class="text-[10px] text-muted-foreground sm:text-xs">{t('dashboard.chat_subtitle')}</p>
            </div>
            <div class="flex-1 space-y-2 overflow-y-auto p-3 min-h-[160px] max-h-[260px] sm:min-h-[200px] sm:max-h-[300px] sm:space-y-3 sm:p-4">
                {#each chatMessages as msg}
                    <div class="flex {msg.role === 'user' ? 'justify-end' : 'justify-start'}">
                        <div class="max-w-[85%] rounded-xl px-3 py-1.5 text-xs sm:max-w-[80%] sm:px-4 sm:py-2 sm:text-sm {msg.role === 'user' ? 'bg-brand-green text-brand-teal-deep' : 'bg-muted'}">
                            {msg.content}
                        </div>
                    </div>
                {/each}
            </div>
            <div class="border-t border-hairline p-2 sm:p-3">
                <form onsubmit={(e) => { e.preventDefault(); sendChatMessage(); }} class="flex gap-2">
                    <Input placeholder={t('dashboard.chat_placeholder')} class="flex-1" bind:value={chatInput} />
                    <Button type="submit" size="icon" disabled={!chatInput.trim()}>
                        <Send class="size-3.5 sm:size-4" />
                    </Button>
                </form>
            </div>
        </div>
    </div>

    {#if recentTransactions.length > 0}
        <div class="rounded-xl border border-hairline bg-card dark:bg-card">
            <div class="flex items-center justify-between border-b border-hairline p-3 sm:p-4">
                <h3 class="text-sm font-semibold sm:text-base">{t('dashboard.recent')}</h3>
            </div>
            <div class="space-y-1 p-2 sm:space-y-2 sm:p-4">
                {#each recentTransactions as tx}
                    <div class="flex items-center gap-2 rounded-lg p-1.5 hover:bg-muted/50 sm:gap-3 sm:p-2">
                        <div class="flex size-7 shrink-0 items-center justify-center rounded-full sm:size-8" style="background-color: {tx.category?.color || '#6b7280'}20">
                            {#if tx.type === 'expense'}
                                <ArrowUpRight class="size-3.5 sm:size-4" style="color: {tx.category?.color || '#6b7280'}" />
                            {:else}
                                <ArrowDownLeft class="size-3.5 sm:size-4" style="color: {tx.category?.color || '#6b7280'}" />
                            {/if}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-medium sm:text-sm">{tx.description}</p>
                            <p class="text-[10px] text-muted-foreground sm:text-xs">{localizedName(tx.category) || t('transactions.no_category_label')}</p>
                        </div>
                        <p class="text-xs font-semibold sm:text-sm {tx.type === 'expense' ? 'text-destructive' : 'text-brand-green-dark dark:text-brand-green'}">
                            {tx.type === 'expense' ? '-' : '+'}{formatAmount(tx.amount)}
                        </p>
                    </div>
                {/each}
            </div>
        </div>
    {/if}
</div>
</PullToRefresh>
