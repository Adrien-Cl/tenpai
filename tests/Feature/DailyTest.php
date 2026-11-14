<?php

use App\Livewire\Training;
use Livewire\Livewire;

it('redirige vers la main du jour, à l’heure de Paris', function () {
    $this->travelTo(new DateTimeImmutable('2026-11-13 23:30:00', new DateTimeZone('UTC')));

    $this->get('/defi')->assertRedirect('/entrainement/defi-2026-11-14');
});

it('présente la main comme le défi du jour', function () {
    Livewire::test(Training::class, ['seed' => 'defi-2026-11-13'])
        ->assertSee('Défi du jour')
        ->assertSee('Vendredi 13 novembre');
});

it('annonce la date du défi en fin de main', function () {
    $component = Livewire::test(Training::class, ['seed' => 'defi-2026-11-13']);

    foreach (range(0, 3) as $step) {
        $component->call('reveal')->call('next');
    }

    $component->call('reveal')->assertDispatched('hand-finished', daily: '2026-11-13');
});

it('donne au calendrier des défis le jour de Paris et l’adresse des mains passées', function () {
    $this->travelTo(new DateTimeImmutable('2026-11-13 23:30:00', new DateTimeZone('UTC')));

    $this->get('/')
        ->assertOk()
        ->assertSee('dailyCalendar({ today: \'2026-11-14\'', false)
        ->assertSee('entrainement\/defi-DATE', false);
});

it('propose de partager le résultat à la fin du défi, et seulement du défi', function (string $seed, bool $daily) {
    $component = Livewire::test(Training::class, ['seed' => $seed]);

    foreach (range(0, 4) as $step) {
        $component->call('reveal')->call('next');
    }

    $daily
        ? $component->assertSee('Partager mon résultat')->assertSee('shareDaily(\'2026-11-13\'', false)
        : $component->assertDontSee('Partager mon résultat');
})->with([
    'défi du jour' => ['defi-2026-11-13', true],
    'main ordinaire' => ['tenpai', false],
]);
