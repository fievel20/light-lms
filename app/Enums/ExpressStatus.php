<?php

namespace App\Enums;

/**
 * The steps of a quiz express session, led by the trainer.
 */
enum ExpressStatus: string
{
    /** Waiting room: the QR code is shown, participants join. */
    case Lobby = 'lobby';

    /** A question is asked (answers close at the end of its time). */
    case Question = 'question';

    /** Final ranking. */
    case Ranking = 'ranking';

    /** The questions are corrected on the projected screen. */
    case Correction = 'correction';

    /** Over: nobody joins or answers any more. */
    case Closed = 'closed';

    /** Late participants may still join. */
    public function isJoinable(): bool
    {
        return $this === self::Lobby || $this === self::Question;
    }

    /** The right answers may be shown. */
    public function revealsAnswers(): bool
    {
        return $this === self::Ranking || $this === self::Correction || $this === self::Closed;
    }
}
