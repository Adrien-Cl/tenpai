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
