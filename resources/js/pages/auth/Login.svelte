<script module lang="ts">
    export const layout = {
        title: '',
        description: '',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasskeyVerify from '@/components/PasskeyVerify.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { register } from '@/routes';
    import { store } from '@/routes/login';
    import { request } from '@/routes/password';
    import { t } from '@/lib/locale.svelte';

    let {
        status = '',
        canResetPassword,
    }: {
        status?: string;
        canResetPassword: boolean;
    } = $props();

    $effect(() => {
        import('@/layouts/AuthLayout.svelte').then(m => {
            m.default;
        });
    });
</script>

<AppHead title={t('auth.login')} />

{#if status}
    <div class="mb-4 text-center text-sm font-medium text-brand-green-dark dark:text-brand-green">
        {status}
    </div>
{/if}

<PasskeyVerify />

<Form
    {...store.form()}
    resetOnSuccess={['password']}
    class="animate-fade-in-up flex flex-col gap-6"
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">{t('auth.email')}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <InputError message={errors.email} />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">{t('auth.password')}</Label>
                    {#if canResetPassword}
                        <TextLink href={request()} class="text-sm">
                            {t('auth.forgot_link')}
                        </TextLink>
                    {/if}
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder={t('auth.password')}
                />
                <InputError message={errors.password} />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3 space-x-reverse">
                    <Checkbox id="remember" name="remember" />
                    <span>{t('auth.remember_me')}</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90"
                disabled={processing}
                data-test="login-button"
            >
                {#if processing}<Spinner />{/if}
                {t('auth.login_button')}
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            {t('auth.no_account')}
            <TextLink href={register()}>{t('auth.signup_now')}</TextLink>
        </div>
    {/snippet}
</Form>
