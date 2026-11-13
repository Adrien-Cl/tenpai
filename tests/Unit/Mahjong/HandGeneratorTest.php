<?php

use App\Domain\Mahjong\Generation\GeneratedHand;
use App\Domain\Mahjong\Generation\HandGenerator;
use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Generation\TileInstance;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Scoring\Evaluator;
use App\Domain\Mahjong\Tile;

function generate(string $seed): GeneratedHand
{
    return (new HandGenerator)->generate(Seed::fromString($seed));
}

it('donne toujours la même main pour la même graine', function () {
    expect(generate('k7f2qx9a')->toArray())->toBe(generate('k7f2qx9a')->toArray());
});

it('donne des mains différentes pour des graines différentes', function () {
    $hands = array_map(fn (int $i) => serialize(generate("graine-{$i}")->toArray()['closed']), range(1, 20));

    expect(count(array_unique($hands)))->toBeGreaterThan(15);
});

it('fige la main de la graine tenpai', function () {
    $hand = generate('tenpai')->toArray();

    expect($hand)->toMatchSnapshot();
});

it('produit des mains réalistes et valides', function () {
    $generator = new HandGenerator;

    for ($i = 0; $i < 1000; $i++) {
        $generated = $generator->generate(Seed::fromString("controle-{$i}"));
        $hand = $generated->hand;
        $counts = Tile::counts($generated->tilesOnTable());

        // Chankan : les trois autres exemplaires sont dans le kan de l'adversaire.
        if ($hand->chankan) {
            $counts[$hand->win] += 3;
        }

        expect(max($counts))->toBeLessThanOrEqual(4, "Graine controle-{$i} : une tuile existe en plus de 4 exemplaires.");

        $tiles = [...$generated->handTiles, $generated->winTile];

        foreach ($generated->melds as $meld) {
            array_push($tiles, ...$meld->tiles);
        }

        $reds = array_filter($tiles, fn (TileInstance $tile) => $tile->red);

        foreach ($reds as $red) {
            expect(in_array($red->tile, Tile::FIVES, true))->toBeTrue();
        }

        expect(count($reds))->toBe($hand->aka)
            ->and(count(array_unique(array_map(fn (TileInstance $tile) => $tile->tile, $reds))))->toBe(count($reds))
            ->and(count($tiles))->toBe(14 + $hand->kanCount())
            ->and(Evaluator::evaluate($hand, Rules::default())->isValid())->toBeTrue("Graine controle-{$i} : main invalide.");
    }
});

it('construit la graine du défi du jour à partir de la date', function () {
    $seed = Seed::daily(new DateTimeImmutable('2026-11-13'));

    expect($seed->value)->toBe('defi-2026-11-13')
        ->and($seed->dailyDate())->toBe('2026-11-13');
});

it('normalise et valide les graines', function () {
    expect(Seed::fromString(' AbC-12 ')->value)->toBe('abc-12')
        ->and(Seed::isValid('pas de espace'))->toBeFalse()
        ->and(Seed::isValid(str_repeat('a', 41)))->toBeFalse()
        ->and(Seed::random()->value)->toMatch('/^[a-z0-9]{8}$/');
});
