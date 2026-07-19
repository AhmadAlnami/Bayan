<script module lang="ts">
    import { edit } from '@/routes/profile';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Profile',
                href: edit(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import AppHead from '@/components/AppHead.svelte';
    import DeleteUser from '@/components/DeleteUser.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { send } from '@/routes/verification';
    import { t } from '@/lib/locale.svelte';

    const user = $derived(page.props.auth.user);
</script>

<AppHead title={t('profile.title')} />

<h1 class="sr-only">{t('profile.title')}</h1>

<div class="flex flex-col space-y-6">
    <Heading
        variant="small"
        title={t('profile.title')}
        description={t('profile.description')}
    />

    <Form
        {...ProfileController.update.form()}
        class="space-y-6"
        options={{ preserveScroll: true }}
    >
        {#snippet children({ errors, processing })}
            <div class="grid gap-2">
                <Label for="name">{t('auth.name')}</Label>
                <Input
                    id="name"
                    name="name"
                    class="mt-1 block w-full"
                    value={user.name}
                    required
                    autocomplete="name"
                    placeholder={t('auth.name_placeholder')}
                />
                <InputError class="mt-2" message={errors.name} />
            </div>

            <div class="grid gap-2">
                <Label for="email">{t('auth.email')}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    class="mt-1 block w-full"
                    value={user.email}
                    required
                    autocomplete="username"
                    placeholder="email@example.com"
                />
                <InputError class="mt-2" message={errors.email} />
            </div>

            {#if Boolean(page.props.mustVerifyEmail) && !user.email_verified_at}
                <div>
                    <p class="-mt-4 text-sm text-muted-foreground">
                        {t('profile.email_unverified')}
                        <TextLink href={send()} as="button">
                            {t('profile.click_resend')}
                        </TextLink>
                    </p>

                    {#if page.props.status === 'verification-link-sent'}
                        <div class="mt-2 text-sm font-medium text-brand-green-dark dark:text-brand-green">
                            {t('profile.verification_sent')}
                        </div>
                    {/if}
                </div>
            {/if}

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    class="rounded-full bg-brand-green text-brand-teal-deep hover:bg-brand-green/90"
                    disabled={processing}
                    data-test="update-profile-button">{t('profile.save')}</Button
                >
            </div>
        {/snippet}
    </Form>
</div>

<DeleteUser />
