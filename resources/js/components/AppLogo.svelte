<script lang="ts">
    import Wallet from 'lucide-svelte/icons/wallet';
    import { t } from '@/lib/locale.svelte';
</script>

<div
    class="flex aspect-square size-9 items-center justify-center rounded-xl bg-brand-green"
>
    <Wallet class="size-5 text-brand-teal-deep" />
</div>
<div class="ms-1 grid flex-1 text-left text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold">{t('app.name')}</span>
</div>
