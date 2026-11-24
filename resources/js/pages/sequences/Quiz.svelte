<script module lang="ts">
    import { index } from '@/routes/sequences';

    export const layout = {
        breadcrumbs: [{ title: 'Séquences', href: index() }],
    };
</script>

<script lang="ts">
    import { Form, Link, router } from '@inertiajs/svelte';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import { toast } from 'svelte-sonner';
    import QuizController from '@/actions/App/Http/Controllers/QuizController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import QuizEditor from '@/components/quiz/QuizEditor.svelte';
    import QuizSaveBar from '@/components/quiz/QuizSaveBar.svelte';
    import { Button } from '@/components/ui/button';
    import { uploadSequenceImage } from '@/lib/markdown';
    import { draftsFrom, questionsPayload } from '@/lib/quiz';
    import type { QuestionDraft, SavedQuestion } from '@/lib/quiz';
    import { toUrl } from '@/lib/utils';
    import { show } from '@/routes/sequences';

    let {
        sequence,
        quiz,
        limits,
    }: {
        sequence: { id: number; title: string };
        quiz: {
            title: string;
            seconds_per_question: number | null;
            questions: SavedQuestion[];
        } | null;
        limits: {
            min_seconds: number;
            max_seconds: number;
            max_questions: number;
        };
    } = $props();

    // The whole quiz is edited here and saved in one go.
    // svelte-ignore state_referenced_locally
    let title = $state(quiz?.title ?? sequence.title);
    // svelte-ignore state_referenced_locally
    let seconds: string = $state(
        quiz ? String(quiz.seconds_per_question ?? '') : '30',
    );
    // svelte-ignore state_referenced_locally
    let questions: QuestionDraft[] = $state(draftsFrom(quiz?.questions ?? []));
    let saving = $state(false);

    function save(): void {
        router.put(
            QuizController.update.url(sequence.id),
            {
                title,
                seconds_per_question: seconds === '' ? null : Number(seconds),
                questions: questionsPayload(questions),
            },
            {
                preserveScroll: true,
                onStart: () => (saving = true),
                onFinish: () => (saving = false),
                onSuccess: () =>
                    (questions = draftsFrom(quiz?.questions ?? [])),
                onError: () =>
                    toast.error(
                        'Le quiz n’est pas enregistré : corrige les champs en rouge.',
                    ),
            },
        );
    }
</script>

<AppHead title={`Quiz · ${sequence.title}`} />

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4 pb-28">
    <div>
        <Heading
            title="Quiz"
            description={`${sequence.title} : place le bouton dans le texte avec [Quizz] (sinon il apparaît à la fin).`}
        />
        <Link
            href={toUrl(show(sequence.id))}
            class="mono-link text-muted-foreground">← {sequence.title}</Link
        >
    </div>

    <QuizEditor
        bind:title
        bind:seconds
        bind:questions
        {limits}
        allowNoLimit
        isNew={!quiz}
        upload={async (file) =>
            (await uploadSequenceImage(sequence.id, file)).id}
    />

    {#if quiz}
        <Form
            {...QuizController.destroy.form(sequence.id)}
            onBefore={() =>
                confirm(
                    'Supprimer le quiz et ses questions ? Le bouton disparaîtra de la séquence.',
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
                    Supprimer le quiz
                </Button>
            {/snippet}
        </Form>
    {/if}
</div>

<QuizSaveBar count={questions.length} {saving} onsave={save} />
