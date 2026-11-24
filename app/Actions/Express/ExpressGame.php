<?php

namespace App\Actions\Express;

use App\Enums\ExpressStatus;
use App\Models\ExpressParticipant;
use App\Models\ExpressQuestion;
use App\Models\ExpressQuiz;
use App\Models\ExpressSession;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The rules of a live session: the trainer leads it step by step, the
 * participants join with a name and answer the current question.
 */
class ExpressGame
{
    /**
     * The quiz's open session, or a new one with a copy of its questions.
     */
    public function launch(ExpressQuiz $quiz): ExpressSession
    {
        $open = $quiz->sessions()->open()->latest('id')->first();

        if ($open instanceof ExpressSession) {
            return $open;
        }

        // A forgotten session is closed before the new one opens.
        $quiz->sessions()->where('status', '!=', ExpressStatus::Closed)->update([
            'status' => ExpressStatus::Closed,
            'finished_at' => now(),
        ]);

        return $quiz->sessions()->create([
            'code' => ExpressSession::newCode(),
            'status' => ExpressStatus::Lobby,
            'seconds_per_question' => $quiz->seconds_per_question,
            'questions' => $quiz->questions()->get()->map(fn (ExpressQuestion $question) => [
                'id' => $question->id,
                'question' => $question->question,
                'image_id' => $question->express_image_id,
                'choices' => $question->choices(),
                'correct' => $question->correct,
            ])->values()->all(),
        ]);
    }

    /**
     * Waiting room → first question; then question after question, and
     * the ranking after the last one.
     */
    public function next(ExpressSession $session): void
    {
        $status = $session->currentStatus();

        if ($status === ExpressStatus::Lobby) {
            $session->update(['started_at' => now()]);
        } elseif ($status !== ExpressStatus::Question) {
            return;
        }

        $position = ($session->current_position ?? 0) + 1;

        $session->update($position > $session->questionCount()
            ? ['status' => ExpressStatus::Ranking, 'question_started_at' => null]
            : ['status' => ExpressStatus::Question, 'current_position' => $position, 'question_started_at' => now()]);
    }

    public function correct(ExpressSession $session): void
    {
        if ($session->currentStatus() === ExpressStatus::Ranking) {
            $session->update(['status' => ExpressStatus::Correction]);
        }
    }

    public function close(ExpressSession $session): void
    {
        $session->update(['status' => ExpressStatus::Closed, 'finished_at' => $session->finished_at ?? now()]);
    }

    /**
     * Joins under a name not taken in the session; answers the participant
     * and the token their browser keeps.
     *
     * @return array{0: ExpressParticipant, 1: string}
     */
    public function join(ExpressSession $session, string $name): array
    {
        if (! $session->currentStatus()->isJoinable()) {
            throw ValidationException::withMessages(['name' => 'Ce quiz est terminé : on ne peut plus le rejoindre.']);
        }

        $name = Str::squish($name);
        $token = Str::random(40);

        try {
            $participant = $session->participants()->create([
                'name' => $name,
                'name_key' => Str::lower($name),
                'token_hash' => ExpressParticipant::hashToken($token),
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => 'Ce nom est déjà pris : ajoute une initiale, par exemple.']);
        }

        return [$participant, $token];
    }

    /**
     * Records the answer to the current question, once, while time is left;
     * a right answer earns 500 points plus the speed bonus.
     */
    public function answer(ExpressSession $session, ExpressParticipant $participant, int $position, string $choice): void
    {
        $question = $session->question($position);

        if ($position !== $session->current_position || $question === null || ! $session->acceptsAnswers()) {
            throw ValidationException::withMessages(['choice' => 'Temps écoulé : ta réponse n’a pas été prise.']);
        }

        if (! array_key_exists($choice, $question['choices'])) {
            throw ValidationException::withMessages(['choice' => 'Cette réponse n’existe pas.']);
        }

        $points = $choice === $question['correct'] ? $session->pointsNow() : 0;

        try {
            DB::transaction(function () use ($participant, $position, $choice, $points): void {
                $participant->answers()->create([
                    'position' => $position,
                    'choice' => $choice,
                    'points' => $points,
                    'answered_at' => now(),
                ]);
                $participant->increment('score', $points);
            });
        } catch (UniqueConstraintViolationException) {
            // Already answered (double tap): the first answer stays.
        }
    }
}
