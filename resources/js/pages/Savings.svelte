<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Savings', href: '/savings' }],
    };
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from '@/components/ui/dialog';
    import Plus from 'lucide-svelte/icons/plus';
    import Trash2 from 'lucide-svelte/icons/trash-2';
    import Pencil from 'lucide-svelte/icons/pencil';
    import PiggyBank from 'lucide-svelte/icons/piggy-bank';
    import Target from 'lucide-svelte/icons/target';
    import Calendar from 'lucide-svelte/icons/calendar';
    import TrendingUp from 'lucide-svelte/icons/trending-up';
    import { t, localizedName, localeState } from '@/lib/locale.svelte';
    import PullToRefresh from '@/components/PullToRefresh.svelte';
    import ConfirmDialog from '@/components/ConfirmDialog.svelte';

    const { locale } = localeState();

    let { goals = [] as any[] } = $props();

    let showModal = $state(false);
    let editingId = $state<number | null>(null);
    let formName = $state('');
    let formTarget = $state('');
    let formCategory = $state('');
    let formDeadline = $state('');
    let formAutoAmount = $state('');
    let formAutoDay = $state('');
    let loading = $state(false);

    let showDeleteConfirm = $state(false);
    let deleteTarget = $state<any>(null);

    let depositGoalId = $state<number | null>(null);
    let depositAmount = $state('');
    let depositLoading = $state(false);

    const categories = [
        { value: 'سيارة', label: () => t('savings.car') },
        { value: 'منزل', label: () => t('savings.house') },
        { value: 'سفر', label: () => t('savings.travel') },
        { value: 'طوارئ', label: () => t('savings.emergency') },
        { value: 'تعليم', label: () => t('savings.education') },
        { value: 'أخرى', label: () => t('savings.other') },
    ];

    function openAddModal() {
        formName = ''; formTarget = ''; formCategory = ''; formDeadline = '';
        formAutoAmount = ''; formAutoDay = '';
        editingId = null; showModal = true;
    }

    function openEditModal(goal: any) {
        formName = locale.value === 'ar' ? goal.name : goal.name_en;
        formTarget = String(goal.target_amount);
        formCategory = goal.category;
        formDeadline = goal.deadline || '';
        formAutoAmount = goal.auto_save_amount ? String(goal.auto_save_amount) : '';
        formAutoDay = goal.auto_save_day ? String(goal.auto_save_day) : '';
        editingId = goal.id; showModal = true;
    }

    function save() {
        loading = true;
        const data: Record<string, any> = {
            name: formName,
            target_amount: parseFloat(formTarget),
            category: formCategory || 'أخرى',
            deadline: formDeadline || undefined,
            auto_save_amount: formAutoAmount ? parseFloat(formAutoAmount) : undefined,
            auto_save_day: formAutoDay ? parseInt(formAutoDay) : undefined,
        };

        router.visit(editingId ? `/savings/${editingId}` : '/savings', {
            method: editingId ? 'patch' : 'post',
            data,
            preserveScroll: true,
            onFinish: () => { loading = false; showModal = false; },
        });
    }

    function confirmDelete(goal: any) {
        deleteTarget = goal; showDeleteConfirm = true;
    }

    function doDelete() {
        if (!deleteTarget) return;
        router.delete(`/savings/${deleteTarget.id}`, { preserveScroll: true, onFinish: () => { showDeleteConfirm = false; } });
    }

    function deposit(goalId: number) {
        const amount = parseFloat(String(depositAmount));
        if (!amount || amount <= 0) return;
        depositLoading = true;
        router.post(`/savings/${goalId}/deposit`, {
            amount,
        }, {
            preserveScroll: true,
            onFinish: () => {
                depositLoading = false;
                depositAmount = '';
                depositGoalId = null;
            },
        });
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

    function getProgressColor(progress: number) {
        if (progress >= 100) return 'bg-brand-green';
        if (progress >= 50) return 'bg-accent-blue';
        return 'bg-accent-orange';
    }

    function getDaysLeft(deadline: string | null): number | null {
        if (!deadline) return null;
        const d = new Date(deadline);
        const now = new Date();
        return Math.ceil((d.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
    }

    const totalSaved = $derived(goals.reduce((sum: number, g: any) => sum + g.current_amount, 0));
    const totalTarget = $derived(goals.reduce((sum: number, g: any) => sum + g.target_amount, 0));
</script>

<AppHead title={t('savings.title')} />

<PullToRefresh>
    <div class="min-w-0 space-y-4 p-4 sm:space-y-6 sm:p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold sm:text-2xl">{t('savings.title')}</h1>
                <p class="text-sm text-muted-foreground">{t('savings.subtitle')}</p>
            </div>
            <Button class="rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90 gap-1.5" size="sm" onclick={openAddModal}>
                <Plus class="size-4" />
                <span class="hidden sm:inline">{t('savings.add_goal')}</span>
            </Button>
        </div>

        {#if goals.length > 0}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl border border-hairline bg-card p-3 dark:bg-card">
                    <p class="text-xs text-muted-foreground">{t('savings.total_saved')}</p>
                    <p class="mt-1 text-lg font-bold text-brand-green-dark dark:text-brand-green">{formatAmount(totalSaved)}</p>
                </div>
                <div class="rounded-xl border border-hairline bg-card p-3 dark:bg-card">
                    <p class="text-xs text-muted-foreground">{t('savings.total_target')}</p>
                    <p class="mt-1 text-lg font-bold">{formatAmount(totalTarget)}</p>
                </div>
                <div class="rounded-xl border border-hairline bg-card p-3 dark:bg-card">
                    <p class="text-xs text-muted-foreground">{t('savings.goals_count')}</p>
                    <p class="mt-1 text-lg font-bold">{goals.length}</p>
                </div>
                <div class="rounded-xl border border-hairline bg-card p-3 dark:bg-card">
                    <p class="text-xs text-muted-foreground">{t('savings.overall_progress')}</p>
                    <p class="mt-1 text-lg font-bold text-accent-blue">{totalTarget > 0 ? Math.round((totalSaved / totalTarget) * 100) : 0}%</p>
                </div>
            </div>
        {/if}

        {#if goals.length === 0}
            <div class="rounded-xl border border-dashed border-hairline p-8 text-center">
                <PiggyBank class="mx-auto size-8 text-muted-foreground mb-3" />
                <p class="text-sm text-muted-foreground">{t('savings.empty')}</p>
                <Button class="mt-4 rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90" size="sm" onclick={openAddModal}>
                    <Plus class="size-4 mr-1" /> {t('savings.add_goal')}
                </Button>
            </div>
        {:else}
            <div class="grid gap-4 sm:grid-cols-2">
                {#each goals as goal (goal.id)}
                    <div class="rounded-xl border border-hairline bg-card p-4 dark:bg-card">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-green-soft dark:bg-brand-green/10">
                                    <Target class="size-4 text-brand-green-dark dark:text-brand-green" />
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-sm truncate">{localizedName({ name: goal.name, name_en: goal.name_en })}</h3>
                                    <p class="text-xs text-muted-foreground">
                                        {categories.find(c => c.value === goal.category)?.label() || goal.category}
                                        {#if goal.deadline}
                                            &middot; {formatDate(goal.deadline)}
                                        {/if}
                                    </p>
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <Button variant="ghost" size="icon" class="size-7" onclick={() => openEditModal(goal)}><Pencil class="size-3.5" /></Button>
                                <Button variant="ghost" size="icon" class="size-7 text-destructive" onclick={() => confirmDelete(goal)}><Trash2 class="size-3.5" /></Button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="flex justify-between text-xs mb-1">
                                <span>{formatAmount(goal.current_amount)} / {formatAmount(goal.target_amount)}</span>
                                <span class="font-medium">{goal.progress}%</span>
                            </div>
                            <div class="h-2.5 w-full rounded-full bg-muted">
                                <div class="h-2.5 rounded-full transition-all {getProgressColor(goal.progress)}" style="width: {goal.progress}%"></div>
                            </div>
                        </div>

                        {#if goal.deadline}
                            {@const daysLeft = getDaysLeft(goal.deadline)}
                            {#if daysLeft !== null}
                                <div class="flex items-center gap-1.5 text-xs mb-2 {daysLeft < 0 ? 'text-destructive' : daysLeft < 31 ? 'text-accent-orange' : 'text-muted-foreground'}">
                                    <Calendar class="size-3" />
                                    {#if daysLeft < 0}
                                        {t('savings.past_due')}
                                    {:else if daysLeft === 0}
                                        {t('savings.due_today')}
                                    {:else}
                                        {daysLeft} {t('savings.days_left')}
                                    {/if}
                                </div>
                            {/if}
                        {/if}

                        {#if goal.auto_save_amount}
                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground mb-3">
                                <TrendingUp class="size-3" />
                                {formatAmount(goal.auto_save_amount)} {t('savings.auto_monthly')} &middot; {t('savings.day')} {goal.auto_save_day}
                            </div>
                        {/if}

                        <div class="border-t border-hairline pt-3">
                            {#if depositGoalId === goal.id}
                                <div class="flex gap-2 mb-2">
                                    <Input type="number" step="0.01" placeholder={t('savings.deposit_amount')} bind:value={depositAmount} class="flex-1 h-8 text-sm" />
                                    <Button size="sm" class="rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90 h-8 text-xs px-3 whitespace-nowrap" disabled={depositLoading || !parseFloat(String(depositAmount))} onclick={() => deposit(goal.id)}>
                                        {depositLoading ? '...' : t('savings.deposit')}
                                    </Button>
                                </div>
                            {:else}
                                <Button variant="outline" size="sm" class="rounded-full h-7 text-xs" onclick={() => { depositGoalId = goal.id; depositAmount = ''; }}>
                                    <Plus class="size-3 mr-1" /> {t('savings.deposit')}
                                </Button>
                            {/if}
                            {#if goal.recent_deposits && goal.recent_deposits.length > 0}
                                <div class="space-y-1 text-xs text-muted-foreground">
                                    {#each goal.recent_deposits as dep}
                                        <div class="flex justify-between">
                                            <span>{dep.created_at} {dep.note ? '&middot; ' + dep.note : ''}</span>
                                            <span class="font-medium text-brand-green-dark dark:text-brand-green">+{formatAmount(dep.amount)}</span>
                                        </div>
                                    {/each}
                                </div>
                            {/if}
                        </div>
                    </div>
                {/each}
            </div>
        {/if}
    </div>
</PullToRefresh>

<Dialog bind:open={showModal}>
    <DialogContent class="sm:max-w-md">
        <DialogHeader>
            <DialogTitle>{editingId ? t('savings.edit_goal') : t('savings.add_goal')}</DialogTitle>
        </DialogHeader>
        <form onsubmit={(e) => { e.preventDefault(); save(); }} class="space-y-4">
            <div>
                <Label for="goalName">{t('savings.goal_name')}</Label>
                <Input id="goalName" bind:value={formName} required />
            </div>
            <div>
                <Label for="goalTarget">{t('savings.target_amount')}</Label>
                <Input id="goalTarget" type="number" step="0.01" bind:value={formTarget} required />
            </div>
            <div>
                <Label for="goalCategory">{t('savings.category_label')}</Label>
                <select id="goalCategory" bind:value={formCategory} class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    {#each categories as cat}
                        <option value={cat.value}>{cat.label()}</option>
                    {/each}
                </select>
            </div>
            <div>
                <Label for="goalDeadline">{t('savings.deadline_label')}</Label>
                <Input id="goalDeadline" type="date" bind:value={formDeadline} />
            </div>
            <div>
                <Label>{t('savings.auto_save_label')}</Label>
                <div class="flex gap-2 mt-1">
                    <Input type="number" step="0.01" placeholder={t('savings.auto_amount')} bind:value={formAutoAmount} class="flex-1" />
                    <Input type="number" min="1" max="28" placeholder={t('savings.auto_day')} bind:value={formAutoDay} class="w-20" />
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" type="button" onclick={() => showModal = false} class="rounded-full">{t('common.cancel')}</Button>
                <Button type="submit" class="rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90" disabled={loading}>{t('common.save')}</Button>
            </DialogFooter>
        </form>
    </DialogContent>
</Dialog>

<ConfirmDialog
    bind:open={showDeleteConfirm}
    title={t('savings.delete_confirm')}
    description={t('savings.delete_desc')}
    confirmText={t('common.delete')}
    onConfirm={doDelete}
/>
