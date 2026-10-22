<?php

use App\Enums\CompetencyType;
use App\Models\Competency;
use App\Models\Framework;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
});

function frameworkJson(array $overrides = []): array
{
    return [
        'name' => 'Développeur Web',
        'description' => 'Titre professionnel DWWM',
        'competencies' => [
            ['code' => 'C1', 'name' => 'Maquetter une application', 'type' => 'binary'],
            [
                'code' => 'C2',
                'name' => 'Développer une interface',
                'description' => 'HTML, CSS, JS',
                'type' => 'scale',
                'validation_threshold' => 3,
                'levels' => [
                    ['value' => 1, 'label' => 'Découverte'],
                    ['value' => 2, 'label' => 'Guidé'],
                    ['value' => 3, 'label' => 'Autonome'],
                    ['value' => 4, 'label' => 'Expert'],
                ],
            ],
            [
                'code' => 'C3',
                'name' => 'Déployer',
                'type' => 'scale',
                'levels' => [['value' => 0, 'label' => 'Non'], ['value' => 1, 'label' => 'Oui']],
            ],
        ],
        ...$overrides,
    ];
}

function importFramework(array|string $content, string $filename = 'referentiel.json')
{
    $json = is_array($content) ? json_encode($content, JSON_UNESCAPED_UNICODE) : $content;

    return test()->actingAs(test()->trainer)->post(route('frameworks.import'), [
        'file' => UploadedFile::fake()->createWithContent($filename, $json),
    ]);
}

it('crée le référentiel et ses compétences dans l\'ordre du fichier', function () {
    $response = importFramework(frameworkJson());

    $framework = Framework::sole();
    $response->assertRedirect(route('frameworks.show', $framework))->assertSessionHasNoErrors();

    $competencies = $framework->competencies()->with('levels')->get();

    expect($framework->name)->toBe('Développeur Web')
        ->and($framework->description)->toBe('Titre professionnel DWWM')
        ->and($competencies->pluck('code')->all())->toBe(['C1', 'C2', 'C3'])
        ->and($competencies[0]->type)->toBe(CompetencyType::Binary)
        ->and($competencies[1]->description)->toBe('HTML, CSS, JS')
        ->and($competencies[1]->validation_threshold)->toBe(3)
        ->and($competencies[1]->levels->pluck('label')->all())->toBe(['Découverte', 'Guidé', 'Autonome', 'Expert'])
        ->and($competencies[2]->validation_threshold)->toBe(1);
});

it('refuse un fichier qui n\'est pas du JSON valide', function () {
    importFramework('{"name": "Oups",')
        ->assertSessionHasErrors(['file' => 'Le fichier n\'est pas un JSON valide.']);

    expect(Framework::count())->toBe(0);
});

it('signale chaque erreur avec la compétence concernée et n\'importe rien', function () {
    $data = frameworkJson();
    $data['competencies'][1]['levels'] = [['value' => 1, 'label' => 'Seul']];
    $data['competencies'][2]['code'] = 'C1';

    importFramework($data)->assertSessionHasErrors([
        'competencies.1.levels' => 'Le tableau niveaux (compétence n°2) doit contenir au moins 2 éléments.',
        'competencies.2.code' => 'Le champ code (compétence n°3) a une valeur en double.',
    ]);

    expect(Framework::count())->toBe(0)
        ->and(Competency::count())->toBe(0);
});

it('valide la structure du fichier', function (array $data, string $field) {
    importFramework($data)->assertSessionHasErrors($field);

    expect(Framework::count())->toBe(0);
})->with([
    'nom manquant' => [frameworkJson(['name' => '']), 'name'],
    'sans compétences' => [frameworkJson(['competencies' => []]), 'competencies'],
    'compétences pas en liste' => [frameworkJson(['competencies' => 'C1']), 'competencies'],
    'type inconnu' => [frameworkJson(['competencies' => [['code' => 'C1', 'name' => 'X', 'type' => 'note']]]), 'competencies.0.type'],
    'seuil hors échelle' => [frameworkJson(['competencies' => [['code' => 'C1', 'name' => 'X', 'type' => 'scale', 'validation_threshold' => 5, 'levels' => [['value' => 1, 'label' => 'A'], ['value' => 2, 'label' => 'B']]]]]), 'competencies.0.validation_threshold'],
]);

it('refuse un nom de référentiel déjà utilisé', function () {
    Framework::factory()->create(['name' => 'Développeur Web']);

    importFramework(frameworkJson())->assertSessionHasErrors('name');

    expect(Framework::count())->toBe(1);
});

it('valide le fichier envoyé', function () {
    $this->actingAs($this->trainer)->post(route('frameworks.import'), [])->assertSessionHasErrors('file');

    importFramework(frameworkJson(), 'referentiel.csv')->assertSessionHasErrors('file');
});

it('est réservé au formateur', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('frameworks.import'), ['file' => UploadedFile::fake()->createWithContent('r.json', '{}')])
        ->assertForbidden();
});
