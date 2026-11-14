<?php

use App\Livewire\Training;
use App\Support\TableRules;
use Livewire\Livewire;

/**
 * La valeur du cookie de règles, telle que le contrôleur l'écrit.
 */
function rulesCookie(bool $aka = true, bool $kiriage = false, bool $doubleWind4 = true): string
{
    return json_encode(['aka' => $aka, 'kiriage' => $kiriage, 'doubleWind4' => $doubleWind4]);
}

it('enregistre les règles cochées dans un cookie et revient sur la page', function () {
    $this->from('/bareme')
        ->post('/regles', ['aka' => '1', 'kiriage' => '1'])
        ->assertRedirect('/bareme')
        ->assertCookie(TableRules::COOKIE, rulesCookie(kiriage: true, doubleWind4: false));
});

it('refuse une option qui n’est pas un booléen', function () {
    $this->from('/bareme')
        ->post('/regles', ['kiriage' => 'peut-être'])
        ->assertRedirect('/bareme')
        ->assertSessionHasErrors('kiriage')
        ->assertCookieMissing(TableRules::COOKIE);
});

it('calcule le barème avec les règles du cookie', function () {
    $this->withCookie(TableRules::COOKIE, rulesCookie(aka: false, kiriage: true))
        ->get('/bareme')
        ->assertOk()
        ->assertSee('Calculé avec : sans rouges, kiriage mangan, double vent à 4 fu.')
        ->assertSee('Kiriage : 4 han 30 fu et 3 han 60 fu comptent comme mangan.')
        ->assertSee('Vos règles');
});

it('garde les règles par défaut sans cookie, ou avec un cookie illisible', function (?string $cookie) {
    $request = $cookie === null ? $this : $this->withCookie(TableRules::COOKIE, $cookie);

    $request->get('/bareme')
        ->assertOk()
        ->assertSee('Calculé avec : cinq rouges, sans kiriage, double vent à 4 fu.')
        ->assertSee('Riichi courant, style salle japonaise')
        ->assertDontSee('Kiriage : 4 han 30 fu');
})->with([
    'sans cookie' => [null],
    'cookie illisible' => ['pas du json'],
]);

it('propose le panneau des règles, cases cochées selon les règles en cours', function () {
    $this->withCookie(TableRules::COOKIE, rulesCookie(kiriage: true))
        ->get('/')
        ->assertOk()
        ->assertSee('Règles de la table')
        ->assertSee('action="'.route('rules').'"', false)
        ->assertSee('name="kiriage" value="1" class="mt-1 size-4 shrink-0 accent-shu" checked', false);
});

it('fige les règles du cookie à l’ouverture de la main', function () {
    $component = Livewire::withCookies([TableRules::COOKIE => rulesCookie(kiriage: true)])
        ->test(Training::class, ['seed' => 'tenpai'])
        ->assertSet('rules', ['aka' => true, 'kiriage' => true, 'doubleWind4' => true]);

    expect($component->instance()->hand->rules->kiriage)->toBeTrue();
});

it('ouvre les règles depuis l’en-tête, signalées quand elles sont personnalisées', function () {
    $this->get('/')
        ->assertSee('aria-label="Règles de la table"', false)
        ->assertDontSee('(règles personnalisées)');

    $this->withCookie(TableRules::COOKIE, rulesCookie(kiriage: true))
        ->get('/')
        ->assertSee('(règles personnalisées)');
});
