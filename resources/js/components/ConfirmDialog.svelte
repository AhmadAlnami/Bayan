<script lang="ts">
    import { Button } from '@/components/ui/button';
    import {
        AlertDialog,
        AlertDialogAction,
        AlertDialogCancel,
        AlertDialogContent,
        AlertDialogDescription,
        AlertDialogFooter,
        AlertDialogHeader,
        AlertDialogTitle,
    } from '@/components/ui/alert-dialog';
    import { t } from '@/lib/locale.svelte';

    let {
        open = false,
        title = '',
        description = '',
        confirmText = '',
        cancelText = '',
        onConfirm,
        onOpenChange,
    }: {
        open: boolean;
        title?: string;
        description?: string;
        confirmText?: string;
        cancelText?: string;
        onConfirm: () => void;
        onOpenChange: (v: boolean) => void;
    } = $props();
</script>

<AlertDialog open={open} onOpenChange={onOpenChange}>
    <AlertDialogContent>
        <AlertDialogHeader>
            <AlertDialogTitle>{title || t('common.confirm')}</AlertDialogTitle>
            {#if description}
                <AlertDialogDescription>{description}</AlertDialogDescription>
            {/if}
        </AlertDialogHeader>
        <AlertDialogFooter>
            <AlertDialogCancel onclick={() => onOpenChange(false)}>
                {cancelText || t('common.cancel')}
            </AlertDialogCancel>
            <AlertDialogAction onclick={onConfirm} class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                {confirmText || t('common.delete')}
            </AlertDialogAction>
        </AlertDialogFooter>
    </AlertDialogContent>
</AlertDialog>
