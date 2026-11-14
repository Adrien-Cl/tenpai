<?php

it('ne défile pas horizontalement sur téléphone', function (string $url) {
    visit($url)->on()->mobile()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoJavaScriptErrors();
})->with(['/', '/bareme', '/defi', '/entrainement/tenpai', '/exercice/fu/tenpai', '/en/training/tenpai']);

it('ouvre le menu sur téléphone et mène au barème', function () {
    $page = visit('/')->on()->mobile();

    $page->assertMissing('#site-menu')
        ->click('[aria-controls="site-menu"]')
        ->assertVisible('#site-menu')
        ->assertAttribute('[aria-controls="site-menu"]', 'aria-expanded', 'true')
        ->click('#site-menu a[href$="/bareme"]')
        ->assertPathIs('/bareme')
        ->assertNoJavaScriptErrors();
});

it('change d’étape depuis la barre de la main, sans remonter', function () {
    $page = visit('/exercice/fu/tenpai')->on()->mobile();

    $page->click('Voir la réponse')
        ->click('Étape suivante')
        ->assertSee('Étape 5 sur 5')
        ->assertVisible('[data-rail] nav')
        ->click('[data-rail] nav button:nth-of-type(4)')
        ->assertSee('Étape 4 sur 5 · relecture')
        ->assertNoJavaScriptErrors();
});
