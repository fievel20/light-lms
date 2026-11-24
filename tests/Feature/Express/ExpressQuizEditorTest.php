<?php

use App\Models\ExpressImage;
use App\Models\ExpressQuiz;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->trainer = User::factory()->trainer()->create();
    $this->actingAs($this->trainer);
});

function expressQuestion(string $text = 'Capitale de l’Australie ?', array $overrides = []): array
{
    return ['question' => $text, 'choice_a' => 'Sydney', 'choice_b' => 'Canberra', 'choice_c' => null, 'choice_d' => null, 'correct' => 'b', ...$overrides];
}

it('est réservé au formateur', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('express-quizzes.index'))->assertForbidden();
    $this->post(route('express-quizzes.store'))->assertForbidden();
});

it('crée un quiz express vide, avec 20 secondes par question, et ouvre son éditeur', function () {
    $this->post(route('express-quizzes.store'))->assertRedirect();

    $quiz = ExpressQuiz::sole();
    expect($quiz->seconds_per_question)->toBe(20);

    $this->get(route('express-quizzes.edit', $quiz))
        ->assertInertia(fn (Assert $page) => $page
            ->component('express/Edit')
            ->where('quiz.title', 'Nouveau quiz express')
            ->has('quiz.questions', 0));
});

it('liste les quiz express avec leur nombre de questions', function () {
    ExpressQuiz::factory()->withQuestions(3)->create(['title' => 'Culture générale']);

    $this->get(route('express-quizzes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('express/Index')
            ->where('quizzes.0.title', 'Culture générale')
            ->where('quizzes.0.questions_count', 3));
});

it('enregistre le quiz entier : réglages, questions ajoutées, modifiées et supprimées', function () {
    $quiz = ExpressQuiz::factory()->withQuestions(2)->create();
    [$kept, $removed] = $quiz->questions()->get()->all();

    $this->put(route('express-quizzes.update', $quiz), [
        'title' => 'Culture générale',
        'seconds_per_question' => 15,
        'questions' => [
            expressQuestion('Nouvelle ?'),
            expressQuestion('Modifiée ?', ['id' => $kept->id, 'choice_c' => 'Perth', 'correct' => 'c']),
        ],
    ])->assertRedirect(route('express-quizzes.edit', $quiz));

    $questions = $quiz->questions()->get();
    expect($quiz->fresh()->only(['title', 'seconds_per_question']))->toBe(['title' => 'Culture générale', 'seconds_per_question' => 15])
        ->and($questions->pluck('question')->all())->toBe(['Nouvelle ?', 'Modifiée ?'])
        ->and($questions[1]->id)->toBe($kept->id)
        ->and($questions[1]->choices())->toBe(['a' => 'Sydney', 'b' => 'Canberra', 'c' => 'Perth'])
        ->and($quiz->questions()->whereKey($removed->id)->exists())->toBeFalse();
});

it('exige un temps par question et une bonne réponse remplie', function () {
    $quiz = ExpressQuiz::factory()->create();

    $this->put(route('express-quizzes.update', $quiz), [
        'title' => 'Quiz',
        'seconds_per_question' => null,
        'questions' => [expressQuestion(overrides: ['correct' => 'd'])],
    ])->assertSessionHasErrors(['seconds_per_question', 'questions.0.correct']);
});

it('ajoute une image à une question, servie au formateur', function () {
    $quiz = ExpressQuiz::factory()->create();

    $id = $this->post(route('express-quizzes.images.store', $quiz), ['image' => UploadedFile::fake()->image('logo.png')])
        ->assertCreated()
        ->json('id');

    $this->put(route('express-quizzes.update', $quiz), [
        'title' => 'Logos',
        'seconds_per_question' => 20,
        'questions' => [expressQuestion('Quel logo ?', ['image_id' => $id])],
    ])->assertSessionHasNoErrors();

    $image = ExpressImage::query()->findOrFail($id);
    Storage::disk('local')->assertExists($image->path);
    $this->get(route('express-quizzes.images.show', [$quiz, $image]))->assertOk();

    // The image of another quiz is refused.
    $other = ExpressQuiz::factory()->create();
    $this->put(route('express-quizzes.update', $other), [
        'title' => 'Autre',
        'seconds_per_question' => 20,
        'questions' => [expressQuestion('Quel logo ?', ['image_id' => $id])],
    ])->assertSessionHasErrors('questions.0.image_id');
});

it('duplique un quiz express avec ses questions et ses images', function () {
    $quiz = ExpressQuiz::factory()->withQuestions(2)->create(['title' => 'Logos']);
    $id = $this->post(route('express-quizzes.images.store', $quiz), ['image' => UploadedFile::fake()->image('logo.png')])->json('id');
    $quiz->questions()->first()?->update(['express_image_id' => $id]);

    $this->post(route('express-quizzes.duplicate', $quiz))->assertRedirect();

    $copy = ExpressQuiz::query()->latest('id')->firstOrFail();
    $copiedImage = $copy->questions()->first()?->image;

    expect($copy->title)->toBe('Logos (copie)')
        ->and($copy->questions()->count())->toBe(2)
        ->and($copiedImage)->not->toBeNull()
        ->and($copiedImage?->id)->not->toBe($id);
    Storage::disk('local')->assertExists($copiedImage->path);
});

it('supprime un quiz express et ses images', function () {
    $quiz = ExpressQuiz::factory()->create();
    $id = $this->post(route('express-quizzes.images.store', $quiz), ['image' => UploadedFile::fake()->image('logo.png')])->json('id');
    $path = ExpressImage::query()->findOrFail($id)->path;

    $this->delete(route('express-quizzes.destroy', $quiz))->assertRedirect(route('express-quizzes.index'));

    expect(ExpressQuiz::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($path);
});
