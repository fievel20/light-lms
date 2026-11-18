<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import UserRound from '@lucide/svelte/icons/user-round';
</script>

{#if page.props.demo}
    <!-- The visitor knows nothing they do here is kept. -->
    <div
        class="flex items-center gap-3 bg-primary-soft px-4 py-2 text-sm"
        role="status"
    >
        <UserRound class="size-4 shrink-0 text-primary" />
        <p class="min-w-0 flex-1">
            <span class="font-semibold">Compte démo</span> : tout ce que tu fais
            ici est effacé quand tu te déconnectes.
        </p>
    </div>
{/if}
