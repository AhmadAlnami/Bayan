<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Salary', href: '/settings/salary' },
        ],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import SalaryController from '@/actions/App/Http/Controllers/Settings/SalaryController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { t } from '@/lib/locale.svelte';

    let { setting, incomeCategories = [] }: {
        setting: { amount: number; day_of_month: number; is_active: boolean };
        incomeCategories: { id: number; name: string }[];
    } = $props();
</script>

<AppHead title={t('salary.title')} />

<h1 class="sr-only">{t('salary.title')}</h1>

<div class="flex flex-col space-y-6">
    <Heading
        variant="small"
        title={t('salary.title')}
        description={t('salary.description')}
    />

    <Form
        {...SalaryController.update.form()}
        class="space-y-6"
        options={{ preserveScroll: true }}
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-4">
                <div class="flex items-center justify-between rounded-lg border p-4">
                    <div>
                        <Label for="is_active" class="text-base">{t('salary.auto')}</Label>
                        <p class="text-sm text-muted-foreground">{t('salary.auto_desc')}</p>
                    </div>
                    <input type="hidden" name="is_active" value="0" />
                    <input
                        type="checkbox"
                        id="is_active"
                        name="is_active"
                        value="1"
                        class="h-5 w-5 rounded border-gray-300"
                        checked={setting.is_active}
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="amount">{t('salary.amount')}</Label>
                    <Input
                        id="amount"
                        name="amount"
                        type="number"
                        step="0.01"
                        min="0"
                        value={setting.amount}
                        placeholder={t('salary.amount_placeholder')}
                        required
                    />
                    <InputError message={errors.amount} />
                </div>

                <div class="grid gap-2">
                    <Label for="day_of_month">{t('salary.day')}</Label>
                    <Input
                        id="day_of_month"
                        name="day_of_month"
                        type="number"
                        min="1"
                        max="28"
                        value={setting.day_of_month}
                        required
                    />
                    <p class="text-xs text-muted-foreground">{t('salary.day_hint')}</p>
                    <InputError message={errors.day_of_month} />
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" class="rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90" disabled={processing}>{t('salary.save')}</Button>
                </div>
            </div>
        {/snippet}
    </Form>
</div>
