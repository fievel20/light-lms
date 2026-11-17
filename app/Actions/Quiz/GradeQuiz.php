<?php

namespace App\Actions\Quiz;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Validation\ValidationException;

class GradeQuiz
{
    /**
     * Scores the answers (question id => letter, or null when time ran out)
     * against the quiz as it is now, and copies each question with its answer.
     *
     * @param  array<array-key, mixed>  $answers
     * @return array{score: int, total: int, questions: list<array{id: int, question: string, image_id: int|null, choices: array<string, string>, correct: string, answer: string|null}>}
     *
     * @throws ValidationException when the answers do not match the quiz (edited meanwhile)
     */
    public function handle(Quiz $quiz, array $answers): array
    {
        $questions = $quiz->questions()->get();
        $given = array_map('intval', array_keys($answers));
        sort($given);
        $expected = $questions->modelKeys();
        sort($expected);

        if ($given !== $expected) {
            throw ValidationException::withMessages([
                'answers' => 'Le quiz a été modifié pendant que tu répondais : recommence.',
            ]);
        }

        $score = 0;
        $copies = [];

        foreach ($questions as $question) {
            /** @var QuizQuestion $question */
            $answer = $answers[$question->id] ?? null;
            $choices = $question->choices();

            if ($answer !== null && (! is_string($answer) || ! array_key_exists($answer, $choices))) {
                throw ValidationException::withMessages([
                    'answers' => 'Une réponse ne correspond à aucun choix : recommence le quiz.',
                ]);
            }

            if ($answer === $question->correct) {
                $score++;
            }

            $copies[] = [
                'id' => $question->id,
                'question' => $question->question,
                'image_id' => $question->sequence_image_id,
                'choices' => $choices,
                'correct' => $question->correct,
                'answer' => $answer,
            ];
        }

        return ['score' => $score, 'total' => count($copies), 'questions' => $copies];
    }
}
