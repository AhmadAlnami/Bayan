<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import LogOut from 'lucide-svelte/icons/log-out';
    import Settings from 'lucide-svelte/icons/settings';
    import FileText from 'lucide-svelte/icons/file-text';
    import PiggyBank from 'lucide-svelte/icons/piggy-bank';
    import {
        DropdownMenuGroup,
        DropdownMenuItem,
        DropdownMenuLabel,
        DropdownMenuSeparator,
    } from '@/components/ui/dropdown-menu';
    import UserInfo from '@/components/UserInfo.svelte';
    import { toUrl } from '@/lib/utils';
    import { logout } from '@/routes';
    import { edit } from '@/routes/profile';
    import type { User } from '@/types';

    let {
        user,
    }: {
        user: User;
    } = $props();

    function handleLogout(propsOnClick?: () => void) {
        return () => {
            propsOnClick?.();
            router.flushAll();
        };
    }
</script>

<DropdownMenuLabel class="p-0 font-normal">
    <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
        <UserInfo {user} showEmail={true} />
    </div>
</DropdownMenuLabel>
<DropdownMenuSeparator />
<DropdownMenuGroup>
    <DropdownMenuItem asChild>
        {#snippet children(props)}
            <Link
                class={props.class}
                href={toUrl(edit())}
                prefetch
                onclick={props.onClick}
            >
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        {/snippet}
    </DropdownMenuItem>
    <DropdownMenuItem asChild>
        {#snippet children(props)}
            <Link
                class={props.class}
                href="/reports"
                prefetch
                onclick={props.onClick}
            >
                <FileText class="mr-2 h-4 w-4" />
                Reports
            </Link>
        {/snippet}
    </DropdownMenuItem>
    <DropdownMenuItem asChild>
        {#snippet children(props)}
            <Link
                class={props.class}
                href="/savings"
                prefetch
                onclick={props.onClick}
            >
                <PiggyBank class="mr-2 h-4 w-4" />
                Savings
            </Link>
        {/snippet}
    </DropdownMenuItem>
</DropdownMenuGroup>
<DropdownMenuSeparator />
<DropdownMenuItem asChild>
    {#snippet children(props)}
        <Link
            class={props.class}
            href={logout()}
            as="button"
            onclick={handleLogout(props.onClick)}
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    {/snippet}
</DropdownMenuItem>
