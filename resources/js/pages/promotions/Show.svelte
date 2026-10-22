<script module lang="ts">
    import { index } from '@/routes/promotions';

    export const layout = {
        breadcrumbs: [{ title: 'Promotions', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link, page } from '@inertiajs/svelte';
    import BookCheck from '@lucide/svelte/icons/book-check';
    import FileUp from '@lucide/svelte/icons/file-up';
    import Mail from '@lucide/svelte/icons/mail';
    import Pencil from '@lucide/svelte/icons/pencil';
    import UserMinus from '@lucide/svelte/icons/user-minus';
    import UserPlus from '@lucide/svelte/icons/user-plus';
    import LearnerInvitationController from '@/actions/App/Http/Controllers/LearnerInvitationController';
    import PromotionLearnerController from '@/actions/App/Http/Controllers/PromotionLearnerController';
    import AppHead from '@/components/AppHead.svelte';
    import AddLearnerForm from '@/components/learners/AddLearnerForm.svelte';
    import ImportLearnersForm from '@/components/learners/ImportLearnersForm.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { formatDate } from '@/lib/dates';
    import { toUrl } from '@/lib/utils';
    import * as frameworks from '@/routes/frameworks';
    import { edit } from '@/routes/promotions';
    import type { ImportReport, Learner } from '@/types/learner';
    import type { Promotion } from '@/types/promotion';

    let {
        promotion,
        framework,
        learners,
    }: {
        promotion: Promotion;
        framework: {
            id: number;
            name: string;
            competencies_count: number;
        } | null;
        learners: Learner[];
    } = $props();

    const report = $derived(
        (page.flash as { import?: ImportReport } | undefined)?.import,
    );
    const invitationError = $derived(page.props.errors?.invitation);
    const activatedCount = $derived(
        learners.filter((learner) => learner.activated_at).length,
    );
</script>

<AppHead title={promotion.name} />

<div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold tracking-tight">
                {promotion.name}
                {#if promotion.archived_at}
                    <Badge variant="secondary" class="ml-2 align-middle">Archivée</Badge>
                {/if}
            </h1>
            <p class="text-sm text-muted-foreground">
                Du {formatDate(promotion.starts_on)} au {formatDate(
                    promotion.ends_on,
                )} · {learners.length} apprenant{learners.length > 1 ? 's' : ''}
                ({activatedCount} activé{activatedCount > 1 ? 's' : ''})
            </p>
        </div>

        <Button variant="outline" size="sm" asChild class="h-11 sm:h-9">
            {#snippet children(props)}
                <Link {...props} href={toUrl(edit(promotion.id))}>
                    <Pencil class="size-4" />
                    Modifier
                </Link>
            {/snippet}
        </Button>
    </header>

    <section
        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-4"
        aria-label="Référentiel"
    >
        <div class="flex min-w-0 items-center gap-3">
            <BookCheck class="size-5 shrink-0 text-muted-foreground" />
            {#if framework}
                <div class="min-w-0">
                    <Link
                        href={toUrl(frameworks.show(framework.id))}
                        class="block truncate font-medium underline-offset-4 hover:underline"
                    >
                        {framework.name}
                    </Link>
                    <p class="text-sm text-muted-foreground">
                        {framework.competencies_count} compétence{framework.competencies_count >
                        1
                            ? 's'
                            : ''}
                    </p>
                </div>
            {:else}
                <p class="text-sm text-muted-foreground">
                    Aucun référentiel affecté à cette promotion.
                </p>
            {/if}
        </div>
        {#if !framework}
            <Button variant="outline" size="sm" asChild class="h-11 sm:h-9">
                {#snippet children(props)}
                    <Link {...props} href={toUrl(edit(promotion.id))}>
                        Choisir un référentiel
                    </Link>
                {/snippet}
            </Button>
        {/if}
    </section>

    {#if !promotion.archived_at}
        <section class="grid gap-3 sm:grid-cols-2">
            <details class="group rounded-xl border p-4 open:sm:col-span-2">
                <summary
                    class="flex min-h-11 cursor-pointer list-none items-center gap-2 font-medium"
                >
                    <UserPlus class="size-4" />
                    Ajouter un apprenant
                </summary>
                <div class="mt-4">
                    <AddLearnerForm promotionId={promotion.id} />
                </div>
            </details>

            <details class="group rounded-xl border p-4 open:sm:col-span-2">
                <summary
                    class="flex min-h-11 cursor-pointer list-none items-center gap-2 font-medium"
                >
                    <FileUp class="size-4" />
                    Importer un fichier CSV
                </summary>
                <div class="mt-4">
                    <ImportLearnersForm promotionId={promotion.id} />
                </div>
            </details>
        </section>
    {/if}

    {#if report}
        <section
            class="rounded-xl border p-4 text-sm"
            aria-live="polite"
            aria-label="Résultat de l'import"
        >
            <p class="font-medium">
                Import terminé : {report.created} créé{report.created > 1
                    ? 's'
                    : ''}, {report.enrolled} déjà inscrit{report.enrolled > 1
                    ? 's'
                    : ''} ailleurs ajouté{report.enrolled > 1 ? 's' : ''},
                {report.errors.length} erreur{report.errors.length > 1
                    ? 's'
                    : ''}.
            </p>
            {#if report.errors.length > 0}
                <ul class="mt-2 grid gap-1 text-destructive">
                    {#each report.errors as error (error.line)}
                        <li>Ligne {error.line} : {error.message}</li>
                    {/each}
                </ul>
            {/if}
        </section>
    {/if}

    {#if invitationError}
        <p class="rounded-xl border border-destructive p-4 text-sm text-destructive">
            {invitationError}
        </p>
    {/if}

    {#if learners.length === 0}
        <p
            class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Aucun apprenant dans cette promotion pour l'instant.
        </p>
    {:else}
        <ul class="grid gap-3">
            {#each learners as learner (learner.id)}
                <li
                    class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2 font-medium">
                            <span class="truncate">{learner.name}</span>
                            {#if learner.activated_at}
                                <Badge variant="secondary">Activé</Badge>
                            {:else}
                                <Badge variant="outline">
                                    Invité{learner.invited_at
                                        ? ` le ${formatDate(learner.invited_at)}`
                                        : ''}
                                </Badge>
                            {/if}
                        </p>
                        <p class="truncate text-sm text-muted-foreground">
                            {learner.email}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        {#if !learner.activated_at}
                            <Form
                                {...LearnerInvitationController.store.form(learner.id)}
                                options={{ preserveScroll: true }}
                                class="flex flex-1 sm:flex-none"
                            >
                                {#snippet children({ processing })}
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        size="sm"
                                        disabled={processing}
                                        class="h-11 w-full sm:h-9"
                                    >
                                        <Mail class="size-4" />
                                        Renvoyer l'invitation
                                    </Button>
                                {/snippet}
                            </Form>
                        {/if}

                        <Form
                            {...PromotionLearnerController.destroy.form([
                                promotion.id,
                                learner.id,
                            ])}
                            options={{ preserveScroll: true }}
                            onBefore={() =>
                                confirm(
                                    `Retirer ${learner.name} de la promotion ?`,
                                )}
                            class="flex flex-1 sm:flex-none"
                        >
                            {#snippet children({ processing })}
                                <Button
                                    type="submit"
                                    variant="ghost"
                                    size="sm"
                                    disabled={processing}
                                    class="h-11 w-full sm:h-9"
                                >
                                    <UserMinus class="size-4" />
                                    Retirer
                                </Button>
                            {/snippet}
                        </Form>
                    </div>
                </li>
            {/each}
        </ul>
    {/if}
</div>
