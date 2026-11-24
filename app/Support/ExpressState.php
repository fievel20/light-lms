<?php

namespace App\Support;

use App\Enums\ExpressStatus;
use App\Models\ExpressAnswer;
use App\Models\ExpressParticipant;
use App\Models\ExpressSession;
use Illuminate\Support\Collection;

/**
 * What the screens of a session read every second: the trainer's projected
 * screen, and each participant's phone. The right answers reach the phones
 * only with the ranking.
 */
class ExpressState
{
    public function __construct(private TextImage $textImage) {}

    /**
     * @return array<string, mixed>
     */
    public function host(ExpressSession $session): array
    {
        $status = $session->currentStatus();
        $participants = $session->participants()->orderBy('created_at')->orderBy('id')->get();
        $question = $status === ExpressStatus::Question ? $session->question($session->current_position) : null;

        return [
            ...$this->common($session),
            'participants' => $participants->map->only(['id', 'name'])->values(),
            'question' => $question === null ? null : [
                'question' => $question['question'],
                'image_url' => $this->hostImage($session, $question['image_id']),
                'choices' => $question['choices'],
            ],
            'answered' => $question === null ? 0 : $session->answers()->where('position', $session->current_position)->count(),
            'ranking' => $status->revealsAnswers() ? $this->ranking($participants) : null,
            'correction' => $status->revealsAnswers() ? $this->hostCorrection($session) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function player(ExpressSession $session, ?ExpressParticipant $me): array
    {
        $status = $session->currentStatus();
        $position = $session->current_position;
        $images = $this->textImage->available();
        $answers = $me?->answers()->get()->keyBy('position') ?? collect();
        $question = $me !== null && $status === ExpressStatus::Question ? $session->question($position) : null;

        return [
            ...$this->common($session),
            // Without GD or a font, the text is sent, marked "do not translate".
            'text_mode' => $images ? 'image' : 'text',
            'me' => $me?->only(['name']),
            'question' => $question === null || $position === null ? null : [
                ...$this->playerQuestion($session, $position, $question, $images),
                'answer' => $answers->get($position)?->choice,
            ],
            'result' => $me !== null && $status->revealsAnswers() ? $this->result($session, $me) : null,
            'correction' => $me !== null && $status->revealsAnswers()
                ? array_map(fn (int $index) => [
                    ...$this->playerQuestion($session, $index + 1, $session->questions[$index], $images),
                    'correct' => $session->questions[$index]['correct'],
                    'answer' => $answers->get($index + 1)?->choice,
                    'points' => $answers->get($index + 1)->points ?? 0,
                ], array_keys($session->questions))
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function common(ExpressSession $session): array
    {
        return [
            'status' => $session->currentStatus()->value,
            'code' => $session->code,
            'title' => $session->quiz?->title,
            'position' => $session->current_position,
            'total' => $session->questionCount(),
            'seconds' => $session->seconds_per_question,
            'remaining_ms' => $session->remainingMs(),
        ];
    }

    /**
     * A question on a phone: drawn as images, the text never sent (or sent
     * as text when images cannot be drawn on this server).
     *
     * @param  array{id: int, question: string, image_id: int|null, choices: array<string, string>, correct: string}  $question
     * @return array<string, mixed>
     */
    private function playerQuestion(ExpressSession $session, int $position, array $question, bool $images): array
    {
        $text = fn (string $part) => route('express.play.text', [$session->code, $position, $part]);

        return [
            'position' => $position,
            'text' => $images ? null : $question['question'],
            'text_url' => $images ? $text('q') : null,
            'image_url' => $question['image_id'] === null ? null : route('express.play.picture', [$session->code, $position]),
            'choices' => array_map(fn (string $letter) => [
                'letter' => $letter,
                'text' => $images ? null : $question['choices'][$letter],
                'text_url' => $images ? $text($letter) : null,
            ], array_keys($question['choices'])),
        ];
    }

    /**
     * Highest score first; equal scores share a rank.
     *
     * @param  Collection<int, ExpressParticipant>  $participants
     * @return list<array{id: int, name: string, score: int, rank: int}>
     */
    private function ranking(Collection $participants): array
    {
        $ranking = [];
        $rank = 0;
        $previous = null;

        foreach ($participants->sortBy([['score', 'desc'], ['name_key', 'asc']])->values() as $index => $participant) {
            if ($participant->score !== $previous) {
                $rank = $index + 1;
                $previous = $participant->score;
            }

            $ranking[] = ['id' => $participant->id, 'name' => $participant->name, 'score' => $participant->score, 'rank' => $rank];
        }

        return $ranking;
    }

    /**
     * @return array{rank: int, of: int, score: int}
     */
    private function result(ExpressSession $session, ExpressParticipant $me): array
    {
        return [
            'rank' => $session->participants()->where('score', '>', $me->score)->count() + 1,
            'of' => $session->participants()->count(),
            'score' => $me->score,
        ];
    }

    /**
     * Each question with its right answer and how many chose each tile.
     *
     * @return list<array<string, mixed>>
     */
    private function hostCorrection(ExpressSession $session): array
    {
        $counts = $session->answers()
            ->get(['position', 'choice'])
            ->groupBy('position')
            ->map(fn (Collection $answers) => $answers->countBy(fn (ExpressAnswer $answer) => $answer->choice));

        return array_map(fn (int $index) => [
            'position' => $index + 1,
            'question' => $session->questions[$index]['question'],
            'image_url' => $this->hostImage($session, $session->questions[$index]['image_id']),
            'choices' => $session->questions[$index]['choices'],
            'correct' => $session->questions[$index]['correct'],
            'counts' => $counts->get($index + 1)?->all() ?? [],
        ], array_keys($session->questions));
    }

    private function hostImage(ExpressSession $session, ?int $imageId): ?string
    {
        return $imageId === null ? null : route('express-quizzes.images.show', [$session->express_quiz_id, $imageId]);
    }
}
