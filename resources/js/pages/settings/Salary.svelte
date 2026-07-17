<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Salary settings', href: '/settings/salary' },
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
    import { Switch } from '@/components/ui/switch';

    let { setting, incomeCategories = [] }: {
        setting: { amount: number; day_of_month: number; is_active: boolean };
        incomeCategories: { id: number; name: string }[];
    } = $props();
</script>

<AppHead title="Salary settings" />

<h1 class="sr-only">Salary settings</h1>

<div class="flex flex-col space-y-6">
    <Heading
        variant="small"
        title="إعدادات الراتب"
        description="حدد مبلغ الراتب وتاريخ نزوله الشهري"
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
                        <Label for="is_active" class="text-base">تفعيل الراتب التلقائي</Label>
                        <p class="text-sm text-muted-foreground">يتم إضافة مبلغ الراتب تلقائياً كل شهر</p>
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
                    <Label for="amount">مبلغ الراتب (ر.س)</Label>
                    <Input
                        id="amount"
                        name="amount"
                        type="number"
                        step="0.01"
                        min="0"
                        value={setting.amount}
                        placeholder="مثال: 10000"
                        required
                    />
                    <InputError message={errors.amount} />
                </div>

                <div class="grid gap-2">
                    <Label for="day_of_month">يوم نزول الراتب (1-28)</Label>
                    <Input
                        id="day_of_month"
                        name="day_of_month"
                        type="number"
                        min="1"
                        max="28"
                        value={setting.day_of_month}
                        required
                    />
                    <p class="text-xs text-muted-foreground">إذا وافق يوم جمعة ينزل الخميس، وإذا وافق سبت ينزل الأحد</p>
                    <InputError message={errors.day_of_month} />
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" disabled={processing}>حفظ الإعدادات</Button>
                </div>
            </div>
        {/snippet}
    </Form>
</div>
