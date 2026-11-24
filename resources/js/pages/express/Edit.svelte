<script module lang="ts">
    import { index } from '@/routes/express-quizzes';

    export const layout = {
        breadcrumbs: [{ title: 'Quiz express', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link, router } from '@inertiajs/svelte';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import { toast } from 'svelte-sonner';
    import ExpressQuizController from '@/actions/App/Http/Controllers/ExpressQuizController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import QuizEditor from '@/components/quiz/QuizEditor.svelte';
    import QuizSaveBar from '@/components/quiz/QuizSaveBar.svelte';
    import { Button } from '@/components/ui/button';
    import { postImage } from '@/lib/markdown';
    import { draftsFrom, questionsPayload } from '@/lib/quiz';
    import type { QuestionDraft, SavedQuestion } from '@/lib/quiz';
    import { toUrl } from '@/lib/utils';
    import { store as storeImage } from '@/routes/express-quizzes/images';

    let {
        quiz,
        limits,
    }: {
        quiz: {
            id: number;
            title: string;
            seconds_per_question: number;
            questions: SavedQuestion[];
        };
        limits: {
            min_seconds: number;
            max_seconds: number;
            max_questions: number;
        };
    } = $props();

    // svelte-ignore state_referenced_locally
    let title = $state(quiz.title);
    // svelte-ignore state_referenced_locally
    let seconds: string = $state(String(quiz.seconds_per_question));
    // svelte-ignore state_referenced_locally
    let questions: QuestionDraft[] = $state(draftsFrom(quiz.questions));
    let saving = $state(false);

    function save(): void {
        router.put(
            ExpressQuizController.update.url(quiz.id),
            {
                title,
                seconds_per_question: Number(seconds),
                questions: questionsPayload(questions),
            },
            {
                preserveScroll: true,
                onStart: () => (saving = true),
                onFinish: () => (saving = false),
                onSuccess: () => (questions = draftsFrom(quiz.questions)),
                onError: () =>
                    toast.error(
                        'Le quiz n’est pas enregistré : corrige les champs en rouge.',
                    ),
            },
        );
    }
</script>

<AppHead title={`Quiz express · ${quiz.title}`} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 pb-28">
    <div>
        <Heading
            title="Quiz express"
            description="Le temps par question compte : plus un participant répond vite, plus il gagne de points."
        />
        <Link
            href={toUrl(index())}
            class="mono-link text-muted-foreground">← Tous les quiz express</Link
        >
    </div>

    <QuizEditor
        bind:title
        bind:seconds
        bind:questions
        {limits}
        allowNoLimit={false}
        isNew={quiz.questions.length === 0}
        upload={async (file) =>
            (await postImage(storeImage(quiz.id).url, file)).id}
    />

    <Form
        {...ExpressQuizController.destroy.form(quiz.id)}
        onBefore={() =>
            confirm(
                'Supprimer ce quiz express, ses questions et ses sessions passées ?',
            )}
    >
        {#snippet children({ processing })}
            <Button
                type="submit"
                variant="ghost"
                disabled={processing}
                class="h-11 text-destructive sm:h-9"
            >
                <Trash2 class="size-4" />
                Supprimer le quiz express
            </Button>
        {/snippet}
    </Form>
</div>

<QuizSaveBar count={questions.length} {saving} onsave={save} />
