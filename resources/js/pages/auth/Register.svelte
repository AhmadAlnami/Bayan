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
    import PasswordInput from '@/components/PasswordInput.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { login } from '@/routes';
    import { store } from '@/routes/register';
    import { t } from '@/lib/locale.svelte';

    let { passwordRules }: { passwordRules: string } = $props();
</script>

<AppHead title={t('auth.register')} />

<Form
    {...store.form()}
    resetOnSuccess={['password', 'password_confirmation']}
    class="animate-fade-in-up flex flex-col gap-6"
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">{t('auth.name')}</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autocomplete="name"
                    name="name"
                    placeholder={t('auth.name_placeholder')}
                />
                <InputError message={errors.name} />
            </div>

            <div class="grid gap-2">
                <Label for="email">{t('auth.email')}</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError message={errors.email} />
            </div>

            <div class="grid gap-2">
                <Label for="password">{t('auth.password')}</Label>
                <PasswordInput
                    id="password"
                    required
                    autocomplete="new-password"
                    name="password"
                    placeholder={t('auth.password')}
                    passwordrules={passwordRules}
                />
                <InputError message={errors.password} />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">{t('auth.confirm_password_field')}</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder={t('auth.confirm_password_field')}
                    passwordrules={passwordRules}
                />
                <InputError message={errors.password_confirmation} />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90"
                disabled={processing}
                data-test="register-user-button"
            >
                {#if processing}<Spinner />{/if}
                {t('auth.register_button')}
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            {t('auth.has_account')}
            <TextLink href={login()} class="underline underline-offset-4">
                {t('auth.login_now')}
            </TextLink>
        </div>
    {/snippet}
</Form>
