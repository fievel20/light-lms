<script lang="ts">
    import Check from '@lucide/svelte/icons/check';
    import Clock from '@lucide/svelte/icons/clock';
    import X from '@lucide/svelte/icons/x';
    import { letterTiles } from '@/lib/quiz';
    import type { CorrectedQuestion, Letter } from '@/lib/quiz';
    import { cn } from '@/lib/utils';


    let { questions }: { questions: CorrectedQuestion[] } = $props();
</script>

<ol class="flex flex-col gap-4">
    {#each questions as question, index (question.id)}
        {@const right = question.answer === question.correct}
        <li class="grid gap-3 surface p-4">
            <div class="flex items-start gap-3">
                <span
                    class={cn(
                        'grid size-8 shrink-0 place-items-center rounded-full',
                        right
                            ? 'bg-success text-success-foreground'
                            : 'bg-destructive text-white',
                    )}
                >
                    {#if right}<Check class="size-4" />{:else}<X
                            class="size-4"
                        />{/if}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="eyebrow">Question {index + 1}</p>
                    <p class="font-medium">{question.question}</p>
                    {#if question.answer === null}
                        <p
                            class="mt-1 inline-flex items-center gap-1 text-sm text-muted-foreground"
                        >
                            <Clock class="size-4" /> Temps écoulé, pas de réponse
                        </p>
                    {/if}
                </div>
            </div>
            {#if question.image_url}
                <img
                    src={question.image_url}
                    alt=""
                    class="max-h-48 justify-self-start rounded-md border border-border"
                />
            {/if}
            <ul class="grid gap-2 sm:grid-cols-2">
                {#each Object.entries(question.choices) as [letter, text] (letter)}
                    {@const isCorrect = letter === question.correct}
                    {@const isChosen = letter === question.answer}
                    <li
                        class={cn(
                            'flex items-center gap-2 rounded-md border p-2 text-sm',
                            isCorrect &&
                                'border-success bg-success-soft font-semibold',
                            isChosen &&
                                !isCorrect &&
                                'border-destructive bg-destructive/10',
                            !isCorrect && !isChosen && 'border-border',
                        )}
                    >
                        <span
                            class={cn(
                                'grid size-7 shrink-0 place-items-center rounded font-display text-sm font-bold uppercase',
                                letterTiles[letter as Letter],
                            )}>{letter}</span
                        >
                        <span class="flex-1">{text}</span>
                        {#if isCorrect}
                            <span class="text-xs">Bonne réponse</span>
                        {:else if isChosen}
                            <span class="text-xs text-destructive"
                                >Ta réponse</span
                            >
                        {/if}
                    </li>
                {/each}
            </ul>
        </li>
    {/each}
</ol>
