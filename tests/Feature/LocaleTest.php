<?php

use App\Livewire\Training;
use Livewire\Livewire;

it('sert la version anglaise sous /en, avec ses propres adresses', function () {
    $this->get('/en')
        ->assertOk()
        ->assertSee('<html lang="en"', false)
        ->assertSee('Score a riichi hand in five steps, no calculator.')
        ->assertSee('href="'.url('/en/training').'"', false)
        ->assertSee('href="'.url('/en/scoring').'"', false)
        ->assertDontSee('Commencer une main');
});

it('relie chaque page à sa traduction (hreflang et lien de langue)', function () {
    $this->get('/entrainement/tenpai')
        ->assertSee('<link rel="alternate" hreflang="fr" href="'.url('/entrainement/tenpai').'">', false)
        ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/en/training/tenpai').'">', false)
        ->assertSee('<link rel="alternate" hreflang="x-default" href="'.url('/entrainement/tenpai').'">', false)
        ->assertSee('hreflang="en" lang="en"', false)
        ->assertSeeInOrder(['<header', 'aria-label="English"', '>EN</a>', '</header>'], false);

    $this->get('/en/scoring')
        ->assertSee('href="'.url('/bareme').'" hreflang="fr" lang="fr"', false)
        ->assertSeeInOrder(['<header', 'aria-label="Français"', '>FR</a>', '</header>'], false);
});

it('garde la langue en tirant une main', function (string $from, string $pattern) {
    $response = $this->get($from);

    expect($response->headers->get('Location'))->toMatch($pattern);
})->with([
    'main libre' => ['/en/training', '#/en/training/[a-z0-9]{8}$#'],
    'thème' => ['/en/training?theme=pinfu', '#/en/training/pinfu-[a-z0-9]{8}$#'],
    'exercice' => ['/en/training?exercice=fu', '#/en/drill/fu/[a-z0-9]{8}$#'],
    'défi du jour' => ['/en/daily', '#/en/training/defi-\d{4}-\d{2}-\d{2}$#'],
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

    $component->call('nextHand')->assertRedirectContains('/en/training/');
});

it('écrit les nombres et les explications du moteur en anglais', function () {
    $this->get('/en/scoring')
        ->assertOk()
        ->assertSee('12,000')
        ->assertDontSee("12\u{00A0}000")
        ->assertSee('Calculated with: red fives, no kiriage, double wind for 4 fu.');

    $this->get('/en/training/tenpai')
        ->assertSee('aria-label="7 of characters"', false)
        ->assertSee('Your seat')
        ->assertSee('Standard riichi, Japanese parlour style: kuitan, red fives');
});

it('donne l’aperçu anglais d’une main partagée', function () {
    $this->get('/en/training/tenpai')
        ->assertSee('<meta property="og:image" content="'.url('/en/preview/tenpai.png').'">', false)
        ->assertSee('<meta property="og:locale" content="en_GB">', false);

    $this->get('/en/scoring')
        ->assertSee('<meta property="og:image" content="'.asset('og-en.png').'">', false);

    $this->get('/en/preview/tenpai.png')->assertOk()->assertHeader('Content-Type', 'image/png');
})->skip(fn () => ! function_exists('imagecreatefrompng'), 'GD n’est pas installé.');
