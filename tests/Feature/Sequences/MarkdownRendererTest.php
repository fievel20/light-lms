<?php

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
