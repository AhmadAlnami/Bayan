<script lang="ts">
import { Link } from '@inertiajs/svelte';
import ArrowDownLeft from 'lucide-svelte/icons/arrow-down-left';
import ArrowUpRight from 'lucide-svelte/icons/arrow-up-right';
import BarChart3 from 'lucide-svelte/icons/bar-chart-3';
import LayoutGrid from 'lucide-svelte/icons/layout-grid';
import MessageSquare from 'lucide-svelte/icons/message-square';
import Target from 'lucide-svelte/icons/target';
import { currentUrlState } from '@/lib/currentUrl.svelte';
import { t } from '@/lib/locale.svelte';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';

    const url = currentUrlState();

    const tabs = [
        { title: () => t('nav.dashboard'), href: toUrl(dashboard()), icon: LayoutGrid },
        { title: () => t('insights.title'), href: '/insights', icon: BarChart3 },
        { title: () => t('budgets.title'), href: '/budgets', icon: Target },
        { title: () => t('nav.expenses'), href: '/transactions/expenses', icon: ArrowUpRight },
        { title: () => t('nav.income'), href: '/transactions/income', icon: ArrowDownLeft },
        { title: () => t('assistant.title'), href: '/assistant', icon: MessageSquare },
    ];
</script>

<nav class="fixed bottom-0 left-0 right-0 z-50 border-t border-hairline bg-white md:hidden dark:bg-brand-teal-deep dark:border-hairline-dark">
    <div class="grid h-16 grid-cols-6">
        {#each tabs as tab (tab.href)}
            <Link
                href={tab.href}
                class="flex min-w-0 flex-col items-center justify-center gap-1 py-1.5 px-0 text-[11px] font-medium transition-colors {url.isCurrentOrParentUrl(tab.href, url.currentUrl) ? 'text-brand-green-dark dark:text-brand-green' : 'text-muted-foreground'}"
            >
                <tab.icon class="size-5 shrink-0" />
                <span class="max-w-full truncate px-0.5 text-center leading-tight">{tab.title()}</span>
            </Link>
        {/each}
    </div>
</nav>
