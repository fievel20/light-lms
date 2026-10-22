<?php

use App\Models\Promotion;
use App\Models\User;
use App\Notifications\LearnerInvitation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->trainer = User::factory()->trainer()->create(['email' => 'formateur@example.com']);
    $this->promotion = Promotion::factory()->create();
});

function csvFile(string $content, string $name = 'apprenants.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

function importCsv(string $content, ?Promotion $promotion = null, string $name = 'apprenants.csv')
{
    return test()->actingAs(test()->trainer)->post(
        route('promotions.learners.import', $promotion ?? test()->promotion),
        ['file' => csvFile($content, $name)],
    );
}

it('crée, inscrit et invite les apprenants du fichier', function () {
    importCsv("nom;prenom;email\nDupont;Marie;marie@example.com\nMartin;Paul;paul@example.com\n")
        ->assertRedirect(route('promotions.show', $this->promotion))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('import', ['created' => 2, 'enrolled' => 0, 'errors' => []]);

    $marie = User::where('email', 'marie@example.com')->sole();

    expect($marie->first_name)->toBe('Marie')
        ->and($marie->last_name)->toBe('Dupont')
        ->and($this->promotion->learners()->count())->toBe(2);

    Notification::assertSentTimes(LearnerInvitation::class, 2);
});

it('accepte la virgule, les en-têtes accentués, le BOM et les fins de ligne Windows', function () {
    importCsv("\u{FEFF}Prénom,Nom,E-mail\r\nMarie,Dupont,marie@example.com\r\n")
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('import.created', 1);

    expect(User::where('email', 'marie@example.com')->sole()->last_name)->toBe('Dupont');
});

it('lit les fichiers enregistrés par Excel en Windows-1252', function () {
    importCsv(mb_convert_encoding("nom;prénom;email\nLefèvre;Hélène;helene@example.com\n", 'Windows-1252', 'UTF-8'))
        ->assertSessionHasNoErrors();

    $helene = User::where('email', 'helene@example.com')->sole();

    expect($helene->first_name)->toBe('Hélène')
        ->and($helene->last_name)->toBe('Lefèvre');
});

it('inscrit les apprenants existants et signale les lignes en erreur', function () {
    $existing = User::factory()->create(['email' => 'paul@example.com']);
    $already = User::factory()->create(['email' => 'deja@example.com']);
    $this->promotion->learners()->attach($already);

    importCsv(implode("\n", [
        'nom;prenom;email',
        'Dupont;Marie;marie@example.com',
        'Martin;Paul;paul@example.com',
        'Déjà;Là;deja@example.com',
        'Sans;Email;',
        'Mauvais;Email;pas-un-email',
        ';Prénom;sans.nom@example.com',
        'Formateur;Le;formateur@example.com',
        'Dupont;Marie;MARIE@example.com',
        '',
    ]))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('import', [
            'created' => 1,
            'enrolled' => 1,
            'errors' => [
                ['line' => 4, 'message' => 'Cet apprenant fait déjà partie de la promotion.'],
                ['line' => 5, 'message' => 'Le champ adresse e-mail est obligatoire.'],
                ['line' => 6, 'message' => 'Le champ adresse e-mail doit être une adresse e-mail valide.'],
                ['line' => 7, 'message' => 'Le champ nom est obligatoire.'],
                ['line' => 8, 'message' => 'Cette adresse appartient au compte formateur.'],
                ['line' => 9, 'message' => 'Cet apprenant fait déjà partie de la promotion.'],
            ],
        ]);

    expect($this->promotion->learners()->count())->toBe(3)
        ->and($existing->promotions()->count())->toBe(1);

    Notification::assertSentTimes(LearnerInvitation::class, 1);
});

it('refuse un fichier sans les colonnes attendues', function () {
    importCsv("name;email\nMarie;marie@example.com\n")
        ->assertSessionHasErrors(['file' => 'Le fichier doit contenir les colonnes nom, prenom et email.']);

    expect(User::count())->toBe(1);
});

it('refuse un fichier sans apprenant', function () {
    importCsv("nom;prenom;email\n")
        ->assertSessionHasErrors(['file' => 'Le fichier ne contient aucun apprenant.']);
});

it('valide le fichier envoyé', function () {
    $this->actingAs($this->trainer)
        ->post(route('promotions.learners.import', $this->promotion), [])
        ->assertSessionHasErrors('file');

    importCsv('%PDF-1.4', null, 'apprenants.pdf')->assertSessionHasErrors('file');
});

it('refuse l\'import dans une promotion archivée', function () {
    importCsv("nom;prenom;email\nDupont;Marie;marie@example.com\n", Promotion::factory()->archived()->create())
        ->assertSessionHasErrors('file');

    expect(User::count())->toBe(1);
});

it('est réservé au formateur', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('promotions.learners.import', $this->promotion), ['file' => csvFile("nom;prenom;email\n")])
        ->assertForbidden();
});
