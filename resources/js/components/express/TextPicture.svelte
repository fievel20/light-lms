<script lang="ts">
    import { cn } from '@/lib/utils';

    // A text drawn by the server, shown at a fixed scale so that every text
    // has the same size (the images are as wide as their text).
    let {
        src,
        scale,
        class: className = '',
    }: { src: string; scale: number; class?: string } = $props();

    let width: number | null = $state(null);
</script>

<img
    {src}
    alt=""
    draggable="false"
    class={cn('h-auto max-w-full', width === null && 'opacity-0', className)}
    style:width={width === null ? undefined : `${width * scale}px`}
    onload={(event) =>
        (width = (event.currentTarget as HTMLImageElement).naturalWidth)}
/>
