<?php

use App\Domain\Mahjong\Limit;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Scoring\Evaluation;
use App\Domain\Mahjong\Scoring\EvaluationStatus;
use App\Domain\Mahjong\Scoring\Evaluator;
use App\Domain\Mahjong\Scoring\YakuHan;
use App\Domain\Mahjong\Yaku;

/**
 * @return array<string, int>
 */
function yakuList(Evaluation $evaluation): array
{
    $list = [];

    foreach ($evaluation->yaku() as $yaku) {
        /** @var YakuHan $yaku */
        $list[$yaku->yaku->value] = $yaku->han;
    }

    return $list;
}

it('compte 1000 points pour un pinfu en ron', function () {
    $evaluation = evaluate(hand('123m 456m 234p 678s 99s', '4m'));

    expect(yakuList($evaluation))->toBe(['pinfu' => 1])
        ->and($evaluation->han)->toBe(1)
        ->and($evaluation->payment()->ron)->toBe(1000);
});

it('compte 1300/2600 pour riichi, tsumo, pinfu et tanyao', function () {
    $evaluation = evaluate(hand('234m 567m 345p 678s 55s', '2m', ['tsumo' => true, 'riichi' => true, 'ura' => '3z']));

    expect(yakuList($evaluation))->toBe(['riichi' => 1, 'tsumo' => 1, 'pinfu' => 1, 'tanyao' => 1])
        ->and($evaluation->han)->toBe(4)
        ->and($evaluation->fu())->toBe(20)
        ->and($evaluation->payment()->fromOthers)->toBe(1300)
        ->and($evaluation->payment()->fromDealer)->toBe(2600)
        ->and($evaluation->payment()->limit)->toBeNull();
});

it('compte 3200 pour riichi et chiitoitsu en ron', function () {
    $evaluation = evaluate(hand('11m 99m 22p 55p 77s 11z 66z', '7s', ['riichi' => true, 'ura' => '3z']));

    expect(yakuList($evaluation))->toBe(['riichi' => 1, 'chiitoi' => 2])
        ->and($evaluation->payment()->ron)->toBe(3200);
});

it('compte 1300 pour un riichi en shanpon', function () {
    $evaluation = evaluate(hand('234m 678p 345s 11p 999s', '9s', ['riichi' => true, 'ura' => '3z']));

    expect($evaluation->payment()->ron)->toBe(1300);
});

it('compte 1000 pour un tanyao ouvert à 30 fu', function () {
    $evaluation = evaluate(hand('567p 345s 678s 22p', '5s', ['melds' => ['chi:2m']]));

    expect(yakuList($evaluation))->toBe(['tanyao' => 1])
        ->and($evaluation->payment()->ron)->toBe(1000);
});

it('retire un han aux yaku concernés quand la main est ouverte', function () {
    $honitsu = evaluate(hand('234m 678m 999m 55m', '2m', ['melds' => ['pon:5z']]));
    $chinitsu = evaluate(hand('345m 678m 999m 22m', '3m', ['melds' => ['chi:1m']]));

    expect(yakuList($honitsu))->toBe(['haku' => 1, 'honitsu' => 2])
        ->and(yakuList($chinitsu))->toMatchArray(['chinitsu' => 5]);
});

it('compte les dora, les cinq rouges et les ura-dora', function () {
    $evaluation = evaluate(hand('234m 567m 345p 678s 55s', '2m', [
        'tsumo' => true,
        'riichi' => true,
        'dora' => '1m',
        'ura' => '4s',
        'aka' => 1,
    ]));

    expect($evaluation->dora->count)->toBe(1)
        ->and($evaluation->ura->count)->toBe(2)
        ->and($evaluation->aka)->toBe(1)
        ->and($evaluation->han)->toBe(4 + 1 + 2 + 1)
        ->and($evaluation->payment()->limit)->toBe(Limit::Baiman);
});

it('ignore les ura-dora sans riichi', function () {
    $evaluation = evaluate(hand('123m 456m 234p 678s 99s', '4m', ['ura' => '8s']));

    expect($evaluation->ura->count)->toBe(0);
});

it('retient la lecture qui rapporte le plus', function () {
    $evaluation = evaluate(hand('222333444m 678s 55p', '6s', ['tsumo' => true]));

    expect(yakuList($evaluation))->toBe(['tsumo' => 1, 'tanyao' => 1, 'sanankou' => 2])
        ->and($evaluation->fu())->toBe(40)
        ->and($evaluation->payment()->limit)->toBe(Limit::Mangan)
        ->and($evaluation->multiple)->toBeTrue();
});

it('écarte les yakuman', function () {
    $evaluation = evaluate(hand('555z 666z 777z 123m 99m', '1m'));

    expect($evaluation->status)->toBe(EvaluationStatus::Yakuman);
});

it('signale une main sans yaku', function () {
    $evaluation = evaluate(hand('456p 789s 234s 99p', '4p', ['melds' => ['chi:1m']]));

    expect($evaluation->status)->toBe(EvaluationStatus::NoYaku);
});

it('signale une main incomplète', function () {
    $evaluation = evaluate(hand('13579m 2468p 1357s 1z', '1m'));

    expect($evaluation->status)->toBe(EvaluationStatus::Invalid);
});

it('remplace riichi par double riichi', function () {
    $evaluation = evaluate(hand('123m 456m 234p 678s 99s', '4m', ['riichi' => true, 'doubleRiichi' => true, 'ura' => '3z']));

    expect(array_keys(yakuList($evaluation)))->toContain(Yaku::DoubleRiichi->value)
        ->not->toContain(Yaku::Riichi->value);
});

it('donne toutes les lectures, de la plus payante à la moins payante', function () {
    $hand = hand('222333444m 678s 55p', '6s', ['tsumo' => true]);
    $readings = Evaluator::scoredReadings($hand, Rules::default());

    expect($readings)->toHaveCount(2)
        ->and($readings[0]->payment->limit)->toBe(Limit::Mangan)
        ->and($readings[0]->reading)->toEqual(evaluate($hand)->reading())
        ->and([$readings[1]->han, $readings[1]->fu()])->toBe([4, 20])
        ->and(array_map(fn (YakuHan $yaku) => $yaku->yaku, $readings[1]->reading->yaku))
        ->toEqualCanonicalizing([Yaku::MenzenTsumo, Yaku::Tanyao, Yaku::Pinfu, Yaku::Iipeikou]);
});

it('ne donne aucune lecture pour un yakuman', function () {
    expect(Evaluator::scoredReadings(hand('111m 999m 111p 999p 1s', '1s'), Rules::default()))->toBe([]);
});

it('ajoute honba et bâtons de riichi seulement quand la table les compte', function () {
    $hand = hand('123m 456m 234p 678s 99s', '4m')->withTable(honba: 2, riichiSticks: 1);

    expect(evaluate($hand)->payment()->ron)->toBe(1000)
        ->and(evaluate($hand)->payment()->gain())->toBe(1000)
        ->and(evaluate($hand, new Rules(honba: true))->payment()->ron)->toBe(1600)
        ->and(evaluate($hand, new Rules(honba: true))->payment()->gain())->toBe(2600);
});
