<script lang="ts">
    let {
        percent,
        label,
    }: {
        percent: number;
        label: string;
    } = $props();
</script>

<div class="grid gap-1">
    <div class="flex items-baseline justify-between text-sm">
        <span class="text-muted-foreground">{label}</span>
        <span class="font-semibold tabular-nums">{percent} %</span>
    </div>
    <div
        class="h-2 overflow-hidden rounded-full bg-muted"
        role="progressbar"
        aria-label={label}
        aria-valuemin={0}
        aria-valuemax={100}
        aria-valuenow={percent}
    >
        <div
            class="h-full rounded-full bg-primary transition-[width]"
            style:width={`${percent}%`}
        ></div>
    </div>
</div>
