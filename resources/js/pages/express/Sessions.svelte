<script module lang="ts">
    import { index } from '@/routes/express-quizzes';

    export const layout = {
        breadcrumbs: [{ title: 'Quiz express', href: index() }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChevronRight from '@lucide/svelte/icons/chevron-right';
    import Gamepad2 from '@lucide/svelte/icons/gamepad-2';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/design/EmptyState.svelte';
    import PageHeader from '@/components/design/PageHeader.svelte';
    import { formatDate } from '@/lib/dates';
    import type { ExpressStatus } from '@/lib/express';
    import { toUrl } from '@/lib/utils';
    import { results, show } from '@/routes/express-sessions';

    let {
        quiz,
        sessions,
    }: {
        quiz: { id: number; title: string };
        sessions: {
            id: number;
            code: string;
            status: ExpressStatus;
            questions: number;
            participants: number;
            created_at: string;
        }[];
    } = $props();

    const statusLabel: Record<ExpressStatus, string> = {
        lobby: 'Salle d’attente',
        question: 'En cours',
        ranking: 'Classement',
        correction: 'Correction',
        closed: 'Terminée',
    };
</script>

<AppHead title={`Sessions · ${quiz.title}`} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4 sm:p-6">
    <PageHeader
        eyebrow="Quiz express"
        title={quiz.title}
        subtitle="Sessions passées : classement et correction de chacune."
    />

    {#if sessions.length === 0}
        <EmptyState
            icon={Gamepad2}
            title="Pas encore de session"
            description="Lance le quiz depuis la liste des quiz express."
        />
    {:else}
        <ul class="flex flex-col gap-2">
            {#each sessions as session (session.id)}
                <li>
                    <Link
                        href={toUrl(
                            session.status === 'closed'
                                ? results(session.id)
                                : show(session.id),
                        )}
                        class="flex min-h-16 items-center gap-3 surface p-3 transition-colors hover:bg-muted/50"
                    >
                        <span class="min-w-0 flex-1">
                            <span class="block font-medium"
                                >{formatDate(session.created_at)}</span
                            >
                            <span class="block text-sm text-muted-foreground">
                                {session.participants} participant{session.participants >
                                1
                                    ? 's'
                                    : ''} · {session.questions} question{session.questions >
                                1
                                    ? 's'
                                    : ''} · code {session.code}
                            </span>
                        </span>
                        <span class="eyebrow">{statusLabel[session.status]}</span>
                        <ChevronRight
                            class="size-5 shrink-0 text-muted-foreground"
                        />
                    </Link>
                </li>
            {/each}
        </ul>
    {/if}

    <Link href={toUrl(index())} class="mono-link text-muted-foreground"
        >← Tous les quiz express</Link
    >
</div>
