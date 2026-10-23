<script lang="ts">
    import PartyPopper from '@lucide/svelte/icons/party-popper';
    import { onMount } from 'svelte';
    import { fade, scale } from 'svelte/transition';
    import BadgeMedal from '@/components/design/BadgeMedal.svelte';
    import { Button } from '@/components/ui/button';
    import type { BadgeInfo } from '@/types/badge';

    type Celebration = { promotion: string; code: string; name: string };

    let {
        celebrations,
        badges = [],
    }: { celebrations: Celebration[]; badges?: BadgeInfo[] } = $props();

    let open = $state(false);

    const colors = ['#6d5ef8', '#22c55e', '#f59e0b', '#ec4899', '#06b6d4'];

    // Pre-computed confetti: position, colour, delay, drift and rotation.
    const confetti = Array.from({ length: 48 }, (_, index) => ({
        left: Math.random() * 100,
        color: colors[index % colors.length],
        delay: Math.random() * 0.6,
        duration: 2.2 + Math.random() * 1.4,
        drift: (Math.random() - 0.5) * 160,
        spin: Math.random() * 720 - 360,
        size: 6 + Math.random() * 6,
        round: Math.random() > 0.6,
    }));

    onMount(() => {
        open = celebrations.length > 0 || badges.length > 0;
    });

    function close(): void {
        open = false;
    }
</script>

<svelte:window onkeydown={(event) => open && event.key === 'Escape' && close()} />

{#if open}
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-background/60 p-6 backdrop-blur-sm"
        transition:fade={{ duration: 200 }}
        role="presentation"
        onclick={close}
    >
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            {#each confetti as piece, index (index)}
                <span
                    class="confetti absolute top-0 block"
                    style:left={`${piece.left}%`}
                    style:width={`${piece.size}px`}
                    style:height={`${piece.round ? piece.size : piece.size * 1.6}px`}
                    style:background={piece.color}
                    style:border-radius={piece.round ? '9999px' : '2px'}
                    style:animation-delay={`${piece.delay}s`}
                    style:animation-duration={`${piece.duration}s`}
                    style:--drift={`${piece.drift}px`}
                    style:--spin={`${piece.spin}deg`}
                ></span>
            {/each}
        </div>

        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="celebrate-title"
            tabindex="-1"
            class="surface relative w-full max-w-sm p-6 text-center"
            transition:scale={{ start: 0.85, duration: 300 }}
            onclick={(event) => event.stopPropagation()}
            onkeydown={(event) => event.stopPropagation()}
        >
            <span
                class="mx-auto mb-4 grid size-16 place-items-center rounded-2xl bg-success-soft text-success-ink"
            >
                <PartyPopper class="size-8" />
            </span>
            <h2 id="celebrate-title" class="text-2xl font-extrabold tracking-tight">
                Bravo !
            </h2>
            {#if celebrations.length > 0}
                <p class="mt-1 text-sm text-muted-foreground">
                    {celebrations.length > 1
                        ? `Tu as acquis ${celebrations.length} nouvelles compétences :`
                        : 'Tu as acquis une nouvelle compétence :'}
                </p>
                <ul class="mt-4 grid max-h-48 gap-2 overflow-y-auto text-left">
                    {#each celebrations as celebration (celebration.promotion + celebration.code)}
                        <li class="flex items-center gap-3 rounded-xl bg-success-soft px-3 py-2">
                            <span class="font-mono text-xs font-bold text-success-ink">{celebration.code}</span>
                            <span class="min-w-0 truncate text-sm font-medium">{celebration.name}</span>
                        </li>
                    {/each}
                </ul>
            {/if}

            {#if badges.length > 0}
                <p class="mt-4 text-sm text-muted-foreground">
                    {badges.length > 1 ? 'Nouveaux badges débloqués :' : 'Nouveau badge débloqué :'}
                </p>
                <ul class="mt-3 grid gap-2 text-left">
                    {#each badges as badge (badge.key)}
                        <li class="flex items-center gap-3 rounded-xl bg-muted/60 px-3 py-2">
                            <BadgeMedal badgeKey={badge.key} size="sm" />
                            <span class="min-w-0">
                                <span class="block text-sm font-bold">{badge.label}</span>
                                <span class="block truncate text-xs text-muted-foreground"
                                    >{badge.description}</span
                                >
                            </span>
                        </li>
                    {/each}
                </ul>
            {/if}
            <Button class="mt-6 h-11 w-full" onclick={close}>Continuer</Button>
        </div>
    </div>
{/if}

<style>
    .confetti {
        transform: translateY(-20px);
        opacity: 0;
        animation-name: fall;
        animation-timing-function: cubic-bezier(0.25, 0.6, 0.5, 1);
        animation-fill-mode: forwards;
    }

    @keyframes fall {
        0% {
            transform: translate(0, -20px) rotate(0deg);
            opacity: 1;
        }
        100% {
            transform: translate(var(--drift), 105vh) rotate(var(--spin));
            opacity: 0.9;
        }
    }
</style>
