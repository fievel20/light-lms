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
    import type { FrameworkOption, TypeOption } from '@/types/framework';

    let {
        framework,
        types,
    }: { framework: FrameworkOption; types: TypeOption[] } = $props();
</script>

<AppHead title="Nouvelle compétence" />

<div class="mx-auto w-full max-w-xl p-4">
    <Heading title="Nouvelle compétence" description={framework.name} />
    <CompetencyForm {framework} {types} />
</div>
