<?php

use App\Models\Sequence;
use App\Models\SequenceImage;
use App\Models\User;
use App\Support\MarkdownRenderer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->trainer = User::factory()->trainer()->create();
    $this->sequence = Sequence::factory()->create();
});

it('importe une image et renvoie la balise Markdown à insérer', function () {
    $response = $this->actingAs($this->trainer)
        ->postJson(route('sequences.images.store', $this->sequence), [
            'image' => UploadedFile::fake()->image('Boîte de réception.png', 800, 600),
        ])
        ->assertCreated();

    $image = SequenceImage::sole();

    expect($image->sequence_id)->toBe($this->sequence->id)
        ->and($image->original_name)->toBe('Boîte de réception.png')
        ->and($image->mime_type)->toBe('image/png');
    Storage::disk('local')->assertExists($image->path);
    $response->assertJson(['markdown' => "![Boîte de réception](image:{$image->id})"]);
});

it('refuse ce qui n’est pas une image, ou une image trop lourde', function (UploadedFile $file) {
    $this->actingAs($this->trainer)
        ->postJson(route('sequences.images.store', $this->sequence), ['image' => $file])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    expect(SequenceImage::count())->toBe(0);
})->with([
    'pdf' => fn () => UploadedFile::fake()->create('atelier.pdf', 100, 'application/pdf'),
    'svg' => fn () => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
    'trop lourde' => fn () => UploadedFile::fake()->image('capture.png')->size(5121),
]);

it('sert les images au formateur seulement, et dans leur séquence', function () {
    $image = SequenceImage::factory()->for($this->sequence)->create();
    $other = Sequence::factory()->create();

    $this->actingAs($this->trainer)
        ->get(route('sequences.images.show', [$this->sequence, $image]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    $this->actingAs($this->trainer)
        ->get(route('sequences.images.show', [$other, $image]))
        ->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->get(route('sequences.images.show', [$this->sequence, $image]))
        ->assertForbidden();

    auth()->logout();
    $this->get(route('sequences.images.show', [$this->sequence, $image]))
        ->assertRedirect(route('login'));
});

it('remplace les références image: par l’adresse protégée, pour les images de la séquence seulement', function () {
    $image = SequenceImage::factory()->for($this->sequence)->create();
    $foreign = SequenceImage::factory()->create();

    $html = app(MarkdownRenderer::class)->toHtml(
        "![Capture](image:{$image->id})\n\n![Autre](image:{$foreign->id})",
        $this->sequence,
    );

    expect($html)
        ->toContain('src="'.route('sequences.images.show', [$this->sequence, $image]).'"')
        ->toContain('alt="Capture"')
        ->not->toContain("image:{$foreign->id}")
        ->not->toContain((string) route('sequences.images.show', [$foreign->sequence_id, $foreign]));
});

it('rend les images dans l’aperçu de la séquence en cours de modification', function () {
    $image = SequenceImage::factory()->for($this->sequence)->create();

    $this->actingAs($this->trainer)
        ->postJson(route('sequences.preview'), [
            'content' => "![Capture](image:{$image->id})",
            'sequence_id' => $this->sequence->id,
        ])
        ->assertOk()
        ->assertJsonPath('html', fn (string $html) => str_contains($html, route('sequences.images.show', [$this->sequence, $image])));
});

it('efface les fichiers des images avec la séquence', function () {
    $image = SequenceImage::factory()->for($this->sequence)->create();
    Storage::disk('local')->assertExists($image->path);

    $this->actingAs($this->trainer)->delete(route('sequences.destroy', $this->sequence));

    Storage::disk('local')->assertMissing($image->path);
    expect(SequenceImage::count())->toBe(0);
});

it('duplique une séquence avec ses images', function () {
    $image = SequenceImage::factory()->for($this->sequence)->create();
    $this->sequence->update([
        'title' => 'Google Lens 1',
        'content' => "1. Installe Google Lens\n\n![Menu](image:{$image->id})",
    ]);

    $response = $this->actingAs($this->trainer)
        ->post(route('sequences.duplicate', $this->sequence));

    $copy = Sequence::query()->latest('id')->first();
    $copiedImage = $copy->images()->sole();

    $response->assertRedirect(route('sequences.edit', $copy));
    expect($copy->title)->toBe('Google Lens 1 (copie)')
        ->and($copy->content)->toBe("1. Installe Google Lens\n\n![Menu](image:{$copiedImage->id})")
        ->and($copiedImage->path)->not->toBe($image->path)
        ->and($copiedImage->original_name)->toBe($image->original_name);
    Storage::disk('local')->assertExists($copiedImage->path);
    Storage::disk('local')->assertExists($image->path);
});

it('réserve l’import, l’affichage et la duplication au formateur', function () {
    $this->actingAs(User::factory()->create());

    $this->postJson(route('sequences.images.store', $this->sequence), [
        'image' => UploadedFile::fake()->image('x.png'),
    ])->assertForbidden();
    $this->post(route('sequences.duplicate', $this->sequence))->assertForbidden();
});
