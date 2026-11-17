<?php

use App\Models\Quiz;
use App\Models\Sequence;
use App\Models\SequenceImage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->trainer = User::factory()->trainer()->create();
    $this->sequence = Sequence::factory()->create(['title' => 'Google Lens 1']);
});

/**
 * @return array<string, mixed>
 */
function quizPayload(array $questions, array $overrides = []): array
{
    return ['title' => 'Test de positionnement', 'seconds_per_question' => 30, 'questions' => $questions, ...$overrides];
}

function question(string $text = 'Quelle est la capitale de l’Australie ?', array $overrides = []): array
{
    return [
        'question' => $text,
        'choice_a' => 'Sydney',
        'choice_b' => 'Melbourne',
        'choice_c' => 'Canberra',
        'choice_d' => 'Perth',
        'correct' => 'c',
        ...$overrides,
    ];
}

it('est réservé au formateur', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('sequences.quiz.edit', $this->sequence))->assertForbidden();
    $this->put(route('sequences.quiz.update', $this->sequence), quizPayload([question()]))->assertForbidden();
    $this->delete(route('sequences.quiz.destroy', $this->sequence))->assertForbidden();
});

it('affiche l’éditeur, vide tant que la séquence n’a pas de quiz', function () {
    $this->actingAs($this->trainer)
        ->get(route('sequences.quiz.edit', $this->sequence))
        ->assertInertia(fn (Assert $page) => $page
            ->component('sequences/Quiz')
            ->where('sequence.title', 'Google Lens 1')
            ->where('quiz', null));
});

it('crée le quiz et ses questions dans l’ordre', function () {
    $this->actingAs($this->trainer)
        ->put(route('sequences.quiz.update', $this->sequence), quizPayload([
            question(),
            question('Vrai ou faux : la Terre est ronde.', ['choice_a' => 'Vrai', 'choice_b' => 'Faux', 'choice_c' => null, 'choice_d' => null, 'correct' => 'a']),
        ]))
        ->assertRedirect(route('sequences.quiz.edit', $this->sequence));

    $quiz = Quiz::sole();
    expect($quiz->sequence_id)->toBe($this->sequence->id)
        ->and($quiz->title)->toBe('Test de positionnement')
        ->and($quiz->seconds_per_question)->toBe(30)
        ->and($quiz->questions->pluck('question')->all())->toBe(['Quelle est la capitale de l’Australie ?', 'Vrai ou faux : la Terre est ronde.'])
        ->and($quiz->questions[1]->choices())->toBe(['a' => 'Vrai', 'b' => 'Faux']);
});

it('met à jour les questions gardées, ajoute les nouvelles et retire les autres', function () {
    $this->actingAs($this->trainer)->put(route('sequences.quiz.update', $this->sequence), quizPayload([question('Q1'), question('Q2'), question('Q3')]));
    [$q1, $q2] = Quiz::sole()->questions;

    $this->put(route('sequences.quiz.update', $this->sequence), quizPayload([
        question('Q2 corrigée', ['id' => $q2->id]),
        question('Nouvelle'),
        question('Q1', ['id' => $q1->id]),
    ], ['seconds_per_question' => null]));

    $quiz = Quiz::sole()->fresh();
    expect($quiz->seconds_per_question)->toBeNull()
        ->and($quiz->questions->pluck('question')->all())->toBe(['Q2 corrigée', 'Nouvelle', 'Q1'])
        ->and($quiz->questions[0]->id)->toBe($q2->id)
        ->and($quiz->questions[2]->id)->toBe($q1->id);
});

it('ajoute une image de la séquence à une question', function () {
    $image = SequenceImage::factory()->for($this->sequence)->create();

    $this->actingAs($this->trainer)->put(route('sequences.quiz.update', $this->sequence), quizPayload([question(overrides: ['image_id' => $image->id])]));

    $this->get(route('sequences.quiz.edit', $this->sequence))
        ->assertInertia(fn (Assert $page) => $page
            ->where('quiz.questions.0.image_id', $image->id)
            ->where('quiz.questions.0.image_url', route('sequences.images.show', [$this->sequence, $image])));
});

it('refuse une question incohérente', function (array $question, string $error) {
    $this->actingAs($this->trainer)
        ->put(route('sequences.quiz.update', $this->sequence), quizPayload([question('Bonne'), $question]))
        ->assertSessionHasErrors($error);

    expect(Quiz::count())->toBe(0);
})->with([
    'bonne réponse vide' => [question(overrides: ['choice_d' => null, 'correct' => 'd']), 'questions.1.correct'],
    'D sans C' => [question(overrides: ['choice_c' => null]), 'questions.1.choice_d'],
    'une seule réponse' => [question(overrides: ['choice_b' => '', 'choice_c' => null, 'choice_d' => null, 'correct' => 'a']), 'questions.1.choice_b'],
    'énoncé vide' => [question(''), 'questions.1.question'],
    'lettre inconnue' => [question(overrides: ['correct' => 'e']), 'questions.1.correct'],
]);

it('refuse l’image d’une autre séquence et un temps hors limites', function () {
    $foreign = SequenceImage::factory()->create();

    $this->actingAs($this->trainer)
        ->put(route('sequences.quiz.update', $this->sequence), quizPayload([question(overrides: ['image_id' => $foreign->id])], ['seconds_per_question' => 5]))
        ->assertSessionHasErrors(['questions.0.image_id', 'seconds_per_question']);
});

it('supprime le quiz', function () {
    $this->actingAs($this->trainer)->put(route('sequences.quiz.update', $this->sequence), quizPayload([question()]));

    $this->delete(route('sequences.quiz.destroy', $this->sequence))
        ->assertRedirect(route('sequences.show', $this->sequence));

    expect(Quiz::count())->toBe(0)
        ->and(DB::table('quiz_questions')->count())->toBe(0);
});
