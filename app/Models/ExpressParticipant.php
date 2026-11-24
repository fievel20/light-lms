<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Someone who joined a session with a name, without an account. Their
 * browser keeps a token (cookie) to come back after a reload.
 *
 * @property int $id
 * @property int $express_session_id
 * @property string $name
 * @property string $name_key
 * @property string $token_hash
 * @property int $score
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'name_key', 'token_hash', 'score'])]
class ExpressParticipant extends Model
{
    public const int MAX_NAME = 30;

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @return BelongsTo<ExpressSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ExpressSession::class, 'express_session_id');
    }

    /**
     * @return HasMany<ExpressAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ExpressAnswer::class);
    }
}
