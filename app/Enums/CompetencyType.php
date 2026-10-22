<?php

namespace App\Enums;

enum CompetencyType: string
{
    /** Acquired or not acquired. */
    case Binary = 'binary';

    /** Evaluated on a scale of levels defined per competency. */
    case Scale = 'scale';

    public function label(): string
    {
        return match ($this) {
            self::Binary => 'Acquis / non acquis',
            self::Scale => 'Échelle de niveaux',
        };
    }
}
