<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
    import ArrowUpRight from 'lucide-svelte/icons/arrow-up-right';
    import ArrowDownLeft from 'lucide-svelte/icons/arrow-down-left';
    import Sun from 'lucide-svelte/icons/sun';
    import Moon from 'lucide-svelte/icons/moon';
    import LogOut from 'lucide-svelte/icons/log-out';
    import Settings from 'lucide-svelte/icons/settings';
    import Banknote from 'lucide-svelte/icons/banknote';
    import AppLogo from '@/components/AppLogo.svelte';
    import { Avatar, AvatarFallback } from '@/components/ui/avatar';
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuSeparator,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { getInitials } from '@/lib/initials';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import { themeState } from '@/lib/theme.svelte';
    import { t } from '@/lib/locale.svelte';

    const { appearance, updateAppearance } = themeState();

    const auth = $derived(page.props.auth);
    const url = currentUrlState();

    const navItems = [
        { title: () => t('nav.dashboard'), href: toUrl(dashboard()), icon: LayoutGrid },
        { title: () => t('nav.expenses'), href: '/transactions/expenses', icon: ArrowUpRight },
        { title: () => t('nav.income'), href: '/transactions/income', icon: ArrowDownLeft },
    ];

    function visitSettings(page: string) {
        router.visit(page);
    }
</script>

<header class="sticky top-0 z-40 hidden border-b border-hairline bg-white md:flex dark:bg-brand-teal-deep dark:border-hairline-dark">
    <div class="flex h-14 w-full items-center justify-between px-4">
        <div class="flex items-center gap-6">
            <Link href={toUrl(dashboard())} class="flex items-center gap-2 font-medium">
                <AppLogo />
            </Link>
            <nav class="flex items-center gap-1">
                {#each navItems as item}
                    <Link
                        href={item.href}
                        class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium transition-colors {url.isCurrentOrParentUrl(item.href, url.currentUrl) ? 'bg-brand-green-soft text-brand-green-dark dark:bg-brand-green/10 dark:text-brand-green' : 'text-muted-foreground hover:bg-muted hover:text-ink dark:hover:text-on-dark'}"
                    >
                        <item.icon class="size-4" />
                        <span>{item.title()}</span>
                    </Link>
                {/each}
            </nav>
        </div>

        <div class="flex items-center gap-2">
            <Button variant="ghost" size="icon" class="rounded-full" onclick={() => updateAppearance(appearance.value === 'dark' ? 'light' : 'dark')}>
                <Sun class="size-4 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
                <Moon class="absolute size-4 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
            </Button>

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon" class="rounded-full">
                        <Avatar class="size-8">
                            <AvatarFallback class="bg-brand-green-soft text-brand-green-dark dark:bg-brand-green/10 dark:text-brand-green">{getInitials(auth.user?.name || 'U')}</AvatarFallback>
                        </Avatar>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-48">
                    <div class="px-2 py-1.5 text-sm font-medium truncate">{auth.user?.name}</div>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem onclick={() => visitSettings('/settings/profile')}>
                        <Settings class="size-4 mr-2" />
                        {t('nav.settings')}
                    </DropdownMenuItem>
                    <DropdownMenuItem onclick={() => visitSettings('/settings/salary')}>
                        <Banknote class="size-4 mr-2" />
                        {t('settings.salary')}
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem onclick={() => router.post('/logout')}>
                        <LogOut class="size-4 mr-2" />
                        {t('nav.logout')}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>
</header>

<header class="sticky top-0 z-40 flex border-b border-hairline bg-white md:hidden dark:bg-brand-teal-deep dark:border-hairline-dark">
    <div class="flex h-14 w-full items-center justify-between px-4">
        <Link href={toUrl(dashboard())} class="flex items-center gap-2 font-medium">
            <AppLogo />
        </Link>
        <div class="flex items-center gap-2">
            <Button variant="ghost" size="icon" class="rounded-full" onclick={() => updateAppearance(appearance.value === 'dark' ? 'light' : 'dark')}>
                <Sun class="size-4 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
                <Moon class="absolute size-4 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
            </Button>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon" class="rounded-full">
                        <Avatar class="size-8">
                            <AvatarFallback class="bg-brand-green-soft text-brand-green-dark dark:bg-brand-green/10 dark:text-brand-green">{getInitials(auth.user?.name || 'U')}</AvatarFallback>
                        </Avatar>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-48">
                    <div class="px-2 py-1.5 text-sm font-medium truncate">{auth.user?.name}</div>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem onclick={() => visitSettings('/settings/profile')}>
                        <Settings class="size-4 mr-2" />
                        {t('nav.settings')}
                    </DropdownMenuItem>
                    <DropdownMenuItem onclick={() => visitSettings('/settings/salary')}>
                        <Banknote class="size-4 mr-2" />
                        {t('settings.salary')}
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem onclick={() => router.post('/logout')}>
                        <LogOut class="size-4 mr-2" />
                        {t('nav.logout')}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>
</header>
