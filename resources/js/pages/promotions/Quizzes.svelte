<script module lang="ts">
    import { index } from '@/routes/promotions';

    export const layout = {
        breadcrumbs: [{ title: 'Promotions', href: index() }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import CircleHelp from '@lucide/svelte/icons/circle-help';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/design/EmptyState.svelte';
    import PageHeader from '@/components/design/PageHeader.svelte';
    import { toUrl } from '@/lib/utils';
    import { show } from '@/routes/promotions/quizzes';

    let {
        promotion,
        quizzes,
    }: {
        promotion: { id: number; name: string };
        quizzes: {
            sequence_id: number;
            sequence_title: string;
            title: string;
            questions: number;
            learners: number;
            participants: number;
            attempts: number;
            average_percent: number | null;
        }[];
    } = $props();
</script>

<AppHead title={`Quiz · ${promotion.name}`} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 sm:p-6">
    <PageHeader
        eyebrow="Promotion"
        title="Quiz"
        subtitle={`${promotion.name} · quiz des séquences ouvertes`}
    />

    {#if quizzes.length === 0}
        <EmptyState
            icon={CircleHelp}
            title="Aucun quiz pour l'instant"
            description="Ajoute un quiz à une séquence, puis ouvre-la à la promotion."
        />
    {:else}
        <ul class="flex flex-col gap-2">
            {#each quizzes as quiz (quiz.sequence_id)}
                <li>
                    <Link
                        href={toUrl(show([promotion.id, quiz.sequence_id]))}
                        class="flex min-h-16 items-center gap-3 surface p-3 transition-colors hover:bg-muted/50"
                    >
                        <span class="min-w-0 flex-1">
                            <span class="block eyebrow"
                                >{quiz.sequence_title}</span
                            >
                            <span class="block font-medium">{quiz.title}</span>
                            <span class="block text-sm text-muted-foreground">
                                {quiz.participants} / {quiz.learners} apprenant{quiz.learners >
                                1
                                    ? 's'
                                    : ''} · {quiz.attempts} tentative{quiz.attempts >
                                1
                                    ? 's'
                                    : ''}
                            </span>
                        </span>
                        <span class="shrink-0 text-right">
                            <span class="block figures text-xl font-bold"
                                >{quiz.average_percent === null
                                    ? '—'
                                    : `${quiz.average_percent} %`}</span
                            >
                            <span class="block text-xs text-muted-foreground"
                                >moyenne</span
                            >
                        </span>
                        <ChevronRight
                            class="size-5 shrink-0 text-muted-foreground"
                        />
                    </Link>
                </li>
            {/each}
        </ul>
        <p class="text-xs text-muted-foreground">
            Moyenne : dernière tentative de chaque apprenant qui a fait le
            quiz.
        </p>
    {/if}
</div>
