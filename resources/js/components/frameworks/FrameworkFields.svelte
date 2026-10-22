<script lang="ts">
    import InputError from '@/components/InputError.svelte';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { nativeFieldClass } from '@/lib/form';
    import type { Framework } from '@/types/framework';

    let {
        errors,
        framework,
    }: {
        errors: Record<string, string | undefined>;
        framework?: Framework;
    } = $props();
</script>

<div class="grid gap-2">
    <Label for="name">Nom</Label>
    <Input
        id="name"
        name="name"
        value={framework?.name ?? ''}
        required
        maxlength={255}
        placeholder="ex. Développeur Web et Web Mobile"
        autocomplete="off"
    />
    <InputError message={errors.name} />
</div>

<div class="grid gap-2">
    <Label for="description">Description (facultatif)</Label>
    <textarea
        id="description"
        name="description"
        rows="3"
        maxlength="2000"
        class={nativeFieldClass}>{framework?.description ?? ''}</textarea
    >
    <InputError message={errors.description} />
</div>
