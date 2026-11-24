<?php

namespace App\Actions\Quiz;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Saves the questions received from the editor: kept questions are updated
 * (by id), new ones created, missing ones deleted, and all are numbered in
 * the order received.
 */
class SyncQuestions
{
    /**
     * @template TQuestion of Model
     *
     * @param  HasMany<TQuestion, covariant Model>  $relation
     * @param  list<array<string, mixed>>  $questions
     * @param  string  $imageColumn  where the question's image id goes
     */
    public function handle(HasMany $relation, array $questions, string $imageColumn): void
    {
        $existing = $relation->get()->keyBy('id');
        $kept = [];

        foreach ($questions as $index => $data) {
            $attributes = [
                'position' => $index + 1,
                'question' => $data['question'],
                $imageColumn => $data['image_id'] ?? null,
                'choice_a' => $data['choice_a'],
                'choice_b' => $data['choice_b'],
                'choice_c' => $data['choice_c'] ?? null,
                'choice_d' => $data['choice_d'] ?? null,
                'correct' => $data['correct'],
            ];

            $question = $existing->get($data['id'] ?? null);

            if ($question instanceof Model) {
                $question->update($attributes);
                $kept[] = $question->getKey();
            } else {
                $relation->create($attributes);
            }
        }

        $relation->getQuery()->whereKeyNot($kept)->whereIn('id', $existing->keys())->delete();
    }
}
