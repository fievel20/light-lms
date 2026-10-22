<script lang="ts">
    import InputError from '@/components/InputError.svelte';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import type { Promotion } from '@/types/promotion';

    let {
        errors,
        promotion,
    }: {
        errors: Record<string, string | undefined>;
        promotion?: Promotion;
    } = $props();
</script>

<div class="grid gap-2">
    <Label for="name">Nom</Label>
    <Input
        id="name"
        name="name"
        value={promotion?.name ?? ''}
        required
        maxlength={255}
        placeholder="ex. DWWM 2026"
        autocomplete="off"
    />
    <InputError message={errors.name} />
</div>

<div class="grid gap-6 sm:grid-cols-2">
    <div class="grid gap-2">
        <Label for="starts_on">Date de début</Label>
        <Input
            id="starts_on"
            name="starts_on"
            type="date"
            value={promotion?.starts_on ?? ''}
            required
        />
        <InputError message={errors.starts_on} />
    </div>

    <div class="grid gap-2">
        <Label for="ends_on">Date de fin</Label>
        <Input
            id="ends_on"
            name="ends_on"
            type="date"
            value={promotion?.ends_on ?? ''}
            required
        />
        <InputError message={errors.ends_on} />
    </div>
</div>
