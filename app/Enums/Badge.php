<?php

namespace App\Enums;

/**
 * Automatic badges, in display order.
 */
enum Badge: string
{
    case FirstStep = 'premier-pas';
    case FirstSuccess = 'premiere-reussite';
    case Diligent = 'assidu';
    case ClearSighted = 'lucide';
    case Expert = 'expert';
    case Halfway = 'mi-parcours';
    case FinalSprint = 'sprint-final';
    case Graduate = 'diplomable';

    /** Confirmed self-assessments needed for the "lucide" badge. */
    public const int CLEAR_SIGHTED_THRESHOLD = 5;

    public function label(): string
    {
        return match ($this) {
            self::FirstStep => 'Premier pas',
            self::FirstSuccess => 'Première réussite',
            self::Diligent => 'Assidu',
            self::ClearSighted => 'Lucide',
            self::Expert => 'Expert',
            self::Halfway => 'Mi-parcours',
            self::FinalSprint => 'Sprint final',
            self::Graduate => 'Diplômable',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FirstStep => 'Tu t\'es autoévalué(e) pour la première fois.',
            self::FirstSuccess => 'Ta première compétence est acquise.',
            self::Diligent => 'Tu t\'es positionné(e) sur toutes les compétences d\'une promotion.',
            self::ClearSighted => self::CLEAR_SIGHTED_THRESHOLD.' autoévaluations confirmées à l\'identique par ton formateur.',
            self::Expert => 'Une compétence validée au niveau le plus élevé.',
            self::Halfway => 'La moitié des compétences d\'une promotion est acquise.',
            self::FinalSprint => '75 % des compétences d\'une promotion sont acquises.',
            self::Graduate => 'Toutes les compétences d\'une promotion sont acquises.',
        };
    }

    /**
     * @return array{key: string, label: string, description: string}
     */
    public function toPresentation(): array
    {
        return [
            'key' => $this->value,
            'label' => $this->label(),
            'description' => $this->description(),
        ];
    }
}
