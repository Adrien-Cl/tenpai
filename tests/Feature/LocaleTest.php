<?php

use App\Livewire\Training;
use Livewire\Livewire;

it('sert la version anglaise sous /en, avec ses propres adresses', function () {
    $this->get('/en')
        ->assertOk()
        ->assertSee('<html lang="en"', false)
        ->assertSee('Score a riichi hand in five steps, no calculator.')
        ->assertSee('href="'.url('/en/counting').'"', false)
        ->assertSee('href="'.url('/en/scoring').'"', false)
        ->assertDontSee('Commencer une main');
});

it('relie chaque page à sa traduction (hreflang et lien de langue)', function () {
    $this->get('/comptage/tenpai')
        ->assertSee('<link rel="alternate" hreflang="fr" href="'.url('/comptage/tenpai').'">', false)
        ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/en/counting/tenpai').'">', false)
        ->assertSee('<link rel="alternate" hreflang="x-default" href="'.url('/comptage/tenpai').'">', false)
        ->assertSee('hreflang="en" lang="en"', false)
        ->assertSeeInOrder(['<header', 'aria-label="English"', '>EN</span>', '</header>'], false);

    $this->get('/en/scoring')
        ->assertSee('href="'.url('/bareme').'" hreflang="fr" lang="fr"', false)
        ->assertSeeInOrder(['<header', 'aria-label="Français"', '>FR</span>', '</header>'], false);
});

it('garde la langue en tirant une main', function (string $from, string $pattern) {
    $response = $this->get($from);

    expect($response->headers->get('Location'))->toMatch($pattern);
})->with([
    'main libre' => ['/en/counting', '#/en/counting/[a-z0-9]{8}$#'],
    'thème' => ['/en/counting?theme=pinfu', '#/en/counting/pinfu-[a-z0-9]{8}$#'],
    'exercice' => ['/en/counting?exercice=fu', '#/en/drill/fu/[a-z0-9]{8}$#'],
    'défi du jour' => ['/en/daily', '#/en/counting/defi-\d{4}-\d{2}-\d{2}$#'],
]);

it('reste en anglais pendant toute la main, requêtes Livewire comprises', function () {
    app()->setLocale('en');

    $component = Livewire::test(Training::class, ['seed' => 'tenpai'])
        ->assertSet('locale', 'en')
        ->assertSee('Which yaku?');

    // Une requête Livewire suivante arrive sans langue : le composant remet la sienne.
    app()->setLocale('fr');

    $component->call('reveal')
        ->assertSee('The yaku in this hand:')
        ->assertDontSee('Les yaku de cette main');

    $component->call('nextHand')->assertRedirectContains('/en/counting/');
});

it('écrit les nombres et les explications du moteur en anglais', function () {
    $this->get('/en/scoring')
        ->assertOk()
        ->assertSee('12,000')
        ->assertDontSee("12\u{00A0}000")
        ->assertSee('Calculated with: red fives, no kiriage, double wind for 4 fu, counted yakuman.');

    $this->get('/en/counting/tenpai')
        ->assertSee('aria-label="7 of characters"', false)
        ->assertSee('Your seat')
        ->assertSee('Japanese parlour and online: kuitan, red fives');
});

it('donne l’aperçu anglais d’une main partagée', function () {
    $this->get('/en/counting/tenpai')
        ->assertSee('<meta property="og:image" content="'.url('/en/preview/tenpai.png').'">', false)
        ->assertSee('<meta property="og:locale" content="en_GB">', false);

    $this->get('/en/scoring')
        ->assertSee('<meta property="og:image" content="'.asset('og-en.png').'">', false);

    $this->get('/en/preview/tenpai.png')->assertOk()->assertHeader('Content-Type', 'image/png');
})->skip(fn () => ! function_exists('imagecreatefrompng'), 'GD n’est pas installé.');
