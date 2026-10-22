<script module lang="ts">
    import { index } from '@/routes/frameworks';

    export const layout = {
        breadcrumbs: [{ title: 'Référentiels', href: index() }],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import CompetencyForm from '@/components/frameworks/CompetencyForm.svelte';
    import Heading from '@/components/Heading.svelte';
    import type {
        Competency,
        FrameworkOption,
        TypeOption,
    } from '@/types/framework';

    let {
        framework,
        competency,
        types,
    }: {
        framework: FrameworkOption;
        competency: Competency;
        types: TypeOption[];
    } = $props();
</script>

<AppHead title={`Modifier ${competency.code}`} />

<div class="mx-auto w-full max-w-xl p-4">
    <Heading
        title={`Modifier la compétence ${competency.code}`}
        description={framework.name}
    />
    <CompetencyForm {framework} {types} {competency} />
</div>
