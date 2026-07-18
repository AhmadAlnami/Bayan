<script module lang="ts">
    export const layout = {
        title: 'تأكيد البريد الإلكتروني',
        description: 'الرجاء تأكيد بريدك الإلكتروني بالضغط على الرابط اللي أرسلناه لك.',
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

    let {
        status = '',
    }: {
        status?: string;
    } = $props();
</script>

<AppHead title="تأكيد البريد الإلكتروني" />

{#if status === 'verification-link-sent'}
    <div class="mb-4 text-center text-sm font-medium text-brand-green-dark dark:text-brand-green">
        تم إرسال رابط تأكيد جديد إلى بريدك الإلكتروني.
    </div>
{/if}

<Form {...send.form()} class="space-y-6 text-center">
    {#snippet children({ processing })}
        <Button type="submit" disabled={processing} variant="secondary">
            {#if processing}<Spinner />{/if}
            إعادة إرسال بريد التأكيد
        </Button>

        <TextLink href={logout()} as="button" class="mx-auto block text-sm">
            تسجيل الخروج
        </TextLink>
    {/snippet}
</Form>
