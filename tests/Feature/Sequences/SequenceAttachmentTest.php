<?php

use App\Models\LearningPath;
use App\Models\Promotion;
use App\Models\Sequence;
use App\Models\SequenceAttachment;
use App\Models\User;
use App\Support\MarkdownRenderer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->trainer = User::factory()->trainer()->create();
    $this->sequence = Sequence::factory()->create();
});

function attachToSequence(Sequence $sequence, string $name = 'facture.pdf'): SequenceAttachment
{
    $id = test()->actingAs(test()->trainer)
        ->postJson(route('sequences.attachments.store', $sequence), ['file' => UploadedFile::fake()->create($name, 120)])
        ->assertCreated()
        ->json('id');

    return SequenceAttachment::query()->findOrFail($id);
}

it('ajoute une pièce jointe et renvoie le lien Markdown à insérer', function () {
    $attachment = attachToSequence($this->sequence, 'Feuille de soins.pdf');

    expect($attachment->sequence_id)->toBe($this->sequence->id)
        ->and($attachment->mime_type)->toBe('application/pdf')
        ->and($attachment->markdown())->toBe("[Feuille de soins.pdf](fichier:{$attachment->id})");
    Storage::disk('local')->assertExists($attachment->path);
});

it('refuse les fichiers qui pourraient s’exécuter, et ceux de plus de 5 Mo', function (UploadedFile $file) {
    $this->actingAs($this->trainer)
        ->postJson(route('sequences.attachments.store', $this->sequence), ['file' => $file])
        ->assertJsonValidationErrors('file');

    expect(SequenceAttachment::count())->toBe(0);
})->with([
    'script' => fn () => UploadedFile::fake()->create('outil.js', 10),
    'page web' => fn () => UploadedFile::fake()->create('page.html', 10),
    'svg' => fn () => UploadedFile::fake()->create('logo.svg', 10),
    'trop lourd' => fn () => UploadedFile::fake()->create('gros.pdf', 5121),
]);

it('transforme les liens fichier: en téléchargement, pour les fichiers de la séquence seulement', function () {
    $attachment = attachToSequence($this->sequence);
    $foreign = attachToSequence(Sequence::factory()->create(), 'autre.pdf');

    $html = app(MarkdownRenderer::class)->toHtml(
        "[La facture](fichier:{$attachment->id}) et [l’autre](fichier:{$foreign->id})",
        $this->sequence,
    );

    expect($html)->toContain('href="'.route('sequences.attachments.show', [$this->sequence, $attachment]).'"')
        ->toContain('target="_blank"')
        ->toContain('l’autre')
        ->not->toContain("fichier:{$foreign->id}");
});

it('sert les pièces jointes au formateur et aux apprenants de la séquence ouverte', function () {
    $pdf = attachToSequence($this->sequence);
    $docx = attachToSequence($this->sequence, 'courrier.docx');
    $learner = User::factory()->create();

    $this->get(route('sequences.attachments.show', [$this->sequence, $pdf]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename=facture.pdf');

    $this->actingAs($learner)->get(route('sequences.attachments.show', [$this->sequence, $pdf]))->assertNotFound();

    $promotion = Promotion::factory()->create();
    $promotion->learners()->attach($learner);
    $path = LearningPath::factory()->create();
    $path->sequences()->attach($this->sequence);
    $promotion->learningPaths()->attach($path);
    $promotion->sequenceReleases()->create(['sequence_id' => $this->sequence->id, 'released_at' => now()]);

    $this->get(route('sequences.attachments.show', [$this->sequence, $docx]))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename=courrier.docx');
    $this->get(route('sequences.attachments.show', [Sequence::factory()->create(), $docx]))->assertNotFound();
});

it('copie les pièces jointes avec la séquence, et les efface avec elle', function () {
    $attachment = attachToSequence($this->sequence);
    $this->sequence->update(['content' => "Voir [la facture](fichier:{$attachment->id})."]);

    $this->post(route('sequences.duplicate', $this->sequence))->assertRedirect();
    $copy = Sequence::query()->latest('id')->firstOrFail();
    $copied = $copy->attachments()->sole();

    expect($copy->content)->toBe("Voir [la facture](fichier:{$copied->id}).");
    Storage::disk('local')->assertExists($copied->path);

    $this->delete(route('sequences.destroy', $this->sequence));
    Storage::disk('local')->assertMissing($attachment->path);
    Storage::disk('local')->assertExists($copied->path);
});
