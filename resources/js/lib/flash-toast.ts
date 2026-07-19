import { router, usePage } from '@inertiajs/svelte';
import { toast } from 'svelte-sonner';
import type { FlashToast } from '@/types/ui';

let toastCounter = 0;

export function initializeFlashToast(): void {
    router.on('finish', (event) => {
        setTimeout(() => {
            const page = usePage();
            const toastData = page.props.toast as FlashToast | null;
            if (!toastData || !toastData.message) return;

            const id = `toast-${++toastCounter}-${Date.now()}`;
            toast[toastData.type](toastData.message, { id });
        }, 200);
    });
}
