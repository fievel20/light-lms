<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Sequence;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The whole quiz at once: its settings and its questions in order (the
 * editor keeps them on the page, JSON imports included, until saved).
 */
class QuizRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Sequence $sequence */
        $sequence = $this->route('sequence');

        return [
            'title' => ['required', 'string', 'max:255'],
            'seconds_per_question' => ['nullable', 'integer', 'min:'.Quiz::MIN_SECONDS, 'max:'.Quiz::MAX_SECONDS],
            'questions' => ['present', 'array', 'list', 'max:'.Quiz::MAX_QUESTIONS],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.question' => ['required', 'string', 'max:1000'],
            'questions.*.image_id' => ['nullable', 'integer', Rule::exists('sequence_images', 'id')->where('sequence_id', $sequence->id)],
            'questions.*.choice_a' => ['required', 'string', 'max:255'],
            'questions.*.choice_b' => ['required', 'string', 'max:255'],
            'questions.*.choice_c' => ['nullable', 'string', 'max:255'],
            'questions.*.choice_d' => ['nullable', 'string', 'max:255'],
            'questions.*.correct' => ['required', Rule::in(QuizQuestion::LETTERS)],
        ];
    }

    /**
     * The right answer must be one of the filled-in choices.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var list<array<string, mixed>> $questions */
            $questions = (array) $this->input('questions', []);

            foreach ($questions as $index => $question) {
                if (filled($question['choice_d'] ?? null) && blank($question['choice_c'] ?? null)) {
                    $validator->errors()->add("questions.{$index}.choice_d", 'Remplis la réponse C avant la réponse D.');
                }

                $correct = $question['correct'] ?? null;

                if (is_string($correct) && in_array($correct, QuizQuestion::LETTERS, true) && blank($question["choice_{$correct}"] ?? null)) {
                    $validator->errors()->add("questions.{$index}.correct", 'La bonne réponse doit être une des réponses remplies.');
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'titre',
            'seconds_per_question' => 'temps par question',
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
