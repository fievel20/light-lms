<?php

namespace App\Enums;

enum AssessmentSource: string
{
    /** Self-assessment by the learner. */
    case Self = 'self';

    /** Validation by the trainer. */
    case Trainer = 'trainer';
}
