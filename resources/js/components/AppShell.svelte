<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import { SidebarProvider } from '@/components/ui/sidebar';
    import { localeState } from '@/lib/locale.svelte';
    import type { AppVariant } from '@/types';

    let {
        variant = 'sidebar',
        class: className = '',
        children,
    }: {
        variant?: AppVariant;
        class?: string;
        children?: Snippet;
    } = $props();

    const isOpen = $derived(page.props.sidebarOpen);
    const { locale } = localeState();
    const dir = $derived(locale.value === 'ar' ? 'rtl' : 'ltr');
</script>

{#if variant === 'header'}
    <div dir={dir} class="flex min-h-screen w-full flex-col {className}">
        {@render children?.()}
    </div>
{:else}
    <SidebarProvider defaultOpen={isOpen}>
        <div dir={dir} class="flex min-h-screen w-full">
            {@render children?.()}
        </div>
    </SidebarProvider>
{/if}
