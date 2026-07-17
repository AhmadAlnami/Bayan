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
        if (confirm('حذف هذه المعاملة؟')) router.delete(`/transactions/${tx.id}`, { preserveScroll: true });
    }

    function formatAmount(amount: number): string {
        return new Intl.NumberFormat('ar-SA').format(amount) + ' ر.س';
    }
</script>

<AppHead title={type === 'expense' ? 'المصروفات' : 'الدخل'} />

<div class="flex h-full flex-col gap-6 p-4 md:p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">{type === 'expense' ? 'المصروفات' : 'الدخل'}</h1>
        <div class="flex gap-1">
            <a href="/transactions/expenses" class="rounded-full px-3 py-1.5 text-sm font-medium {type === 'expense' ? 'bg-red-500 text-white' : 'bg-muted text-muted-foreground'}">مصروفات</a>
            <a href="/transactions/income" class="rounded-full px-3 py-1.5 text-sm font-medium {type === 'income' ? 'bg-emerald-500 text-white' : 'bg-muted text-muted-foreground'}">دخل</a>
        </div>
    </div>

    <form onsubmit={(e) => { e.preventDefault(); quickAdd(); }} class="flex gap-2">
        <Input placeholder={type === 'expense' ? 'إضافة سريعة... 45 ريال قهوة' : 'إضافة سريعة... 5000 راتب'} bind:value={quickText} class="flex-1" />
        <Button type="submit" size="icon" disabled={quickLoading || !quickText.trim()}>
            {#if quickLoading}<Spinner class="size-4" />{:else}<Send class="size-4" />{/if}
        </Button>
        <Dialog open={showModal}>
            <DialogTrigger asChild>
                <Button onclick={openAddModal} variant="outline" size="icon"><Plus class="size-4" /></Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader><DialogTitle>{editingId ? 'تعديل' : (type === 'expense' ? 'إضافة مصروف' : 'إضافة دخل')}</DialogTitle></DialogHeader>
                <form onsubmit={(e) => { e.preventDefault(); save(); }} class="space-y-4">
                    <div><Label>المبلغ</Label><Input type="number" step="0.01" bind:value={formAmount} required /></div>
                    <div><Label>الوصف</Label><Input bind:value={formDescription} required /></div>
                    <div><Label>التاريخ</Label><Input type="date" bind:value={formDate} required /></div>
                    <div>
                        <Label>التصنيف</Label>
                        <select bind:value={formCategoryId} class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">بدون</option>
                            {#each categories as c}<option value={c.id}>{c.name}</option>{/each}
                        </select>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" type="button" onclick={() => showModal = false}>إلغاء</Button>
                        <Button type="submit" disabled={loading}>{#if loading}<Spinner class="mr-2" />{/if}{editingId ? 'حفظ' : 'إضافة'}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </form>

    {#if transactions.length > 0}
        <div class="rounded-xl border bg-card">
            <div class="flex items-center justify-between border-b p-4">
                <h3 class="font-semibold">آخر المعاملات</h3>
            </div>
            <div class="p-4 space-y-2">
                {#each transactions as tx}
                    <div class="flex items-center gap-3 rounded-lg p-2 hover:bg-accent/50">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full" style="background-color: {tx.category?.color || '#6b7280'}20">
                            {#if tx.type === 'expense'}
                                <ArrowUpRight class="size-4" style="color: {tx.category?.color || '#6b7280'}" />
                            {:else}
                                <ArrowDownLeft class="size-4" style="color: {tx.category?.color || '#6b7280'}" />
                            {/if}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{tx.description}</p>
                            <p class="text-xs text-muted-foreground">{tx.category?.name || 'بدون تصنيف'}</p>
                        </div>
                        <p class="text-sm font-semibold {tx.type === 'expense' ? 'text-red-600' : 'text-green-600'}">
                            {tx.type === 'expense' ? '-' : '+'}{formatAmount(tx.amount)}
                        </p>
                        <Button variant="ghost" size="icon" class="size-7" onclick={() => openEditModal(tx)}><Pencil class="size-3" /></Button>
                        <Button variant="ghost" size="icon" class="size-7" onclick={() => del(tx)}><Trash2 class="size-3 text-red-500" /></Button>
                    </div>
                {/each}
            </div>
        </div>
    {:else}
        <div class="rounded-xl border border-dashed p-12 text-center">
            <p class="text-muted-foreground">لا توجد معاملات. أضف من الحقل أعلاه</p>
        </div>
    {/if}
</div>
