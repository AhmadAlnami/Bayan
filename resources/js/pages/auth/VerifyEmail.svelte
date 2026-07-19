<script module lang="ts">
    export const layout = {
        title: '',
        description: '',
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { logout } from '@/routes';
    import { send } from '@/routes/verification';
    import { t } from '@/lib/locale.svelte';

    let {
        status = '',
    }: {
        status?: string;
    } = $props();
</script>

<AppHead title={t('auth.verify_email')} />

{#if status === 'verification-link-sent'}
    <div class="mb-4 text-center text-sm font-medium text-brand-green-dark dark:text-brand-green">
        {t('auth.verification_sent')}
    </div>
{/if}

<Form {...send.form()} class="space-y-6 text-center">
    {#snippet children({ processing })}
        <Button type="submit" disabled={processing} variant="secondary">
            {#if processing}<Spinner />{/if}
            {t('auth.resend_verification')}
        </Button>

        <TextLink href={logout()} as="button" class="mx-auto block text-sm">
            {t('nav.logout')}
        </TextLink>
    {/snippet}
</Form>
