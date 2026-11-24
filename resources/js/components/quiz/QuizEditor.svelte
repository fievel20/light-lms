<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import ArrowDown from '@lucide/svelte/icons/arrow-down';
    import ArrowUp from '@lucide/svelte/icons/arrow-up';
    import Braces from '@lucide/svelte/icons/braces';
    import ImagePlus from '@lucide/svelte/icons/image-plus';
    import Plus from '@lucide/svelte/icons/plus';
    import Trash2 from '@lucide/svelte/icons/trash-2';
    import X from '@lucide/svelte/icons/x';
    import { toast } from 'svelte-sonner';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { nativeFieldClass } from '@/lib/form';
    import {
        emptyQuestion,
        letters,
        letterTiles,
        parseQuizJson,
    } from '@/lib/quiz';
    import type { Letter, ParsedQuiz, QuestionDraft } from '@/lib/quiz';
    import { cn } from '@/lib/utils';

    // The settings and questions of a quiz, edited on the page and saved in
    // one go by the page (quiz of a sequence, quiz express).
    let {
        title = $bindable(),
        seconds = $bindable(),
        questions = $bindable(),
        limits,
        allowNoLimit,
        isNew,
        upload,
    }: {
        title: string;
        // '' means no limit, when allowed.
        seconds: string;
        questions: QuestionDraft[];
        limits: {
            min_seconds: number;
            max_seconds: number;
            max_questions: number;
        };
        allowNoLimit: boolean;
        // Not saved yet: a pasted JSON gives its title.
        isNew: boolean;
        // Sends an image; resolves with its id.
        upload: (file: File) => Promise<number>;
    } = $props();

    let uploading: string | null = $state(null);
    let jsonSource = $state('');
    let parsed: ParsedQuiz | null = $state(null);

    const timeOptions = $derived(
        [10, 15, 20, 30, 45, 60, 90, 120, 180, 300].filter(
            (value) =>
                value >= limits.min_seconds && value <= limits.max_seconds,
        ),
    );

    const errors = $derived(
        (page.props.errors ?? {}) as Record<string, string | undefined>,
    );

    const fieldError = (index: number, field: string) =>
        errors[`questions.${index}.${field}`];

    function move(index: number, delta: -1 | 1): void {
        const target = index + delta;
        [questions[index], questions[target]] = [
            questions[target],
            questions[index],
        ];
    }

    function remove(index: number): void {
        questions.splice(index, 1);
    }

    function setCorrect(question: QuestionDraft, letter: Letter): void {
        question.correct = letter;
    }

    async function addImage(question: QuestionDraft, file: File | undefined) {
        if (!file) {
            return;
        }

        uploading = question.key;

        try {
            question.image_id = await upload(file);
            question.image_url = URL.createObjectURL(file);
        } catch (error) {
            toast.error(
                error instanceof Error
                    ? error.message
                    : "L'image n'a pas pu être envoyée.",
            );
        } finally {
            uploading = null;
        }
    }

    function importParsed(mode: 'append' | 'replace'): void {
        if (!parsed) {
            return;
        }

        questions =
            mode === 'replace'
                ? parsed.questions
                : [...questions, ...parsed.questions];

        if (parsed.title && (mode === 'replace' || isNew)) {
            title = parsed.title;
        }

        toast.success(
            `${parsed.questions.length} question${parsed.questions.length > 1 ? 's' : ''} ajoutée${parsed.questions.length > 1 ? 's' : ''} : pense à enregistrer.`,
        );
        parsed = null;
        jsonSource = '';
    }
</script>

<section class="grid gap-4 surface p-4 sm:grid-cols-[1fr_12rem]">
    <div class="grid gap-2">
        <Label for="quiz-title">Titre du quiz</Label>
        <Input
            id="quiz-title"
            bind:value={title}
            maxlength={255}
            class="h-11 sm:h-9"
        />
        <InputError message={errors.title} />
    </div>
    <div class="grid gap-2">
        <Label for="quiz-seconds">Temps par question</Label>
        <select
            id="quiz-seconds"
            bind:value={seconds}
            class={cn(nativeFieldClass, 'h-11 sm:h-9 sm:py-1')}
        >
            {#if allowNoLimit}
                <option value="">Sans limite</option>
            {/if}
            {#each timeOptions as option (option)}
                <option value={String(option)}
                    >{option < 60
                        ? `${option} s`
                        : `${option / 60} min${option % 60 ? ` ${option % 60} s` : ''}`}</option
                >
            {/each}
        </select>
        <InputError message={errors.seconds_per_question} />
    </div>
</section>

{#if errors.questions}
    <p class="text-sm text-destructive">{errors.questions}</p>
{/if}

{#if questions.length === 0}
    <p
        class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
    >
        Aucune question. Ajoute-en une, ou colle un JSON ci-dessous.
    </p>
{/if}

<ol class="flex flex-col gap-4">
    {#each questions as question, index (question.key)}
        <li class="grid gap-3 surface p-4">
            <div class="flex items-center gap-1">
                <span class="flex-1 eyebrow">Question {index + 1}</span>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-11 text-muted-foreground sm:size-9"
                    disabled={index === 0}
                    aria-label={`Monter la question ${index + 1}`}
                    onclick={() => move(index, -1)}
                >
                    <ArrowUp class="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-11 text-muted-foreground sm:size-9"
                    disabled={index === questions.length - 1}
                    aria-label={`Descendre la question ${index + 1}`}
                    onclick={() => move(index, 1)}
                >
                    <ArrowDown class="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-11 text-muted-foreground hover:text-destructive sm:size-9"
                    aria-label={`Supprimer la question ${index + 1}`}
                    onclick={() => remove(index)}
                >
                    <Trash2 class="size-4" />
                </Button>
            </div>

            <div class="grid gap-2">
                <Label for={`question-${question.key}`} class="sr-only"
                    >Énoncé</Label
                >
                <textarea
                    id={`question-${question.key}`}
                    bind:value={question.question}
                    rows="2"
                    maxlength={1000}
                    placeholder="Que veut dire coopérer ?"
                    class={cn(nativeFieldClass, 'resize-y text-base')}
                ></textarea>
                <InputError message={fieldError(index, 'question')} />
            </div>

            <div class="flex flex-wrap items-center gap-3">
                {#if question.image_url}
                    <img
                        src={question.image_url}
                        alt=""
                        class="max-h-32 rounded-md border border-border"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        class="h-11 text-muted-foreground sm:h-9"
                        onclick={() => {
                            question.image_id = null;
                            question.image_url = null;
                        }}
                    >
                        <X class="size-4" />
                        Retirer l’image
                    </Button>
                {:else}
                    <label
                        class="inline-flex h-11 cursor-pointer items-center gap-2 rounded-md border border-input px-3 text-sm font-medium text-muted-foreground hover:bg-muted sm:h-9"
                    >
                        {#if uploading === question.key}<Spinner
                            />{:else}<ImagePlus class="size-4" />{/if}
                        Ajouter une image
                        <input
                            type="file"
                            accept="image/png,image/jpeg,image/webp,image/gif"
                            class="sr-only"
                            onchange={(event) => {
                                void addImage(
                                    question,
                                    event.currentTarget.files?.[0],
                                );
                                event.currentTarget.value = '';
                            }}
                        />
                    </label>
                {/if}
                <InputError message={fieldError(index, 'image_id')} />
            </div>

            <fieldset class="grid gap-2 sm:grid-cols-2">
                <legend class="mb-2 text-sm text-muted-foreground">
                    Réponses (C et D facultatives) : touche la lettre de la
                    bonne réponse.
                </legend>
                {#each letters as letter (letter)}
                    {@const field = `choice_${letter}` as const}
                    <div class="grid gap-1">
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class={cn(
                                    'grid size-11 shrink-0 place-items-center rounded-md font-display text-lg font-bold uppercase transition sm:size-9',
                                    question.correct === letter
                                        ? letterTiles[letter]
                                        : 'border border-input text-muted-foreground',
                                )}
                                aria-pressed={question.correct === letter}
                                aria-label={`La bonne réponse est ${letter.toUpperCase()}`}
                                onclick={() => setCorrect(question, letter)}
                                >{letter}</button
                            >
                            <Input
                                bind:value={question[field]}
                                maxlength={255}
                                placeholder={letter === 'a' || letter === 'b'
                                    ? `Réponse ${letter.toUpperCase()}`
                                    : `Réponse ${letter.toUpperCase()} (facultative)`}
                                aria-label={`Réponse ${letter.toUpperCase()}`}
                                class="h-11 sm:h-9"
                            />
                        </div>
                        <InputError message={fieldError(index, field)} />
                    </div>
                {/each}
                <div class="sm:col-span-2">
                    <InputError message={fieldError(index, 'correct')} />
                </div>
            </fieldset>
        </li>
    {/each}
</ol>

{#if questions.length < limits.max_questions}
    <Button
        type="button"
        variant="outline"
        class="h-11 sm:h-9 sm:justify-self-start"
        onclick={() => questions.push(emptyQuestion())}
    >
        <Plus class="size-4" />
        Ajouter une question
    </Button>
{/if}

<details class="surface p-4" open={questions.length === 0}>
    <summary
        class="flex min-h-11 cursor-pointer list-none items-center gap-2 font-medium"
    >
        <Braces class="size-4" />
        Coller des questions en JSON
    </summary>
    <div class="mt-3 grid gap-3">
        <textarea
            bind:value={jsonSource}
            rows="6"
            spellcheck="false"
            placeholder={'{"title":"Test","questions":[{"question":"…","choice_a":"…","choice_b":"…","choice_c":"…","choice_d":"…","correct":"c"}]}'}
            class={cn(nativeFieldClass, 'resize-y font-mono text-xs')}
        ></textarea>
        <Button
            type="button"
            variant="outline"
            disabled={jsonSource.trim() === ''}
            class="h-11 sm:h-9 sm:justify-self-start"
            onclick={() => (parsed = parseQuizJson(jsonSource))}
        >
            Analyser
        </Button>

        {#if parsed}
            <div class="grid gap-2 rounded-md bg-muted p-3 text-sm">
                <p class="font-medium">
                    {parsed.questions.length} question{parsed.questions
                        .length > 1
                        ? 's'
                        : ''} reconnue{parsed.questions.length > 1
                        ? 's'
                        : ''}{parsed.title ? ` · « ${parsed.title} »` : ''}
                </p>
                {#if parsed.questions.length > 0}
                    <ol class="list-decimal pl-5 text-muted-foreground">
                        {#each parsed.questions as item (item.key)}
                            <li>{item.question}</li>
                        {/each}
                    </ol>
                {/if}
                {#if parsed.errors.length > 0}
                    <ul class="grid gap-1 text-destructive">
                        {#each parsed.errors as message (message)}
                            <li>{message}</li>
                        {/each}
                    </ul>
                {/if}
                {#if parsed.questions.length > 0}
                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            class="h-11 sm:h-9"
                            onclick={() => importParsed('append')}
                        >
                            Ajouter à la suite
                        </Button>
                        {#if questions.length > 0}
                            <Button
                                type="button"
                                variant="outline"
                                class="h-11 sm:h-9"
                                onclick={() => importParsed('replace')}
                            >
                                Remplacer les {questions.length} questions
                            </Button>
                        {/if}
                    </div>
                {/if}
            </div>
        {/if}
    </div>
</details>
