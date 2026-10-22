<?php

namespace App\Actions\Learners;

enum EnrollmentResult
{
    /** A new learner account was created and invited. */
    case Created;

    /** An existing learner was added to the promotion. */
    case Enrolled;
}
