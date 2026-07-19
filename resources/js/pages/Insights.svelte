<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Insights', href: '/insights' }],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import TrendingDown from 'lucide-svelte/icons/trending-down';
    import TrendingUp from 'lucide-svelte/icons/trending-up';
    import PiggyBank from 'lucide-svelte/icons/piggy-bank';
    import Target from 'lucide-svelte/icons/target';
    import Zap from 'lucide-svelte/icons/zap';
    import Gauge from 'lucide-svelte/icons/gauge';
    import { t, localizedName, localeState } from '@/lib/locale.svelte';

    const { locale } = localeState();

    let { insights }: {
        insights: {
            monthComparison: { thisMonthExpenses: number; lastMonthExpenses: number; expenseChange: number; thisMonthIncome: number; lastMonthIncome: number; incomeChange: number };
            topCategory: { name: string; name_en: string; color: string; total: number; percentage: number };
            dailyAverage: { today: number; lastMonth: number; projected: number };
            weekdayBreakdown: { days: { name: string; nameEn: string; total: number; count: number }[]; highest: any; lowest: any };
            savingsRate: { saved: number; rate: number; income: number; expenses: number };
            spendingPace: { pctElapsed: number; pctSpent: number; status: string };
        };
    } = $props();

    function formatAmount(amount: number): string {
        const numLocale = locale.value === 'ar' ? 'ar-SA' : 'en-US';
        return new Intl.NumberFormat(numLocale).format(amount) + ' ' + t('common.sar');
    }

    const arShortDays = ['ح', 'ن', 'ث', 'أ', 'خ', 'ج', 'س'];
    const paceLabel = $derived(insights.spendingPace.status === 'fast' ? t('insights.paceFast') : insights.spendingPace.status === 'slow' ? t('insights.paceSlow') : t('insights.paceNormal'));
    const paceColor = $derived(insights.spendingPace.status === 'fast' ? 'text-destructive' : insights.spendingPace.status === 'slow' ? 'text-brand-green-dark dark:text-brand-green' : 'text-accent-blue');
</script>

<AppHead title={t('insights.title')} />

<div class="space-y-4 p-4 sm:space-y-6 sm:p-6">
    <div>
        <h1 class="text-xl font-semibold sm:text-2xl">{t('insights.title')}</h1>
        <p class="text-sm text-muted-foreground">{t('insights.subtitle')}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card animate-fade-in-up">
            <div class="mb-3 flex items-center gap-2">
                <TrendingDown class="size-5 text-destructive" />
                <h3 class="font-semibold text-sm">{t('insights.monthCompare')}</h3>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-muted-foreground">{t('dashboard.legend_expenses')}</span>
                    <span class="font-medium {insights.monthComparison.expenseChange > 0 ? 'text-destructive' : 'text-brand-green-dark dark:text-brand-green'}">
                        {insights.monthComparison.expenseChange > 0 ? '+' : ''}{insights.monthComparison.expenseChange}%
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-muted-foreground">{t('dashboard.legend_income')}</span>
                    <span class="font-medium {insights.monthComparison.incomeChange > 0 ? 'text-brand-green-dark dark:text-brand-green' : 'text-destructive'}">
                        {insights.monthComparison.incomeChange > 0 ? '+' : ''}{insights.monthComparison.incomeChange}%
                    </span>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card animate-fade-in-up animate-stagger-2">
            <div class="mb-3 flex items-center gap-2">
                <Target class="size-5 text-accent-orange" />
                <h3 class="font-semibold text-sm">{t('insights.topCategory')}</h3>
            </div>
            <div class="flex items-center gap-2">
                <div class="size-3 rounded-full shrink-0" style="background-color: {insights.topCategory.color}"></div>
                <span class="text-sm">{localizedName(insights.topCategory)}</span>
                <span class="text-sm font-medium ms-auto">{insights.topCategory.percentage}%</span>
            </div>
            <p class="text-xs text-muted-foreground mt-1">{formatAmount(insights.topCategory.total)} {t('insights.ofTotal')}</p>
        </div>

        <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card animate-fade-in-up animate-stagger-3">
            <div class="mb-3 flex items-center gap-2">
                <Gauge class="size-5 text-accent-blue" />
                <h3 class="font-semibold text-sm">{t('insights.dailyAverage')}</h3>
            </div>
            <p class="text-2xl font-bold">{formatAmount(insights.dailyAverage.today)}</p>
            <div class="flex gap-4 mt-1 text-xs text-muted-foreground">
                <span>{t('insights.lastMonth')}: {formatAmount(insights.dailyAverage.lastMonth)}</span>
            </div>
        </div>

        <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card animate-fade-in-up">
            <div class="mb-3 flex items-center gap-2">
                <PiggyBank class="size-5 text-brand-green-dark dark:text-brand-green" />
                <h3 class="font-semibold text-sm">{t('insights.savingsRate')}</h3>
            </div>
            <p class="text-2xl font-bold text-brand-green-dark dark:text-brand-green">{insights.savingsRate.rate}%</p>
            <p class="text-xs text-muted-foreground mt-1">{formatAmount(insights.savingsRate.saved)} {t('insights.savedThisMonth')}</p>
        </div>

        <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card animate-fade-in-up animate-stagger-2">
            <div class="mb-3 flex items-center gap-2">
                <Zap class="size-5 text-accent-purple" />
                <h3 class="font-semibold text-sm {paceColor}">{paceLabel}</h3>
            </div>
            <p class="text-xs text-muted-foreground">{t('insights.paceDesc').replace('{elapsed}', String(insights.spendingPace.pctElapsed)).replace('{spent}', String(insights.spendingPace.pctSpent))}</p>
            <div class="h-2 w-full rounded-full bg-muted mt-2">
                <div class="h-2 rounded-full {insights.spendingPace.status === 'fast' ? 'bg-destructive' : 'bg-brand-green-dark dark:bg-brand-green'}" style="width: {Math.min(100, insights.spendingPace.pctSpent)}%"></div>
            </div>
        </div>

        <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card animate-fade-in-up animate-stagger-3">
            <div class="mb-3 flex items-center gap-2">
                <TrendingUp class="size-5 text-accent-orange" />
                <h3 class="font-semibold text-sm">{t('insights.weekdays')}</h3>
            </div>
            {#if insights.weekdayBreakdown.highest}
                <p class="text-sm">{t('insights.highestDay')}: <span class="font-medium">{locale.value === 'ar' ? insights.weekdayBreakdown.highest.name : insights.weekdayBreakdown.highest.nameEn}</span></p>
            {/if}
            {#if insights.weekdayBreakdown.lowest}
                <p class="text-sm mt-1">{t('insights.lowestDay')}: <span class="font-medium">{locale.value === 'ar' ? insights.weekdayBreakdown.lowest.name : insights.weekdayBreakdown.lowest.nameEn}</span></p>
            {/if}
            <div class="flex items-end gap-1 h-16 mt-3">
                {#each insights.weekdayBreakdown.days as day, i}
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full rounded-t-sm bg-accent-blue/60" style="height: {day.total > 0 ? Math.max(4, (day.total / (insights.weekdayBreakdown.highest?.total || 1)) * 100) : 0}%"></div>
                        <span class="text-[9px] text-muted-foreground">{locale.value === 'ar' ? arShortDays[i] : day.nameEn.substring(0, 2)}</span>
                    </div>
                {/each}
            </div>
        </div>
    </div>
</div>
