<?php

namespace App\Enums;

enum Role: string
{
    case Trainer = 'trainer';
    case Learner = 'learner';

    public function label(): string
    {
        return match ($this) {
            self::Trainer => 'Formateur',
            self::Learner => 'Apprenant',
        };
    }
}
