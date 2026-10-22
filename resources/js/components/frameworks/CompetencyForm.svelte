<script lang="ts">
    import { Link, useForm } from '@inertiajs/svelte';
    import Plus from '@lucide/svelte/icons/plus';
    import X from '@lucide/svelte/icons/x';
    import CompetencyController from '@/actions/App/Http/Controllers/CompetencyController';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    import { nativeFieldClass } from '@/lib/form';
    import { toUrl } from '@/lib/utils';
    import { show } from '@/routes/frameworks';
    import type {
        Competency,
        CompetencyType,
        FrameworkOption,
        TypeOption,
    } from '@/types/framework';

    const MAX_LEVELS = 10;

    let {
        framework,
        types,
        competency,
        assessed = false,
    }: {
        framework: FrameworkOption;
        types: TypeOption[];
        competency?: Competency;
        assessed?: boolean;
    } = $props();

    const defaultLevels = [
        { value: 1, label: 'Découverte' },
        { value: 2, label: 'Guidé' },
        { value: 3, label: 'Autonome' },
        { value: 4, label: 'Expert' },
    ];

    // svelte-ignore state_referenced_locally
    const form = useForm({
        code: competency?.code ?? '',
        name: competency?.name ?? '',
        description: competency?.description ?? '',
        type: (competency?.type ?? 'binary') as CompetencyType,
        levels: competency?.levels.length
            ? competency.levels.map((level) => ({ ...level }))
            : defaultLevels.map((level) => ({ ...level })),
        validation_threshold: (competency?.validation_threshold ?? null) as
            | number
            | null,
    });

    const errors = $derived(form.errors as Record<string, string | undefined>);

    function addLevel(): void {
        const next = Math.max(0, ...form.levels.map((level) => Number(level.value))) + 1;
        form.levels = [...form.levels, { value: next, label: '' }];
    }

    function removeLevel(index: number): void {
        const removed = form.levels[index];
        form.levels = form.levels.filter((_, i) => i !== index);

        if (form.validation_threshold === Number(removed.value)) {
            form.validation_threshold = null;
        }
    }

    function submit(event: SubmitEvent): void {
        event.preventDefault();
        form.submit(
            competency
                ? CompetencyController.update([framework.id, competency.id])
                : CompetencyController.store(framework.id),
        );
    }
</script>

<form onsubmit={submit} class="space-y-6">
    <div class="grid gap-6 sm:grid-cols-[8rem_1fr]">
        <div class="grid gap-2">
            <Label for="code">Code</Label>
            <Input
                id="code"
                bind:value={form.code}
                required
                maxlength={20}
                placeholder="C1"
                autocomplete="off"
            />
            <InputError message={errors.code} />
        </div>

        <div class="grid gap-2">
            <Label for="name">Intitulé</Label>
            <Input
                id="name"
                bind:value={form.name}
                required
                maxlength={255}
                placeholder="ex. Maquetter une application"
                autocomplete="off"
            />
            <InputError message={errors.name} />
        </div>
    </div>

    <div class="grid gap-2">
        <Label for="description">Description (facultatif)</Label>
        <textarea
            id="description"
            rows="2"
            maxlength="2000"
            class={nativeFieldClass}
            bind:value={form.description}
        ></textarea>
        <InputError message={errors.description} />
    </div>

    {#if assessed}
        <p class="rounded-lg border p-3 text-sm text-muted-foreground">
            Cette compétence a déjà été évaluée : son type et les valeurs de ses
            niveaux sont verrouillés. Tu peux encore modifier les libellés et
            le seuil.
        </p>
    {/if}

    <fieldset class="grid gap-2">
        <legend class="mb-2 text-sm font-medium">Type d'évaluation</legend>
        <div class="grid gap-2 sm:grid-cols-2">
            {#each types as type (type.value)}
                <label
                    class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm has-[:checked]:border-primary has-[:checked]:bg-muted"
                >
                    <input
                        type="radio"
                        name="type"
                        value={type.value}
                        bind:group={form.type}
                        disabled={assessed}
                        class="size-4"
                    />
                    {type.label}
                </label>
            {/each}
        </div>
        <InputError message={errors.type} />
    </fieldset>

    {#if form.type === 'scale'}
        <fieldset class="grid gap-3 surface p-4">
            <legend class="px-1 text-sm font-medium">Niveaux de l'échelle</legend>

            <ol class="grid gap-3">
                {#each form.levels as level, index (index)}
                    <li class="grid grid-cols-[4.5rem_1fr_auto] items-start gap-2">
                        <div class="grid gap-1">
                            <Label for={`level-value-${index}`} class="sr-only"
                                >Valeur du niveau {index + 1}</Label
                            >
                            <Input
                                id={`level-value-${index}`}
                                type="number"
                                inputmode="numeric"
                                min={0}
                                max={100}
                                required
                                readonly={assessed}
                                bind:value={level.value}
                            />
                            <InputError message={errors[`levels.${index}.value`]} />
                        </div>
                        <div class="grid gap-1">
                            <Label for={`level-label-${index}`} class="sr-only"
                                >Libellé du niveau {index + 1}</Label
                            >
                            <Input
                                id={`level-label-${index}`}
                                required
                                maxlength={100}
                                placeholder="Libellé"
                                bind:value={level.label}
                            />
                            <InputError message={errors[`levels.${index}.label`]} />
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-11 sm:size-9"
                            aria-label={`Supprimer le niveau ${index + 1}`}
                            disabled={assessed || form.levels.length <= 2}
                            onclick={() => removeLevel(index)}
                        >
                            <X class="size-4" />
                        </Button>
                    </li>
                {/each}
            </ol>
            <InputError message={errors.levels} />

            <Button
                type="button"
                variant="outline"
                size="sm"
                class="h-11 justify-self-start sm:h-9"
                disabled={assessed || form.levels.length >= MAX_LEVELS}
                onclick={addLevel}
            >
                <Plus class="size-4" />
                Ajouter un niveau
            </Button>

            <div class="grid gap-2">
                <Label for="validation_threshold">Seuil d'acquisition</Label>
                <select
                    id="validation_threshold"
                    class={nativeFieldClass}
                    bind:value={form.validation_threshold}
                >
                    <option value={null}>Niveau le plus élevé (par défaut)</option>
                    {#each form.levels as level, index (index)}
                        {#if level.label}
                            <option value={Number(level.value)}
                                >{level.value} – {level.label}</option
                            >
                        {/if}
                    {/each}
                </select>
                <p class="text-xs text-muted-foreground">
                    La compétence est considérée comme acquise à partir de ce
                    niveau.
                </p>
                <InputError message={errors.validation_threshold} />
            </div>
        </fieldset>
    {/if}

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <Button variant="outline" asChild class="h-11 sm:h-9">
            {#snippet children(props)}
                <Link {...props} href={toUrl(show(framework.id))}>Annuler</Link>
            {/snippet}
        </Button>
        <Button type="submit" disabled={form.processing} class="h-11 sm:h-9">
            {#if form.processing}<Spinner />{/if}
            {competency ? 'Enregistrer' : 'Ajouter la compétence'}
        </Button>
    </div>
</form>
