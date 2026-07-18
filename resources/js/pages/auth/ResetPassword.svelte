<script module lang="ts">
    export const layout = {
        title: 'إعادة تعيين كلمة المرور',
        description: 'أدخل كلمة المرور الجديدة أدناه',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { update } from '@/routes/password';

    let {
        token,
        email,
        passwordRules,
    }: {
        token: string;
        email: string;
        passwordRules: string;
    } = $props();
</script>

<AppHead title="إعادة تعيين كلمة المرور" />

<Form
    {...update.form()}
    transform={(data) => ({ ...data, token, email })}
    resetOnSuccess={['password', 'password_confirmation']}
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">البريد الإلكتروني</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    value={email}
                    class="mt-1 block w-full"
                    readonly
                />
                <InputError message={errors.email} class="mt-2" />
            </div>

            <div class="grid gap-2">
                <Label for="password">كلمة المرور الجديدة</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    placeholder="كلمة المرور الجديدة"
                    passwordrules={passwordRules}
                />
                <InputError message={errors.password} />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">تأكيد كلمة المرور</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    placeholder="تأكيد كلمة المرور"
                    passwordrules={passwordRules}
                />
                <InputError message={errors.password_confirmation} />
            </div>

            <Button
                type="submit"
                class="mt-4 w-full rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90"
                disabled={processing}
                data-test="reset-password-button"
            >
                {#if processing}<Spinner />{/if}
                إعادة تعيين كلمة المرور
            </Button>
        </div>
    {/snippet}
</Form>
