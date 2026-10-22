<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';

    const name = $derived(page.props.name);
</script>

<div
    class="flex aspect-square size-8 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm shadow-primary/30"
>
    <AppLogoIcon class="size-5" />
</div>
<div class="ml-1 grid flex-1 text-left">
    <span class="truncate text-base leading-tight font-extrabold tracking-tight"
        >{name}</span
    >
</div>
