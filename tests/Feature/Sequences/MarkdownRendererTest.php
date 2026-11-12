<?php

use App\Models\Icon;
use App\Support\MarkdownRenderer;

beforeEach(function () {
    $this->markdown = app(MarkdownRenderer::class);
});

it('rend les étapes numérotées, le gras et les citations', function () {
    $html = $this->markdown->toHtml("1. Connecte-toi\n2. Ouvre **Internet**\n\n> Cadre commun");

    expect($html)
        ->toContain('<ol>')
        ->toContain('<li>Ouvre <strong>Internet</strong></li>')
        ->toContain('<blockquote>');
});

it('garde les emojis tels quels', function () {
    expect($this->markdown->toHtml('🎯 12/20 en mode débutant · 🔥🔥'))
        ->toContain('🎯 12/20 en mode débutant · 🔥🔥');
});

it('échappe le HTML brut', function () {
    $html = $this->markdown->toHtml("<script>alert('x')</script>\n\n<img src=x onerror=alert(1)>");

    expect($html)
        ->not->toContain('<script>')
        ->not->toContain('<img')
        ->toContain('&lt;script&gt;');
});

it('refuse les liens javascript', function () {
    expect($this->markdown->toHtml('[clic](javascript:alert(1))'))
        ->not->toContain('javascript:');
});

it('ouvre les liens externes dans un nouvel onglet, adresses nues comprises', function () {
    $html = $this->markdown->toHtml("[Clavinator](https://www.logicieleducatif.fr/games/clavinator5/)\n\nLancer https://flexboxfroggy.com/#fr");

    expect($html)
        ->toContain('href="https://www.logicieleducatif.fr/games/clavinator5/"')
        ->toContain('href="https://flexboxfroggy.com/#fr"')
        ->toContain('target="_blank"')
        ->toContain('noopener');
});

it('affiche les icônes importées à la taille du texte', function () {
    $colour = Icon::factory()->create(['name' => 'Fusée', 'monochrome' => false]);
    $mono = Icon::factory()->create(['name' => 'Souris', 'monochrome' => true]);

    $html = $this->markdown->toHtml("![](icon:{$colour->id}) Départ ![clic](icon:{$mono->id}) ![](icon:999)");

    expect($html)
        ->toContain('<img class="icon" src="'.route('icons.show', $colour).'" alt="Fusée"')
        // One colour: drawn in the colour of the text, as a mask.
        ->toContain('<span class="icon icon-mono" role="img" aria-label="clic" style="--icon: url(&quot;'.route('icons.show', $mono).'&quot;)"></span>')
        // A deleted icon shows nothing.
        ->not->toContain('999');
});
