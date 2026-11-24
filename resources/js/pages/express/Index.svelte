<script module lang="ts">
    import { index } from '@/routes/express-quizzes';

    export const layout = {
        breadcrumbs: [{ title: 'Quiz express', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import Copy from '@lucide/svelte/icons/copy';
    import Pencil from '@lucide/svelte/icons/pencil';
    import Plus from '@lucide/svelte/icons/plus';
    import ExpressQuizController from '@/actions/App/Http/Controllers/ExpressQuizController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import { Spinner } from '@/components/ui/spinner';
    import { toUrl } from '@/lib/utils';
    import { edit } from '@/routes/express-quizzes';

    let {
        quizzes,
    }: {
        quizzes: {
            id: number;
            title: string;
            seconds_per_question: number;
            questions_count: number;
        }[];
    } = $props();
</script>

<AppHead title="Quiz express" />

<div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <Heading
            title="Quiz express"
            description="Des quiz à jouer en salle : tu projettes un QR code, les participants répondent sur leur téléphone, sans compte."
        />

        <Form
            {...ExpressQuizController.store.form()}
            class="w-full sm:w-auto"
        >
            {#snippet children({ processing })}
                <Button
                    type="submit"
                    disabled={processing}
                    class="h-11 w-full sm:h-9 sm:w-auto"
                >
                    {#if processing}<Spinner />{:else}<Plus
                            class="size-4"
                        />{/if}
                    Nouveau quiz express
                </Button>
            {/snippet}
        </Form>
    </div>

    {#if quizzes.length === 0}
        <p
            class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Aucun quiz express. Crée-en un, puis ajoute ses questions à la
            main ou en collant un JSON.
        </p>
    {:else}
        <ul class="grid gap-3">
            {#each quizzes as quiz (quiz.id)}
                <li
                    class="flex flex-wrap items-center gap-3 surface p-4 sm:flex-nowrap"
                >
                    <div class="min-w-0 flex-1">
                        <p class="font-medium break-words">{quiz.title}</p>
                        <p class="text-sm text-muted-foreground">
                            {quiz.questions_count} question{quiz.questions_count >
                            1
                                ? 's'
                                : ''} · {quiz.seconds_per_question} s par question
                        </p>
                    </div>
                    <div class="flex w-full gap-2 sm:w-auto">
                        <Button
                            asChild
                            variant="outline"
                            class="h-11 flex-1 sm:h-9 sm:flex-none"
                        >
                            {#snippet children(props)}
                                <Link {...props} href={toUrl(edit(quiz.id))}>
                                    <Pencil class="size-4" />
                                    Modifier
                                </Link>
                            {/snippet}
                        </Button>
                        <Form
                            {...ExpressQuizController.duplicate.form(quiz.id)}
                            class="flex-1 sm:flex-none"
                        >
                            {#snippet children({ processing })}
                                <Button
                                    type="submit"
                                    variant="ghost"
                                    disabled={processing}
                                    class="h-11 w-full text-muted-foreground sm:h-9"
                                >
                                    <Copy class="size-4" />
                                    Dupliquer
                                </Button>
                            {/snippet}
                        </Form>
                    </div>
                </li>
            {/each}
        </ul>
    {/if}
</div>
