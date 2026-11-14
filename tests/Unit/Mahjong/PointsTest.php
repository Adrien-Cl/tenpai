<?php

use App\Domain\Mahjong\Limit;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Scoring\PointsCalculator;

function ron(int $han, int $fu, bool $dealer = false, ?Rules $rules = null): int
{
    return PointsCalculator::points($han, $fu, $dealer, false, $rules ?? Rules::default())->ron;
}

it('compte un ron et arrondit à la centaine supérieure', function (int $han, int $fu, bool $dealer, int $expected) {
    expect(ron($han, $fu, $dealer))->toBe($expected);
})->with([
    '1 han 30 fu' => [1, 30, false, 1000],
    '1 han 30 fu donneur' => [1, 30, true, 1500],
    '2 han 25 fu' => [2, 25, false, 1600],
    '3 han 30 fu donneur' => [3, 30, true, 5800],
    '4 han 30 fu' => [4, 30, false, 7700],
    '3 han 60 fu' => [3, 60, false, 7700],
]);

it('paie un tsumo de non-donneur : le donneur paie double', function () {
    $payment = PointsCalculator::points(1, 30, false, true, Rules::default());

    expect($payment->fromOthers)->toBe(300)
        ->and($payment->fromDealer)->toBe(500)
        ->and($payment->total)->toBe(1100);
});

it('paie un tsumo de donneur : chacun paie la même chose', function () {
    $payment = PointsCalculator::points(3, 30, true, true, Rules::default());

    expect($payment->each)->toBe(2000)
        ->and($payment->total)->toBe(6000);
});

it('applique les limites', function (int $han, int $fu, ?Limit $limit, int $nonDealer, int $dealer) {
    $payment = PointsCalculator::points($han, $fu, false, false, Rules::default());

    expect($payment->limit)->toBe($limit)
        ->and($payment->ron)->toBe($nonDealer)
        ->and(ron($han, $fu, true))->toBe($dealer);
})->with([
    'mangan à 4 han 40 fu' => [4, 40, Limit::Mangan, 8000, 12000],
    'mangan à 3 han 70 fu' => [3, 70, Limit::Mangan, 8000, 12000],
    'mangan à 5 han' => [5, 30, Limit::Mangan, 8000, 12000],
    'haneman' => [6, 30, Limit::Haneman, 12000, 18000],
    'baiman' => [8, 30, Limit::Baiman, 16000, 24000],
    'sanbaiman' => [11, 30, Limit::Sanbaiman, 24000, 36000],
    'yakuman compté' => [13, 30, Limit::Kazoe, 32000, 48000],
    'pas de limite' => [3, 30, null, 3900, 5800],
]);

it('arrondit au mangan avec le kiriage', function (int $han, int $fu) {
    $kiriage = new Rules(kiriage: true);

    expect(ron($han, $fu, rules: $kiriage))->toBe(8000)
        ->and(ron($han, $fu))->toBe(7700);
})->with([
    '4 han 30 fu' => [4, 30],
    '3 han 60 fu' => [3, 60],
]);

it('plafonne 13 han au sanbaiman sans yakuman compté', function () {
    $payment = PointsCalculator::points(14, 30, false, false, new Rules(kazoe: false));

    expect($payment->limit)->toBe(Limit::Sanbaiman)
        ->and($payment->ron)->toBe(24000)
        ->and(ron(14, 30))->toBe(32000);
});

it('ajoute 300 par honba au ron', function () {
    $payment = PointsCalculator::points(3, 30, false, false, Rules::default(), honba: 2);

    expect($payment->ron)->toBe(3900 + 600)
        ->and($payment->total)->toBe(4500)
        ->and($payment->honba)->toBe(2);
});

it('ajoute 100 par honba à chaque paiement d’un tsumo', function () {
    $nonDealer = PointsCalculator::points(1, 30, false, true, Rules::default(), honba: 3);
    $dealer = PointsCalculator::points(3, 30, true, true, Rules::default(), honba: 1);

    expect($nonDealer->fromOthers)->toBe(300 + 300)
        ->and($nonDealer->fromDealer)->toBe(500 + 300)
        ->and($nonDealer->total)->toBe(1100 + 900)
        ->and($dealer->each)->toBe(2100)
        ->and($dealer->total)->toBe(6300);
});

it('compte les bâtons de riichi dans le gain, pas dans les paiements', function () {
    $payment = PointsCalculator::points(3, 30, false, false, Rules::default(), honba: 1, sticks: 2);

    expect($payment->ron)->toBe(4200)
        ->and($payment->total)->toBe(4200)
        ->and($payment->gain())->toBe(6200)
        ->and($payment->hasBonus())->toBeTrue()
        ->and(PointsCalculator::points(3, 30, false, false, Rules::default())->hasBonus())->toBeFalse();
});
