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
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { login } from '@/routes';
    import { email } from '@/routes/password';
    import { t } from '@/lib/locale.svelte';

    let {
        status = '',
    }: {
        status?: string;
    } = $props();
</script>

<AppHead title={t('auth.forgot_password')} />

{#if status}
    <div class="mb-4 text-center text-sm font-medium text-brand-green-dark dark:text-brand-green">
        {status}
    </div>
{/if}

<div class="animate-fade-in-up space-y-6">
    <Form {...email.form()}>
        {#snippet children({ errors, processing })}
            <div class="grid gap-2">
                <Label for="email">{t('auth.email')}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    placeholder="email@example.com"
                />
                <InputError message={errors.email} />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    type="submit"
                    class="w-full rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90"
                    disabled={processing}
                    data-test="email-password-reset-link-button"
                >
                    {#if processing}<Spinner />{/if}
                    {t('auth.send_reset_link')}
                </Button>
            </div>
        {/snippet}
    </Form>

    <div class="text-center text-sm text-muted-foreground">
        {t('auth.or')}
        <TextLink href={login()}>{t('auth.back_to_login')}</TextLink>
    </div>
</div>
