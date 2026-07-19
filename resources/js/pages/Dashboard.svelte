<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
        ],
    };
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import ArrowUpRight from 'lucide-svelte/icons/arrow-up-right';
    import ArrowDownLeft from 'lucide-svelte/icons/arrow-down-left';
    import Send from 'lucide-svelte/icons/send';
    import Wallet from 'lucide-svelte/icons/wallet';
    import TrendingDown from 'lucide-svelte/icons/trending-down';
    import TrendingUp from 'lucide-svelte/icons/trending-up';
    import Calendar from 'lucide-svelte/icons/calendar';
    import { t, localizedName } from '@/lib/locale.svelte';

    let {
        stats = {
            total_expenses: 0,
            total_income: 0,
            balance: 0,
            transaction_count: 0,
            this_month_expenses: 0,
            this_month_income: 0,
        },
        categoryBreakdown = [] as { name: string; total: number; color: string; percentage: number }[],
        recentTransactions = [] as any[],
    } = $props();

    let chatMessages = $state([{ role: 'assistant', content: t('dashboard.chat_greeting') }]);
    let chatInput = $state('');

    function sendChatMessage() {
        if (!chatInput.trim()) return;
        chatMessages = [...chatMessages, { role: 'user', content: chatInput }];
        chatInput = '';

        setTimeout(() => {
            chatMessages = [...chatMessages, {
                role: 'assistant',
                content: t('dashboard.chat_coming_soon')
            }];
        }, 1000);
    }

    function formatAmount(amount: number): string {
        return new Intl.NumberFormat('ar-SA').format(amount) + ' ' + t('common.sar');
    }
</script>

<AppHead title={t('dashboard.title')} />

<div class="flex h-full flex-col gap-6 p-4 md:p-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">{t('dashboard.title')}</h1>
            <p class="text-sm text-muted-foreground">{t('dashboard.welcome')}</p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-destructive/10">
                    <TrendingDown class="size-5 text-destructive" />
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">{t('dashboard.month_expenses')}</p>
                    <p class="text-xl font-bold text-destructive">{formatAmount(stats.this_month_expenses)}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-green-soft dark:bg-brand-green/10">
                    <TrendingUp class="size-5 text-brand-green-dark dark:text-brand-green" />
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">{t('dashboard.month_income')}</p>
                    <p class="text-xl font-bold text-brand-green-dark dark:text-brand-green">{formatAmount(stats.this_month_income)}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-accent-blue/10">
                    <Wallet class="size-5 text-accent-blue" />
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">{t('dashboard.remaining')}</p>
                    <p class="text-xl font-bold {stats.balance >= 0 ? 'text-accent-blue' : 'text-destructive'}">{formatAmount(stats.balance)}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-accent-orange/10">
                    <Calendar class="size-5 text-accent-orange" />
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">{t('dashboard.transactions_count')}</p>
                    <p class="text-xl font-bold">{stats.transaction_count}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-hairline bg-card p-6 dark:bg-card">
            <h3 class="mb-4 font-semibold">{t('dashboard.categories')}</h3>
            {#if categoryBreakdown.length === 0}
                <p class="text-sm text-muted-foreground">{t('dashboard.no_data')}</p>
            {:else}
                <div class="space-y-3">
                    {#each categoryBreakdown as cat}
                        <div class="flex items-center gap-3">
                            <div class="h-3 w-3 shrink-0 rounded-full" style="background-color: {cat.color}"></div>
                            <span class="flex-1 text-sm">{localizedName(cat)}</span>
                            <span class="text-sm font-medium">{formatAmount(cat.total)}</span>
                            <span class="w-10 text-right text-xs text-muted-foreground">{cat.percentage}%</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-muted">
                            <div class="h-2 rounded-full transition-all" style="width: {cat.percentage}%; background-color: {cat.color}"></div>
                        </div>
                    {/each}
                </div>
            {/if}
        </div>

        <div class="flex flex-col rounded-xl border border-hairline bg-card dark:bg-card">
            <div class="border-b border-hairline p-4">
                <h3 class="font-semibold">{t('dashboard.chat')}</h3>
                <p class="text-xs text-muted-foreground">{t('dashboard.chat_subtitle')}</p>
            </div>
            <div class="flex-1 space-y-3 overflow-y-auto p-4 min-h-[200px] max-h-[300px]">
                {#each chatMessages as msg}
                    <div class="flex {msg.role === 'user' ? 'justify-end' : 'justify-start'}">
                        <div class="max-w-[80%] rounded-xl px-4 py-2 text-sm {msg.role === 'user' ? 'bg-brand-green text-brand-teal-deep' : 'bg-muted'}">
                            {msg.content}
                        </div>
                    </div>
                {/each}
            </div>
            <div class="border-t border-hairline p-3">
                <form onsubmit={(e) => { e.preventDefault(); sendChatMessage(); }} class="flex gap-2">
                    <Input
                        placeholder={t('dashboard.chat_placeholder')}
                        class="flex-1"
                        bind:value={chatInput}
                    />
                    <Button type="submit" size="icon" disabled={!chatInput.trim()}>
                        <Send class="size-4" />
                    </Button>
                </form>
            </div>
        </div>
    </div>

    {#if recentTransactions.length > 0}
        <div class="rounded-xl border border-hairline bg-card dark:bg-card">
            <div class="flex items-center justify-between border-b border-hairline p-4">
                <h3 class="font-semibold">{t('dashboard.recent')}</h3>
            </div>
            <div class="space-y-2 p-4">
                {#each recentTransactions as tx}
                    <div class="flex items-center gap-3 rounded-lg p-2 hover:bg-muted/50">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full" style="background-color: {tx.category?.color || '#6b7280'}20">
                            {#if tx.type === 'expense'}
                                <ArrowUpRight class="size-4" style="color: {tx.category?.color || '#6b7280'}" />
                            {:else}
                                <ArrowDownLeft class="size-4" style="color: {tx.category?.color || '#6b7280'}" />
                            {/if}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{tx.description}</p>
                            <p class="text-xs text-muted-foreground">{localizedName(tx.category) || t('transactions.no_category_label')}</p>
                        </div>
                        <p class="text-sm font-semibold {tx.type === 'expense' ? 'text-destructive' : 'text-brand-green-dark dark:text-brand-green'}">
                            {tx.type === 'expense' ? '-' : '+'}{formatAmount(tx.amount)}
                        </p>
                    </div>
                {/each}
            </div>
        </div>
    {/if}
</div>
