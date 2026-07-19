<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }],
    };
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from '@/components/ui/dialog';
    import { Spinner } from '@/components/ui/spinner';
    import Plus from 'lucide-svelte/icons/plus';
    import Trash2 from 'lucide-svelte/icons/trash-2';
    import Pencil from 'lucide-svelte/icons/pencil';
    import ArrowUpRight from 'lucide-svelte/icons/arrow-up-right';
    import ArrowDownLeft from 'lucide-svelte/icons/arrow-down-left';
    import Send from 'lucide-svelte/icons/send';
    import { t, localizedName } from '@/lib/locale.svelte';

    let { type = 'expense', transactions = [] as any[], categories = [] as any[] } = $props();

    let showModal = $state(false);
    let editingId = $state<number | null>(null);
    let formAmount = $state('');
    let formDescription = $state('');
    let formDate = $state('');
    let formCategoryId = $state('');
    let loading = $state(false);
    let quickText = $state('');
    let quickLoading = $state(false);

    function openAddModal() {
        formAmount = ''; formDescription = ''; formCategoryId = '';
        formDate = new Date().toISOString().slice(0, 10);
        editingId = null; showModal = true;
    }
    function openEditModal(tx: any) {
        formAmount = String(tx.amount); formDescription = tx.description;
        formDate = tx.transaction_date;
        formCategoryId = tx.category?.id ? String(tx.category.id) : '';
        editingId = tx.id; showModal = true;
    }
    function save() {
        loading = true;
        router.visit(editingId ? `/transactions/${editingId}` : '/transactions', {
            method: editingId ? 'patch' : 'post',
            data: { amount: formAmount, description: formDescription, transaction_date: formDate, category_id: formCategoryId, type: editingId ? undefined : type },
            preserveScroll: true,
            onFinish: () => { loading = false; showModal = false; },
        });
    }
    function quickAdd() {
        if (!quickText.trim()) return;
        quickLoading = true;
        router.post('/transactions/quick', { text: quickText, type }, {
            preserveScroll: true,
            onFinish: () => { quickLoading = false; quickText = ''; },
        });
    }
    function del(tx: any) {
        if (confirm(t('transactions.delete_confirm'))) router.delete(`/transactions/${tx.id}`, { preserveScroll: true });
    }

    function formatAmount(amount: number): string {
        return new Intl.NumberFormat('ar-SA').format(amount) + ' ' + t('common.sar');
    }
</script>

<AppHead title={type === 'expense' ? t('transactions.expenses') : t('transactions.income')} />

<div class="min-w-0 space-y-4 p-4 sm:space-y-6 sm:p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold sm:text-2xl">{type === 'expense' ? t('transactions.expenses') : t('transactions.income')}</h1>
        <div class="flex gap-1">
            <a href="/transactions/expenses" class="rounded-full px-3 py-1.5 text-sm font-medium {type === 'expense' ? 'bg-destructive text-white' : 'bg-muted text-muted-foreground'}">{t('transactions.expenses_tab')}</a>
            <a href="/transactions/income" class="rounded-full px-3 py-1.5 text-sm font-medium {type === 'income' ? 'bg-brand-green text-brand-teal-deep' : 'bg-muted text-muted-foreground'}">{t('transactions.income_tab')}</a>
        </div>
    </div>

    <form onsubmit={(e) => { e.preventDefault(); quickAdd(); }} class="flex gap-2">
        <Input placeholder={type === 'expense' ? t('transactions.quick_add_expense') : t('transactions.quick_add_income')} bind:value={quickText} class="flex-1" />
        <Button type="submit" size="icon" class="rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90" disabled={quickLoading || !quickText.trim()}>
            {#if quickLoading}<Spinner class="size-4" />{:else}<Send class="size-4" />{/if}
        </Button>
        <Dialog open={showModal}>
            <DialogTrigger>
                <Button onclick={openAddModal} variant="outline" size="icon" class="rounded-full"><Plus class="size-4" /></Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader><DialogTitle>{editingId ? t('transactions.edit') : (type === 'expense' ? t('transactions.add_expense') : t('transactions.add_income'))}</DialogTitle></DialogHeader>
                <form onsubmit={(e) => { e.preventDefault(); save(); }} class="space-y-4">
                    <div><Label>{t('transactions.amount')}</Label><Input type="number" step="0.01" bind:value={formAmount} required /></div>
                    <div><Label>{t('transactions.description')}</Label><Input bind:value={formDescription} required /></div>
                    <div><Label>{t('transactions.date')}</Label><Input type="date" bind:value={formDate} required /></div>
                    <div>
                        <Label>{t('transactions.category')}</Label>
                        <select bind:value={formCategoryId} class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">{t('transactions.no_category')}</option>
                            {#each categories as c}<option value={c.id}>{localizedName(c)}</option>{/each}
                        </select>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" onclick={() => showModal = false}>{t('transactions.cancel')}</Button>
                        <Button type="submit" class="rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90" disabled={loading}>{#if loading}<Spinner class="mr-2" />{/if}{editingId ? t('transactions.save') : t('transactions.add')}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </form>

    {#if transactions.length > 0}
        <div class="rounded-xl border border-hairline bg-card dark:bg-card">
            <div class="flex items-center justify-between border-b border-hairline p-3 sm:p-4">
                <h3 class="text-sm font-semibold sm:text-base">{t('transactions.recent')}</h3>
            </div>
            <div class="space-y-1 p-2 sm:space-y-2 sm:p-4">
                {#each transactions as tx}
                    <div class="flex items-center gap-2 rounded-lg p-1.5 hover:bg-muted/50 sm:gap-3 sm:p-2">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full sm:h-8 sm:w-8" style="background-color: {tx.category?.color || '#6b7280'}20">
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
                        <Button variant="ghost" size="icon" class="size-6 sm:size-7" onclick={() => openEditModal(tx)}><Pencil class="size-3" /></Button>
                        <Button variant="ghost" size="icon" class="size-6 sm:size-7" onclick={() => del(tx)}><Trash2 class="size-3 text-destructive" /></Button>
                    </div>
                {/each}
            </div>
        </div>
    {:else}
        <div class="rounded-xl border border-dashed border-hairline p-8 text-center sm:p-12">
            <p class="text-xs text-muted-foreground sm:text-sm">{t('transactions.empty')}</p>
        </div>
    {/if}
</div>
