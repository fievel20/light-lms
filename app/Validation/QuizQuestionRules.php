<?php

namespace App\Validation;

use App\Models\QuizQuestion;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

/**
 * The questions of a quiz, saved all at once by the editor: shared by the
 * quiz of a sequence and the quiz express.
 */
class QuizQuestionRules
{
    /**
     * @return array<string, array<mixed>>
     */
    public static function rules(int $maxQuestions, Exists $image): array
    {
        return [
            'questions' => ['present', 'array', 'list', 'max:'.$maxQuestions],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.question' => ['required', 'string', 'max:1000'],
            'questions.*.image_id' => ['nullable', 'integer', $image],
            'questions.*.choice_a' => ['required', 'string', 'max:255'],
            'questions.*.choice_b' => ['required', 'string', 'max:255'],
            'questions.*.choice_c' => ['nullable', 'string', 'max:255'],
            'questions.*.choice_d' => ['nullable', 'string', 'max:255'],
            'questions.*.correct' => ['required', Rule::in(QuizQuestion::LETTERS)],
        ];
    }

    /**
     * C before D, and the right answer among the filled-in choices.
     *
     * @param  array<array-key, mixed>  $questions
     */
    public static function check(array $questions, Validator $validator): void
    {
        foreach ($questions as $index => $question) {
            if (! is_array($question)) {
                continue;
            }

            if (filled($question['choice_d'] ?? null) && blank($question['choice_c'] ?? null)) {
                $validator->errors()->add("questions.{$index}.choice_d", 'Remplis la réponse C avant la réponse D.');
            }

            $correct = $question['correct'] ?? null;

            if (is_string($correct) && in_array($correct, QuizQuestion::LETTERS, true) && blank($question["choice_{$correct}"] ?? null)) {
                $validator->errors()->add("questions.{$index}.correct", 'La bonne réponse doit être une des réponses remplies.');
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'questions.*.question' => 'énoncé',
            'questions.*.image_id' => 'image',
            'questions.*.choice_a' => 'réponse A',
            'questions.*.choice_b' => 'réponse B',
            'questions.*.choice_c' => 'réponse C',
            'questions.*.choice_d' => 'réponse D',
            'questions.*.correct' => 'bonne réponse',
        ];
    }
}
