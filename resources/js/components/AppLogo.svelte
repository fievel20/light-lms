<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';

    const name = $derived(page.props.name);
</script>

<div
    class="flex aspect-square size-8 items-center justify-center rounded-md bg-ink text-cta dark:bg-cta dark:text-night"
>
    <AppLogoIcon class="size-5" />
</div>
<div class="ml-1 grid flex-1 text-left">
    <span class="truncate font-display text-base leading-tight font-bold tracking-tight uppercase"
        >{name}</span
    >
</div>
