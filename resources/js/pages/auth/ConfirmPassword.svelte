<script module lang="ts">
    export const layout = {
        title: 'تأكيد كلمة المرور',
        description: 'هذه منطقة آمنة من التطبيق. الرجاء تأكيد كلمة المرور قبل المتابعة.',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import {
        index as confirmOptions,
        store as confirmStore,
    } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasskeyVerify from '@/components/PasskeyVerify.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { store } from '@/routes/password/confirm';
</script>

<AppHead title="تأكيد كلمة المرور" />

<PasskeyVerify
    routes={{
        options: confirmOptions(),
        submit: confirmStore(),
    }}
    label="التأكيد بمفتاح المرور"
    loadingLabel="جاري التأكيد..."
    separator="أو أكد بكلمة المرور"
/>

<Form {...store.form()} resetOnSuccess>
    {#snippet children({ errors, processing })}
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label for="password">كلمة المرور</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                />
                <InputError message={errors.password} />
            </div>

            <div class="flex items-center">
                <Button
                    type="submit"
                    class="w-full rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90"
                    disabled={processing}
                    data-test="confirm-password-button"
                >
                    {#if processing}<Spinner />{/if}
                    تأكيد كلمة المرور
                </Button>
            </div>
        </div>
    {/snippet}
</Form>
