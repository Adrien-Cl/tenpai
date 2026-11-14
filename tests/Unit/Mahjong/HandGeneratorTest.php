<?php

use App\Domain\Mahjong\Generation\GeneratedHand;
use App\Domain\Mahjong\Generation\HandGenerator;
use App\Domain\Mahjong\Generation\Level;
use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Generation\Theme;
use App\Domain\Mahjong\Generation\TileInstance;
use App\Domain\Mahjong\Hand\MeldType;
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

it('garde le thème dans la graine d’un entraînement ciblé', function () {
    $seed = Seed::themed(Theme::Chinitsu);

    expect($seed->value)->toMatch('/^chinitsu-[a-z0-9]{8}$/')
        ->and(Seed::fromString($seed->value)->theme())->toBe(Theme::Chinitsu)
        ->and(Seed::themed(Theme::Free)->theme())->toBeNull()
        ->and(Seed::fromString('libre-abcd2345')->theme())->toBeNull()
        ->and(Seed::fromString('defi-2026-11-13')->theme())->toBeNull()
        ->and(Seed::fromString('tenpai')->theme())->toBeNull();
});

it('tire pour chaque thème une main qui contient le yaku visé', function (Theme $theme) {
    for ($i = 0; $i < 25; $i++) {
        $generated = generate(sprintf('%s-test%04d', $theme->value, $i));
        $yaku = array_map(fn ($yakuHan) => $yakuHan->yaku, Evaluator::evaluate($generated->hand, Rules::default())->yaku());

        expect($generated->theme)->toBe($theme)
            ->and(array_intersect(array_map(fn ($y) => $y->value, $theme->targets()), array_map(fn ($y) => $y->value, $yaku)))
            ->not->toBeEmpty("Graine {$theme->value}-test{$i} : aucun yaku du thème.");
    }
})->with(fn () => array_map(fn (Theme $theme) => [$theme], Theme::trainable()));

it('garde le niveau dans la graine, devant le thème', function () {
    $seed = Seed::themed(Theme::Chinitsu, Level::Expert);

    expect($seed->value)->toMatch('/^expert-chinitsu-[a-z0-9]{8}$/')
        ->and($seed->level())->toBe(Level::Expert)
        ->and($seed->theme())->toBe(Theme::Chinitsu)
        ->and(Seed::themed(Theme::Free, Level::Beginner)->value)->toMatch('/^debutant-[a-z0-9]{8}$/')
        ->and(Seed::fromString('debutant-abcd2345')->theme())->toBeNull()
        ->and(Seed::fromString('chinitsu-abcd2345')->level())->toBe(Level::Standard)
        ->and(Seed::fromString('defi-2026-11-13')->level())->toBe(Level::Standard)
        ->and(Seed::themed(Theme::Tanyao)->value)->toMatch('/^tanyao-[a-z0-9]{8}$/');
});

it('tire des mains de débutant sans kan, sans situation rare et à une seule lecture', function () {
    for ($i = 0; $i < 60; $i++) {
        $hand = generate(sprintf('debutant-test%04d', $i))->hand;

        expect($hand->kanCount())->toBe(0)
            ->and([$hand->doubleRiichi, $hand->ippatsu, $hand->haitei, $hand->rinshan, $hand->chankan])->each->toBeFalse()
            ->and(Evaluator::evaluate($hand, Rules::default())->multiple)->toBeFalse();
    }
});

it('tire plus de kan et de mains à plusieurs lectures au niveau expert', function () {
    $count = function (string $prefix): array {
        $kans = 0;
        $multiple = 0;

        for ($i = 0; $i < 120; $i++) {
            $hand = generate(sprintf('%stest%04d', $prefix, $i))->hand;
            $kans += $hand->kanCount() > 0 ? 1 : 0;
            $multiple += Evaluator::evaluate($hand, Rules::default())->multiple ? 1 : 0;
        }

        return [$kans, $multiple];
    };

    [$standardKans, $standardMultiple] = $count('');
    [$expertKans, $expertMultiple] = $count('expert-');

    expect($expertKans)->toBeGreaterThan($standardKans)
        ->and($expertMultiple)->toBeGreaterThan($standardMultiple);
});

it('pose honba et bâtons de riichi sans changer la main', function () {
    $hands = array_map(fn (int $i) => generate(sprintf('test%04d', $i))->hand, range(0, 79));

    foreach ($hands as $hand) {
        expect($hand->honba)->toBeBetween(0, 4)
            ->and($hand->riichiSticks)->toBeBetween($hand->riichi ? 1 : 0, $hand->riichi ? 3 : 2);
    }

    expect(array_filter($hands, fn ($hand) => $hand->honba > 0))->not->toBeEmpty()
        ->and(generate('tenpai')->hand->honba)->toBe(generate('tenpai')->hand->honba);
});

it('abrège les tuiles posées sur la table, appels compris', function () {
    $closedKan = $calledTile = false;

    for ($i = 0; $i < 300; $i++) {
        $generated = generate("expert-controle-{$i}");
        $codes = $generated->tableCodes();

        expect($codes['hand'])->toHaveCount(count($generated->handTiles))
            ->and($codes['win'])->toBe($generated->winTile->tile.($generated->winTile->red ? 'r' : ''))
            ->and($codes['melds'])->toHaveCount(count($generated->melds));

        foreach ($generated->melds as $m => $meld) {
            $meldCodes = $codes['melds'][$m];

            expect($meldCodes)->toHaveCount(count($meld->tiles));

            if ($meld->type === MeldType::ClosedKan) {
                $closedKan = true;
                expect($meldCodes[0])->toBe('x')->and($meldCodes[3])->toBe('x');
            }

            if ($meld->rotated !== null) {
                $calledTile = true;
                expect($meldCodes[$meld->rotated])->toStartWith('s');
            }
        }
    }

    expect($closedKan)->toBeTrue()->and($calledTile)->toBeTrue();
});
