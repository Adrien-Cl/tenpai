<?php

use App\Domain\Mahjong\Generation\HandGenerator;
use App\Domain\Mahjong\Generation\Seed;
use App\Http\Controllers\HandTilesController;

it('donne les tuiles des mains demandées, comme pendant le comptage', function () {
    $generator = new HandGenerator;

    $this->getJson('/mains.json?graines=tenpai,expert-chinitsu-ab12cd34')
        ->assertOk()
        ->assertExactJson([
            'tenpai' => $generator->generate(Seed::fromString('tenpai'))->tableCodes(),
            'expert-chinitsu-ab12cd34' => $generator->generate(Seed::fromString('expert-chinitsu-ab12cd34'))->tableCodes(),
        ]);
});

it('ignore les graines invalides et rend un objet vide sans graine', function () {
    $this->getJson('/mains.json?graines=<script>,-x')->assertOk()->assertExactJson([]);
    expect($this->get('/mains.json')->getContent())->toBe('{}');
});

it('limite le nombre de mains par demande', function () {
    $seeds = implode(',', array_map(fn (int $i): string => "graine-{$i}", range(1, 30)));

    expect($this->getJson("/mains.json?graines={$seeds}")->json())->toHaveCount(HandTilesController::MAX_SEEDS);
});

it('limite le rythme des demandes', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->getJson('/mains.json?graines=tenpai')->assertOk();
    }

    $this->getJson('/mains.json?graines=tenpai')->assertTooManyRequests();
});
