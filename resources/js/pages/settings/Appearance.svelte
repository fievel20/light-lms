<script module lang="ts">
    import { edit as editAppearance } from '@/routes/appearance';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Apparence',
                href: editAppearance(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppearanceTabs from '@/components/AppearanceTabs.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
</script>

<AppHead title="Apparence" />

<h1 class="sr-only">Apparence</h1>

<div class="space-y-6">
    <Heading
        variant="small"
        title="Apparence"
        description="Choisis le thème de l’interface."
    />
    <AppearanceTabs />
</div>
