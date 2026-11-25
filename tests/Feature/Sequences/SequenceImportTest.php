<?php

use App\Models\Sequence;
use App\Models\User;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
});

it('crée une séquence importée, puis enregistre son texte final', function () {
    $response = $this->actingAs($this->trainer)
        ->postJson(route('sequences.import.store'), ['title' => 'Écrire une lettre', 'content' => '![Écran](images/a.png)'])
        ->assertCreated();

    $sequence = Sequence::query()->findOrFail($response->json('id'));
    $response->assertJsonPath('url', route('sequences.show', $sequence));

    $this->putJson(route('sequences.import.update', $sequence), ['content' => '![Écran](image:3)'])->assertOk();

    expect($sequence->fresh()->only(['title', 'content']))->toBe(['title' => 'Écrire une lettre', 'content' => '![Écran](image:3)']);
});

it('exige un titre et un texte', function () {
    $this->actingAs($this->trainer)
        ->postJson(route('sequences.import.store'), ['title' => '', 'content' => ''])
        ->assertJsonValidationErrors(['title', 'content']);
});

it('est réservé au formateur', function () {
    $sequence = Sequence::factory()->create();

    $this->actingAs(User::factory()->create());
    $this->postJson(route('sequences.import.store'), ['title' => 'A', 'content' => 'B'])->assertForbidden();
    $this->putJson(route('sequences.import.update', $sequence), ['content' => 'B'])->assertForbidden();
});
