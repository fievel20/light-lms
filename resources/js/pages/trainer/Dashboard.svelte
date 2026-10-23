<script module lang="ts">
    import { dashboard } from '@/routes';

    export const layout = {
        breadcrumbs: [{ title: 'Accueil', href: dashboard() }],
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowRight from '@lucide/svelte/icons/arrow-right';
    import ChartColumn from '@lucide/svelte/icons/chart-column';
    import Gauge from '@lucide/svelte/icons/gauge';
    import Inbox from '@lucide/svelte/icons/inbox';
    import Mail from '@lucide/svelte/icons/mail';
    import Plus from '@lucide/svelte/icons/plus';
    import Users from '@lucide/svelte/icons/users';
    import Target from '@lucide/svelte/icons/target';
    import UserPlus from '@lucide/svelte/icons/user-plus';
    import UsersRound from '@lucide/svelte/icons/users-round';
    import type { Component } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import EmptyState from '@/components/design/EmptyState.svelte';
    import NightBand from '@/components/design/NightBand.svelte';
    import PageHeader from '@/components/design/PageHeader.svelte';
    import StatTile from '@/components/design/StatTile.svelte';
    import { Button } from '@/components/ui/button';
    import { formatDate } from '@/lib/dates';
    import { toUrl } from '@/lib/utils';
    import { create, edit, progress, show } from '@/routes/promotions';
    import * as promotionCompetencies from '@/routes/promotions/competencies';
    import * as promotionLearners from '@/routes/promotions/learners';

    type Promotion = {
        id: number;
        name: string;
        framework: string | null;
        starts_on: string;
        ends_on: string;
        archived_at: string | null;
    };

    let {
        promotion,
        summary,
        pendingInvitations,
    }: {
        promotion: Promotion | null;
        summary: {
            learners: number;
            competencies: number;
            average_percent: number;
            to_review: number;
        } | null;
        pendingInvitations: number;
    } = $props();

    type Entry = {
        title: string;
        description: string;
        href: string;
        icon: Component<{ class?: string }>;
    };

    const entries = $derived<Entry[]>(
        promotion
            ? [
                  {
                      title: 'Par compétence',
                      description: 'Tous les apprenants pour une compétence.',
                      href: toUrl(promotionCompetencies.index(promotion.id)),
                      icon: Target,
                  },
                  {
                      title: 'Par apprenant',
                      description: 'Toutes les compétences d’un apprenant.',
                      href: toUrl(promotionLearners.index(promotion.id)),
                      icon: UsersRound,
                  },
                  {
                      title: 'Suivi',
                      description: 'Carte de chaleur des compétences de la promotion.',
                      href: toUrl(progress(promotion.id)),
                      icon: ChartColumn,
                  },
                  {
                      title: 'Inscriptions',
                      description: 'Ajouter, importer et inviter des apprenants.',
                      href: toUrl(show(promotion.id)),
                      icon: UserPlus,
                  },
              ]
            : [],
    );
</script>

<AppHead title="Accueil" />

<div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 sm:p-6">
    {#if !promotion || !summary}
        <PageHeader title="Bienvenue 👋" subtitle="Commence par créer ta première promotion." />
        <EmptyState icon={Users} title="Aucune promotion" description="Une promotion regroupe des apprenants et un référentiel.">
            {#snippet action()}
                <Button asChild class="h-11">
                    {#snippet children(props)}
                        <Link {...props} href={toUrl(create())}>
                            <Plus class="size-4" />
                            Créer une promotion
                        </Link>
                    {/snippet}
                </Button>
            {/snippet}
        </EmptyState>
    {:else}
        <NightBand class="flex flex-col gap-6 p-5 sm:p-6">
        <PageHeader
            eyebrow="Promotion active"
            title={promotion.name}
            subtitle={`${promotion.framework ?? 'Aucun référentiel'} · ${formatDate(promotion.starts_on)} → ${formatDate(promotion.ends_on)}${promotion.archived_at ? ' · archivée' : ''}`}
        />

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <StatTile label="Apprenants" value={summary.learners} icon={Users} />
            <StatTile label="Progression moyenne" value={`${summary.average_percent}%`} icon={Gauge} tone="primary" />
            <StatTile
                label="À examiner"
                value={summary.to_review}
                icon={Inbox}
                tone={summary.to_review > 0 ? 'warning' : 'success'}
            />
            <StatTile label="Invitations en attente" value={pendingInvitations} icon={Mail} />
        </div>
        </NightBand>

        {#if !promotion.framework}
            <EmptyState
                icon={ChartColumn}
                title="Pas encore de référentiel"
                description="Affecte un référentiel à cette promotion pour suivre les compétences."
            >
                {#snippet action()}
                    <Button asChild variant="outline" class="h-11">
                        {#snippet children(props)}
                            <Link {...props} href={toUrl(edit(promotion.id))}>Choisir un référentiel</Link>
                        {/snippet}
                    </Button>
                {/snippet}
            </EmptyState>
        {/if}

        <ul class="grid gap-3 sm:grid-cols-2">
            {#each entries as entry (entry.title)}
                <li>
                    <Link href={entry.href} class="surface group flex h-full items-center gap-4 p-5 transition-colors hover:bg-muted/40">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-primary-soft text-primary">
                            <entry.icon class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-bold">{entry.title}</span>
                            <span class="block text-sm text-muted-foreground">{entry.description}</span>
                        </span>
                        <ArrowRight class="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5" />
                    </Link>
                </li>
            {/each}
        </ul>
    {/if}
</div>
