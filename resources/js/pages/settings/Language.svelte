<script module lang="ts">
    import { edit as editLanguage } from '@/routes/language';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Language',
                href: editLanguage(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import LanguageTabs from '@/components/LanguageTabs.svelte';
    import { t } from '@/lib/locale.svelte';
    import type { Locale } from '@/lib/locale.svelte';

    let { locale = 'ar' }: { locale?: Locale } = $props();
</script>

<AppHead title={t('language.title')} />

<h1 class="sr-only">{t('language.title')}</h1>

<div class="animate-fade-in-up space-y-6">
    <Heading
        variant="small"
        title={t('language.title')}
        description={t('language.description')}
    />
    <LanguageTabs currentLocale={locale} />
</div>
