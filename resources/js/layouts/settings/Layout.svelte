<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import Heading from '@/components/Heading.svelte';
    import { Separator } from '@/components/ui/separator';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { toUrl } from '@/lib/utils';
    import { edit as editAppearance } from '@/routes/appearance';
    import { edit as editLanguage } from '@/routes/language';
    import { edit as editProfile } from '@/routes/profile';
    import { edit as editSecurity } from '@/routes/security';
    import { t } from '@/lib/locale.svelte';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const sidebarNavItems = [
        {
            title: () => t('settings.profile'),
            href: editProfile(),
        },
        {
            title: () => t('settings.security'),
            href: editSecurity(),
        },
        {
            title: () => t('settings.appearance'),
            href: editAppearance(),
        },
        {
            title: () => t('settings.language'),
            href: editLanguage(),
        },
        {
            title: () => t('settings.salary'),
            href: '/settings/salary',
        },
        {
            title: () => t('savings.title'),
            href: '/savings',
        },
        {
            title: () => t('reports.title'),
            href: '/reports',
        },
    ];

    const url = currentUrlState();
</script>

<div class="mx-auto w-full max-w-4xl px-3 py-6 sm:px-6 min-w-0">
    <Heading
        title={t('settings.title')}
        description={t('settings.description')}
    />

    <div class="flex flex-col lg:flex-row lg:space-x-12 lg:space-x-reverse">
        <aside class="w-full max-w-xl lg:w-48">
            <nav
                class="flex flex-col space-y-1"
                aria-label={t('settings.title')}
            >
                {#each sidebarNavItems as item (toUrl(item.href))}
                    <Link
                        href={toUrl(item.href)}
                        class="inline-flex w-full items-center justify-start rounded-full px-3 py-2 text-sm font-medium transition-colors hover:bg-muted {url.isCurrentUrl(
                            item.href,
                            url.currentUrl,
                        )
                            ? 'bg-brand-green-soft text-brand-green-dark dark:bg-brand-green/10 dark:text-brand-green'
                            : 'text-muted-foreground hover:text-ink dark:hover:text-on-dark'}"
                    >
                        {item.title()}
                    </Link>
                {/each}
            </nav>
        </aside>

        <Separator class="my-6 lg:hidden" />

        <div class="flex-1 md:max-w-2xl">
            <section class="max-w-xl space-y-12">
                {@render children?.()}
            </section>
        </div>
    </div>
</div>
