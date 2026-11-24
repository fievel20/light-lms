<?php

namespace App\Models\Concerns;

use App\Models\QuizQuestion;

/**
 * A question with two to four choices (choice_a, choice_b, then optional
 * choice_c and choice_d) and the letter of the right one (correct).
 */
trait HasChoices
{
    /**
     * The choices that are filled in, by letter: ['a' => 'Sydney', …].
     *
     * @return array<string, string>
     */
    public function choices(): array
    {
        $choices = [];

        foreach (QuizQuestion::LETTERS as $letter) {
            $text = $this->{"choice_{$letter}"};

            if (is_string($text) && $text !== '') {
                $choices[$letter] = $text;
            }
        }

        return $choices;
    }
}
