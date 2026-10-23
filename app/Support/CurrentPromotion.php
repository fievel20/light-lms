<?php

namespace App\Support;

use App\Models\Promotion;
use App\Models\User;

/**
 * The promotion the trainer is working on, chosen in the menu.
 */
class CurrentPromotion
{
    /**
     * The chosen promotion, or by default the most recent current one.
     */
    public function resolve(User $trainer): ?Promotion
    {
        if ($trainer->current_promotion_id !== null) {
            $chosen = Promotion::find($trainer->current_promotion_id);

            if ($chosen !== null) {
                return $chosen;
            }
        }

        return Promotion::query()
            ->orderByRaw('archived_at is not null')
            ->orderByDesc('starts_on')
            ->orderBy('name')
            ->first();
    }

    /**
     * Data for the promotion switcher of the menu.
     *
     * @return array{
     *     current: array{id: int, name: string}|null,
     *     active: list<array{id: int, name: string}>,
     *     archived: list<array{id: int, name: string}>,
     * }
     */
    public function switcher(User $trainer): array
    {
        $current = $this->resolve($trainer);
        $promotions = Promotion::query()->orderByDesc('starts_on')->orderBy('name')->get(['id', 'name', 'archived_at']);
        $option = fn (Promotion $promotion) => ['id' => $promotion->id, 'name' => $promotion->name];

        return [
            'current' => $current ? $option($current) : null,
            'active' => array_values($promotions->filter(fn (Promotion $p) => ! $p->isArchived())->map($option)->all()),
            'archived' => array_values($promotions->filter(fn (Promotion $p) => $p->isArchived())->map($option)->all()),
        ];
    }
}
