<script lang="ts">
    import InputError from '@/components/InputError.svelte';
    import MarkdownEditor from '@/components/sequences/MarkdownEditor.svelte';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import type { Icon } from '@/types/icon';
    import type { Sequence } from '@/types/sequence';

    let {
        errors,
        sequence,
        icons,
    }: {
        errors: Record<string, string | undefined>;
        sequence?: Sequence;
        icons: Icon[];
    } = $props();

    // svelte-ignore state_referenced_locally
    let content = $state(sequence?.content ?? '');
</script>

<div class="grid gap-2">
    <Label for="title">Titre</Label>
    <Input
        id="title"
        name="title"
        value={sequence?.title ?? ''}
        required
        maxlength={255}
        placeholder="ex. Envoyer un mail"
        autocomplete="off"
    />
    <InputError message={errors.title} />
</div>

<div class="grid gap-2">
    <Label for="summary">Résumé (facultatif)</Label>
    <Input
        id="summary"
        name="summary"
        value={sequence?.summary ?? ''}
        maxlength={500}
        placeholder="ex. Envoyer son premier mail avec une pièce jointe"
        autocomplete="off"
    />
    <InputError message={errors.summary} />
</div>

<div class="grid gap-2">
    <Label for="content">Contenu</Label>
    <MarkdownEditor
        name="content"
        bind:value={content}
        maxlength={100000}
        sequenceId={sequence?.id ?? null}
        library={icons}
    />
    <p class="text-xs text-muted-foreground">
        Markdown : <code>1.</code> pour les étapes, <code>**gras**</code>,
        <code>&gt;</code> pour un encadré. Icônes : bouton 🙂 ou emojis tapés
        directement. Images : bouton, glisser-déposer ou coller une capture{sequence
            ? ''
            : ' (après le premier enregistrement)'}.
    </p>
    <InputError message={errors.content} />
</div>
