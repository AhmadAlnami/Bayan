<script lang="ts">
    import type { Snippet } from 'svelte';
    import AppShell from '@/components/AppShell.svelte';
    import TopNav from '@/components/TopNav.svelte';
    import BottomNav from '@/components/BottomNav.svelte';
    import { Toaster } from '@/components/ui/sonner';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        children?: Snippet;
    } = $props();
</script>

<AppShell variant="header">
    <TopNav {breadcrumbs} />
    <main class="flex-1 pb-16 md:pb-0">
        {@render children?.()}
    </main>
    <BottomNav />
    <Toaster />
</AppShell>
