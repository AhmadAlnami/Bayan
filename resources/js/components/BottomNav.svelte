<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
    import ArrowUpRight from 'lucide-svelte/icons/arrow-up-right';
    import ArrowDownLeft from 'lucide-svelte/icons/arrow-down-left';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import { currentUrlState } from '@/lib/currentUrl.svelte';

    const url = currentUrlState();

    const tabs = [
        { title: 'الرئيسية', href: toUrl(dashboard()), icon: LayoutGrid },
        { title: 'المصروفات', href: '/transactions/expenses', icon: ArrowUpRight },
        { title: 'الدخل', href: '/transactions/income', icon: ArrowDownLeft },
    ];
</script>

<nav class="fixed bottom-0 left-0 right-0 z-50 border-t border-hairline bg-white md:hidden dark:bg-brand-teal-deep dark:border-hairline-dark">
    <div class="flex items-center justify-around h-16">
        {#each tabs as tab}
            <Link
                href={tab.href}
                class="flex flex-col items-center justify-center gap-1 px-3 py-2 text-xs font-medium transition-colors {url.isCurrentOrParentUrl(tab.href, url.currentUrl) ? 'text-brand-green-dark dark:text-brand-green' : 'text-muted-foreground'}"
            >
                <tab.icon class="size-5" />
                <span>{tab.title}</span>
            </Link>
        {/each}
    </div>
</nav>
