<?php

namespace App\Support;

use App\Models\Promotion;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Sequence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;

/**
 * What the quiz pages receive. The right answers only leave the server for
 * the trainer's test, or in the correction of a finished attempt.
 */
class QuizPresenter
{
    /**
     * @return array{title: string, seconds_per_question: int|null, questions: array<int, array{id: int, question: string, image_url: string|null, choices: array<string, string>, correct?: string}>}
     */
    public function forPlay(Quiz $quiz, bool $withAnswers): array
    {
        $sequence = $quiz->sequence;

        return [
            'title' => $quiz->title,
            'seconds_per_question' => $quiz->seconds_per_question,
            'questions' => $quiz->questions()->get()->map(fn (QuizQuestion $question) => [
                'id' => $question->id,
                'question' => $question->question,
                'image_url' => $this->imageUrl($sequence, $question->sequence_image_id),
                'choices' => $question->choices(),
                ...($withAnswers ? ['correct' => $question->correct] : []),
            ])->values()->all(),
        ];
    }

    /**
     * Summary shown next to the button in the sequence.
     *
     * @return array{title: string, questions_count: int, seconds_per_question: int|null}|null
     */
    public function summary(Sequence $sequence): ?array
    {
        $quiz = $sequence->quiz()->withCount('questions')->first();

        if ($quiz === null || $quiz->questions_count === 0) {
            return null;
        }

        return [
            'title' => $quiz->title,
            'questions_count' => (int) $quiz->questions_count,
            'seconds_per_question' => $quiz->seconds_per_question,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attempt(QuizAttempt $attempt, Sequence $sequence): array
    {
        return [
            'id' => $attempt->id,
            'score' => $attempt->score,
            'total' => $attempt->total,
            'finished_at' => $attempt->finished_at,
            'questions' => array_map(fn (array $question) => [
                ...$question,
                'image_url' => $this->imageUrl($sequence, $question['image_id']),
            ], $attempt->questions),
        ];
    }

    /**
     * The learner's attempts in this promotion, oldest first.
     *
     * @param  Closure(QuizAttempt): string  $url
     * @return array<int, array{id: int, score: int, total: int, finished_at: CarbonImmutable, url: string}>
     */
    public function history(Quiz $quiz, Promotion $promotion, User $learner, Closure $url): array
    {
        return $quiz->attempts()
            ->where('promotion_id', $promotion->id)
            ->where('user_id', $learner->id)
            ->orderBy('finished_at')
            ->orderBy('id')
            ->get(['id', 'score', 'total', 'finished_at'])
            ->map(fn (QuizAttempt $attempt) => [
                'id' => $attempt->id,
                'score' => $attempt->score,
                'total' => $attempt->total,
                'finished_at' => $attempt->finished_at,
                'url' => $url($attempt),
            ])
            ->values()
            ->all();
    }

    private function imageUrl(Sequence $sequence, ?int $imageId): ?string
    {
        return $imageId === null ? null : route('sequences.images.show', [$sequence, $imageId]);
    }
}
