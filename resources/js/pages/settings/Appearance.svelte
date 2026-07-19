<script module lang="ts">
    import { edit as editAppearance } from '@/routes/appearance';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Appearance',
                href: editAppearance(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppearanceTabs from '@/components/AppearanceTabs.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { t } from '@/lib/locale.svelte';
</script>

<AppHead title={t('appearance.title')} />

<h1 class="sr-only">{t('appearance.title')}</h1>

<div class="animate-fade-in-up space-y-6">
    <Heading
        variant="small"
        title={t('appearance.title')}
        description={t('appearance.description')}
    />
    <AppearanceTabs />
</div>
