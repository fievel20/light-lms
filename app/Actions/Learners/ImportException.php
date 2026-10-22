<?php

namespace App\Actions\Learners;

use RuntimeException;

/**
 * The uploaded file cannot be imported; the message is shown to the trainer as is.
 */
class ImportException extends RuntimeException {}
