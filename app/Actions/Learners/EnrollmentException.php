<?php

namespace App\Actions\Learners;

use RuntimeException;

/**
 * A learner cannot be enrolled; the message is shown to the trainer as is.
 */
class EnrollmentException extends RuntimeException {}
