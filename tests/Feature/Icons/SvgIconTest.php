<?php

use App\Support\SvgIcon;

it('retire les scripts, les événements et les liens externes', function () {
    $icon = SvgIcon::clean(<<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 24 24" onload="alert(1)">
          <script>alert(2)</script>
          <a xlink:href="javascript:alert(3)"><path d="M0 0h24v24H0z"/></a>
          <image href="https://example.com/pixel.png"/>
        </svg>
        SVG);

    expect($icon)->not->toBeNull()
        ->and($icon->svg)
        ->not->toContain('script')
        ->not->toContain('onload')
        ->not->toContain('javascript:')
        ->not->toContain('example.com')
        ->toContain('<path');
});

it('ajoute la zone d’affichage pour que l’icône se redimensionne', function () {
    $icon = SvgIcon::clean('<svg xmlns="http://www.w3.org/2000/svg" width="512px" height="256"><rect width="512" height="256"/></svg>');

    expect($icon->svg)
        ->toContain('viewBox="0 0 512 256"')
        ->not->toMatch('/<svg[^>]*\swidth=/')
        ->not->toMatch('/<svg[^>]*\sheight=/');
});

it('garde la zone d’affichage existante', function () {
    $icon = SvgIcon::clean('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="96" height="96"><circle cx="24" cy="24" r="20"/></svg>');

    expect($icon->svg)->toContain('viewBox="0 0 48 48"')
        ->not->toMatch('/<svg[^>]*\swidth=/');
});

it('reconnaît une icône d’une seule couleur', function (string $svg, bool $monochrome) {
    expect(SvgIcon::clean($svg)->monochrome)->toBe($monochrome);
})->with([
    'sans couleur' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M0 0h24v24H0z"/></svg>', true],
    'currentColor' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M2 2l20 20"/></svg>', true],
    'une couleur' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="#1d4ed8" d="M0 0h12v12H0z"/><path style="fill: #1D4ED8" d="M12 12h12v12H12z"/></svg>', true],
    'deux couleurs' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="#1d4ed8" d="M0 0h12v12H0z"/><path fill="#facc15" d="M12 12h12v12H12z"/></svg>', false],
    'dégradé' => ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><defs><linearGradient id="g"><stop offset="0" stop-color="#000"/></linearGradient></defs><rect fill="url(#g)" width="24" height="24"/></svg>', false],
]);

it('refuse ce qui n’est pas un SVG', function (string $content) {
    expect(SvgIcon::clean($content))->toBeNull();
})->with([
    'texte' => ['bonjour'],
    'html' => ['<html><body>x</body></html>'],
    'xml cassé' => ['<svg xmlns="http://www.w3.org/2000/svg"><path></svg'],
]);
