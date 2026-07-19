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
    import Wallet from 'lucide-svelte/icons/wallet';
    import Trash2 from 'lucide-svelte/icons/trash-2';
    import { t, localizedName, localeState } from '@/lib/locale.svelte';
    import PullToRefresh from '@/components/PullToRefresh.svelte';

    const { locale } = localeState();

    let { budgets = [] as any[], categories = [] as any[] } = $props();

    let activeTab = $state<'daily' | 'weekly' | 'monthly' | 'category'>('daily');

    let dailyAmount = $state('');
    let weeklyAmount = $state('');
    let monthlyAmount = $state('');
    let categoryAmount = $state('');
    let selectedCategoryId = $state('');
    let saving = $state(false);

    const tabs = [
        { key: 'daily', title: () => t('budgets.daily') },
        { key: 'weekly', title: () => t('budgets.weekly') },
        { key: 'monthly', title: () => t('budgets.monthly') },
        { key: 'category', title: () => t('budgets.category') },
    ];

    function getBudget(type: string, categoryId: number | null = null) {
        return budgets.find((b: any) => {
            if (type === 'category') return b.type === 'category' && b.category?.id === categoryId;
            return b.type === type;
        });
    }

    function formatAmount(amount: number): string {
        const numLocale = locale.value === 'ar' ? 'ar-SA' : 'en-US';
        return new Intl.NumberFormat(numLocale).format(amount) + ' ' + t('common.sar');
    }

    function saveBudget(type: string, amount: string, categoryId: string | null = null) {
        if (!String(amount).trim()) return;
        saving = true;
        const data: Record<string, any> = { type, amount: parseFloat(String(amount)) };
        if (type === 'category' && categoryId) data.category_id = parseInt(categoryId);

        router.post('/budgets', data, {
            preserveScroll: true,
            onFinish: () => {
                saving = false;
                if (type === 'daily') dailyAmount = '';
                else if (type === 'weekly') weeklyAmount = '';
                else if (type === 'monthly') monthlyAmount = '';
                else if (type === 'category') { categoryAmount = ''; selectedCategoryId = ''; }
            },
        });
    }

    function deleteBudget(budget: any) {
        router.delete(`/budgets/${budget.id}`, { preserveScroll: true });
    }

    function getProgressColor(progress: number) {
        if (progress >= 100) return 'bg-destructive';
        if (progress >= 80) return 'bg-accent-orange';
        return 'bg-brand-green';
    }

    const dailyBudget = $derived(getBudget('daily'));
    const weeklyBudget = $derived(getBudget('weekly'));
    const monthlyBudget = $derived(getBudget('monthly'));

    const categoryBudgets = $derived(
        budgets.filter((b: any) => b.type === 'category')
    );

    const unusedCategories = $derived(
        categories.filter((c: any) => !budgets.some((b: any) => b.type === 'category' && b.category?.id === c.id))
    );
</script>

<AppHead title={t('budgets.title')} />

<PullToRefresh>
    <div class="min-w-0 space-y-4 p-4 sm:space-y-6 sm:p-6">
        <div>
            <h1 class="text-xl font-semibold sm:text-2xl">{t('budgets.title')}</h1>
            <p class="text-sm text-muted-foreground">{t('budgets.set_budget')}</p>
        </div>

        <div class="flex gap-1 overflow-x-auto rounded-full bg-muted p-0.5">
            {#each tabs as tab}
                <button
                    onclick={() => activeTab = tab.key as any}
                    class="shrink-0 rounded-full px-3 py-1.5 text-sm font-medium transition-all {activeTab === tab.key ? 'bg-card text-ink shadow-sm dark:text-on-dark' : 'text-muted-foreground'}"
                >
                    {tab.title()}
                </button>
            {/each}
        </div>

        <div class="animate-fade-in-up space-y-3 sm:space-y-4">
            {#if activeTab === 'daily'}
                <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card sm:p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-green-soft dark:bg-brand-green/10">
                            <Wallet class="size-4 text-brand-green-dark dark:text-brand-green" />
                        </div>
                        <div>
                            <h3 class="font-semibold">{t('budgets.daily')}</h3>
                            <p class="text-xs text-muted-foreground">{t('budgets.spent')}: {formatAmount(dailyBudget?.spent || 0)}</p>
                        </div>
                    </div>

                    {#if dailyBudget}
                        <div class="mb-3 sm:mb-4">
                            <div class="mb-1 flex justify-between text-xs sm:text-sm">
                                <span>{formatAmount(dailyBudget.spent || 0)} / {formatAmount(dailyBudget.amount)}</span>
                                <span class="{dailyBudget.progress >= 100 ? 'text-destructive font-medium' : 'text-brand-green-dark dark:text-brand-green'}">
                                    {dailyBudget.progress}%
                                    {#if dailyBudget.progress >= 100}{t('budgets.over_budget')}{:else}{t('budgets.under_budget')}{/if}
                                </span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-muted sm:h-2.5">
                                <div class="h-2 rounded-full transition-all sm:h-2.5 {getProgressColor(dailyBudget.progress)}" style="width: {Math.min(dailyBudget.progress, 100)}%"></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button variant="ghost" size="icon" class="text-destructive" onclick={() => deleteBudget(dailyBudget)}><Trash2 class="size-4" /></Button>
                        </div>
                    {/if}

                    <div class="mt-3 border-t border-hairline pt-3 sm:mt-4 sm:pt-4">
                        <p class="mb-2 text-xs text-muted-foreground sm:text-sm">{dailyBudget ? t('budgets.update_budget') : t('budgets.set_daily')}</p>
                        <form onsubmit={(e) => { e.preventDefault(); saveBudget('daily', dailyAmount); }} class="flex gap-2">
                            <Input type="number" step="0.01" placeholder={t('budgets.amount')} bind:value={dailyAmount} class="flex-1" />
                            <Button type="submit" class="rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90" disabled={saving || !String(dailyAmount).trim()}>{t('budgets.save')}</Button>
                        </form>
                    </div>
                </div>

            {:else if activeTab === 'weekly'}
                <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card sm:p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-green-soft dark:bg-brand-green/10">
                            <Wallet class="size-4 text-brand-green-dark dark:text-brand-green" />
                        </div>
                        <div>
                            <h3 class="font-semibold">{t('budgets.weekly')}</h3>
                            <p class="text-xs text-muted-foreground">{t('budgets.spent')}: {formatAmount(weeklyBudget?.spent || 0)}</p>
                        </div>
                    </div>

                    {#if weeklyBudget}
                        <div class="mb-3 sm:mb-4">
                            <div class="mb-1 flex justify-between text-xs sm:text-sm">
                                <span>{formatAmount(weeklyBudget.spent || 0)} / {formatAmount(weeklyBudget.amount)}</span>
                                <span class="{weeklyBudget.progress >= 100 ? 'text-destructive font-medium' : 'text-brand-green-dark dark:text-brand-green'}">
                                    {weeklyBudget.progress}%
                                    {#if weeklyBudget.progress >= 100}{t('budgets.over_budget')}{:else}{t('budgets.under_budget')}{/if}
                                </span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-muted sm:h-2.5">
                                <div class="h-2 rounded-full transition-all sm:h-2.5 {getProgressColor(weeklyBudget.progress)}" style="width: {Math.min(weeklyBudget.progress, 100)}%"></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button variant="ghost" size="icon" class="text-destructive" onclick={() => deleteBudget(weeklyBudget)}><Trash2 class="size-4" /></Button>
                        </div>
                    {/if}

                    <div class="mt-3 border-t border-hairline pt-3 sm:mt-4 sm:pt-4">
                        <p class="mb-2 text-xs text-muted-foreground sm:text-sm">{weeklyBudget ? t('budgets.update_budget') : t('budgets.set_weekly')}</p>
                        <form onsubmit={(e) => { e.preventDefault(); saveBudget('weekly', weeklyAmount); }} class="flex gap-2">
                            <Input type="number" step="0.01" placeholder={t('budgets.amount')} bind:value={weeklyAmount} class="flex-1" />
                            <Button type="submit" class="rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90" disabled={saving || !String(weeklyAmount).trim()}>{t('budgets.save')}</Button>
                        </form>
                    </div>
                </div>

            {:else if activeTab === 'monthly'}
                <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card sm:p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-green-soft dark:bg-brand-green/10">
                            <Wallet class="size-4 text-brand-green-dark dark:text-brand-green" />
                        </div>
                        <div>
                            <h3 class="font-semibold">{t('budgets.monthly')}</h3>
                            <p class="text-xs text-muted-foreground">{t('budgets.spent')}: {formatAmount(monthlyBudget?.spent || 0)}</p>
                        </div>
                    </div>

                    {#if monthlyBudget}
                        <div class="mb-3 sm:mb-4">
                            <div class="mb-1 flex justify-between text-xs sm:text-sm">
                                <span>{formatAmount(monthlyBudget.spent || 0)} / {formatAmount(monthlyBudget.amount)}</span>
                                <span class="{monthlyBudget.progress >= 100 ? 'text-destructive font-medium' : 'text-brand-green-dark dark:text-brand-green'}">
                                    {monthlyBudget.progress}%
                                    {#if monthlyBudget.progress >= 100}{t('budgets.over_budget')}{:else}{t('budgets.under_budget')}{/if}
                                </span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-muted sm:h-2.5">
                                <div class="h-2 rounded-full transition-all sm:h-2.5 {getProgressColor(monthlyBudget.progress)}" style="width: {Math.min(monthlyBudget.progress, 100)}%"></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button variant="ghost" size="icon" class="text-destructive" onclick={() => deleteBudget(monthlyBudget)}><Trash2 class="size-4" /></Button>
                        </div>
                    {/if}

                    <div class="mt-3 border-t border-hairline pt-3 sm:mt-4 sm:pt-4">
                        <p class="mb-2 text-xs text-muted-foreground sm:text-sm">{monthlyBudget ? t('budgets.update_budget') : t('budgets.set_monthly')}</p>
                        <form onsubmit={(e) => { e.preventDefault(); saveBudget('monthly', monthlyAmount); }} class="flex gap-2">
                            <Input type="number" step="0.01" placeholder={t('budgets.amount')} bind:value={monthlyAmount} class="flex-1" />
                            <Button type="submit" class="rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90" disabled={saving || !String(monthlyAmount).trim()}>{t('budgets.save')}</Button>
                        </form>
                    </div>
                </div>

            {:else if activeTab === 'category'}
                {#each categoryBudgets as b}
                    <div class="animate-fade-in-up rounded-xl border border-hairline bg-card p-4 dark:bg-card sm:p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-full" style="background-color: {b.category.color}20">
                                <Wallet class="size-4" style="color: {b.category.color}" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold">{localizedName(b.category)}</h3>
                                <p class="text-xs text-muted-foreground">{t('budgets.spent')}: {formatAmount(b.spent || 0)}</p>
                            </div>
                            <Button variant="ghost" size="icon" class="text-destructive" onclick={() => deleteBudget(b)}><Trash2 class="size-4" /></Button>
                        </div>

                        <div class="mb-1 flex justify-between text-xs sm:text-sm">
                            <span>{formatAmount(b.spent || 0)} / {formatAmount(b.amount)}</span>
                            <span class="{b.progress >= 100 ? 'text-destructive font-medium' : 'text-brand-green-dark dark:text-brand-green'}">
                                {b.progress}%
                                {#if b.progress >= 100}{t('budgets.over_budget')}{:else}{t('budgets.under_budget')}{/if}
                            </span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-muted sm:h-2.5">
                            <div class="h-2 rounded-full transition-all sm:h-2.5 {getProgressColor(b.progress)}" style="width: {Math.min(b.progress, 100)}%"></div>
                        </div>
                    </div>
                {/each}

                {#if categoryBudgets.length === 0}
                    <div class="rounded-xl border border-dashed border-hairline p-6 text-center sm:p-8">
                        <p class="text-xs text-muted-foreground sm:text-sm">{t('budgets.no_budget')}</p>
                    </div>
                {:else}
                    <div class="mt-2"></div>
                {/if}

                <div class="animate-fade-in-up rounded-xl border border-hairline bg-card p-4 dark:bg-card sm:p-6">
                    <p class="mb-3 text-xs font-medium text-muted-foreground sm:text-sm">{t('budgets.set_budget')}</p>
                    <form onsubmit={(e) => { e.preventDefault(); saveBudget('category', categoryAmount, selectedCategoryId); }} class="space-y-3">
                        <select
                            bind:value={selectedCategoryId}
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">{t('budgets.select_category')}</option>
                            {#each unusedCategories as c}
                                <option value={c.id}>{localizedName(c)}</option>
                            {/each}
                        </select>
                        <div class="flex gap-2">
                            <Input type="number" step="0.01" placeholder={t('budgets.amount')} bind:value={categoryAmount} class="flex-1" />
                            <Button type="submit" class="rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90" disabled={saving || !String(categoryAmount).trim() || !selectedCategoryId}>{t('budgets.save')}</Button>
                        </div>
                    </form>
                </div>
            {/if}
        </div>
    </div>
</PullToRefresh>
