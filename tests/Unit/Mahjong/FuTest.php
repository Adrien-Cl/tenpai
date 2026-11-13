<?php

use App\Domain\Mahjong\Hand\Hand;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Scoring\Evaluation;
use App\Domain\Mahjong\Scoring\Evaluator;
use App\Domain\Mahjong\Scoring\FuPart;
use App\Domain\Mahjong\Wait;
use App\Domain\Mahjong\Wind;

function evaluate(Hand $hand, ?Rules $rules = null): Evaluation
{
    return Evaluator::evaluate($hand, $rules ?? Rules::default());
}

/**
 * @return array<string, int>
 */
function fuParts(Evaluation $evaluation): array
{
    $parts = [];

    foreach ($evaluation->reading()->fu->parts as $part) {
        /** @var FuPart $part */
        $parts[$part->key] = $part->fu;
    }

    return $parts;
}

it('compte 30 fu pour un pinfu en ron', function () {
    $evaluation = evaluate(hand('123m 456m 234p 678s 99s', '4m'));

    expect($evaluation->fu())->toBe(30)
        ->and($evaluation->reading()->fu->raw)->toBe(30)
        ->and($evaluation->wait())->toBe(Wait::Ryanmen)
        ->and(fuParts($evaluation))->toMatchArray(['base' => 20, 'win' => 10, 'pair' => 0, 'wait' => 0]);
});

it('compte 20 fu pile pour un pinfu en tsumo', function () {
    $evaluation = evaluate(hand('234m 567m 345p 678s 55s', '2m', ['tsumo' => true, 'riichi' => true, 'ura' => '3z']));

    expect($evaluation->fu())->toBe(20)
        ->and(fuParts($evaluation)['win'])->toBe(0);
});

it('compte 25 fu fixes pour chiitoitsu, sans arrondi', function () {
    $evaluation = evaluate(hand('11m 99m 22p 55p 77s 11z 66z', '7s', ['riichi' => true, 'ura' => '3z']));

    expect($evaluation->reading()->chiitoitsu)->toBeTrue()
        ->and($evaluation->fu())->toBe(25)
        ->and($evaluation->reading()->fu->raw)->toBe(25)
        ->and($evaluation->wait())->toBe(Wait::Tanki);
});

it('compte comme ouvert le brelan complété par ron en shanpon', function () {
    $evaluation = evaluate(hand('234m 678p 345s 11p 999s', '9s', ['riichi' => true, 'ura' => '3z']));
    $triplet = array_values(array_filter($evaluation->reading()->fu->parts, fn (FuPart $part) => $part->group?->ronDone === true))[0];

    expect($evaluation->wait())->toBe(Wait::Shanpon)
        ->and($triplet->fu)->toBe(4)
        ->and($triplet->why)->toContain('compte comme ouvert')
        ->and($evaluation->reading()->fu->raw)->toBe(34)
        ->and($evaluation->fu())->toBe(40);
});

it('garde le brelan fermé quand le shanpon est complété en tsumo', function () {
    $evaluation = evaluate(hand('234m 678p 345s 11p 999s', '9s', ['tsumo' => true]));

    expect($evaluation->reading()->fu->raw)->toBe(20 + 2 + 8)
        ->and($evaluation->fu())->toBe(30);
});

it('porte à 30 fu une main ouverte à 20 fu', function () {
    $evaluation = evaluate(hand('567p 345s 678s 22p', '5s', ['melds' => ['chi:2m']]));

    expect($evaluation->menzen)->toBeFalse()
        ->and($evaluation->reading()->fu->raw)->toBe(20)
        ->and($evaluation->fu())->toBe(30)
        ->and($evaluation->reading()->fu->finalWhy)->toBe('Une main ouverte à 20 fu est portée à 30 fu.');
});

it('compte 32 fu pour un kan fermé de terminales', function () {
    $evaluation = evaluate(hand('234p 567p 345s 77s', '4p', ['melds' => ['ankan:9m'], 'riichi' => true, 'dora' => '3z 3z', 'ura' => '3z 3z']));

    expect($evaluation->menzen)->toBeTrue()
        ->and(fuParts($evaluation)['g3'])->toBe(32)
        ->and($evaluation->reading()->fu->raw)->toBe(62)
        ->and($evaluation->fu())->toBe(70);
});

it('compte la paire de double vent selon la règle', function (bool $doubleWind4, int $expected) {
    $hand = hand('234m 567m 345p 678s 11z', '2m', ['seat' => Wind::East, 'round' => Wind::East, 'riichi' => true, 'ura' => '3z']);

    expect(fuParts(evaluate($hand, new Rules(doubleWind4: $doubleWind4)))['pair'])->toBe($expected);
})->with([
    'à 4 fu' => [true, 4],
    'à 2 fu' => [false, 2],
]);

it('ajoute 2 fu pour une attente fermée', function (string $closed, string $win, Wait $wait) {
    $evaluation = evaluate(hand($closed, $win, ['riichi' => true, 'ura' => '3z']));

    expect($evaluation->wait())->toBe($wait)
        ->and(fuParts($evaluation)['wait'])->toBe(2);
})->with([
    'kanchan' => ['234m 567m 345p 678s 99s', '3m', Wait::Kanchan],
    'penchan' => ['123m 567m 345p 678s 99s', '3m', Wait::Penchan],
    'tanki' => ['234m 567m 345p 678s 99s', '9s', Wait::Tanki],
]);
