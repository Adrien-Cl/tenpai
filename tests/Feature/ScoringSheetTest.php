<?php

use App\Domain\Mahjong\Yaku;
use App\View\ScoringSheet;

it('sait quelles combinaisons han × fu existent', function (int $han, int $fu, bool $tsumo, bool $possible) {
    expect(ScoringSheet::isPossible($han, $fu, $tsumo))->toBe($possible);
})->with([
    '20 fu en ron' => [2, 20, false, false],
    '20 fu en tsumo, 1 han' => [1, 20, true, false],
    'pinfu tsumo, 2 han' => [2, 20, true, true],
    'chiitoitsu ron, 1 han' => [1, 25, false, false],
    'chiitoitsu ron, 2 han' => [2, 25, false, true],
    'chiitoitsu tsumo, 2 han' => [2, 25, true, false],
    'chiitoitsu tsumo, 3 han' => [3, 25, true, true],
    '30 fu, 1 han' => [1, 30, false, true],
]);

it('regroupe la zone mangan en une seule bande par ligne', function () {
    $rows = collect((new ScoringSheet)->pointGrid(dealer: false, tsumo: false))->keyBy('fu');

    expect($rows->has(20))->toBeFalse()
        ->and($rows[30]['limit'])->toBeNull()
        ->and(array_column($rows[30]['cells'], 'value'))->toBe(["1\u{00A0}000", "2\u{00A0}000", "3\u{00A0}900", "7\u{00A0}700"])
        ->and($rows[40]['limit'])->toMatchArray(['from' => 4, 'span' => 1, 'label' => 'Mangan', 'value' => "8\u{00A0}000"])
        ->and($rows[70]['limit'])->toMatchArray(['from' => 3, 'span' => 2])
        ->and(count($rows[70]['cells']))->toBe(2);
});

it('affiche les paiements du tsumo dans le tableau', function () {
    $rows = collect((new ScoringSheet)->pointGrid(dealer: false, tsumo: true))->keyBy('fu');
    $dealerRows = collect((new ScoringSheet)->pointGrid(dealer: true, tsumo: true))->keyBy('fu');

    expect($rows[20]['cells'][1])->toMatchArray(['value' => '400', 'detail' => '700'])
        ->and($rows[40]['limit'])->toMatchArray(['value' => "2\u{00A0}000", 'detail' => "4\u{00A0}000"])
        ->and($dealerRows[30]['cells'][2])->toMatchArray(['value' => "2\u{00A0}000", 'detail' => null]);
});

it('prépare les réponses du calculateur avec leur calcul', function () {
    $calculator = (new ScoringSheet)->calculator();

    expect($calculator['00-3-30'])->toMatchArray(['possible' => true, 'value' => "3\u{00A0}900", 'limit' => null])
        ->and($calculator['00-3-30']['formula'][1])->toContain('arrondi à 3')
        ->and($calculator['10-6-0'])->toMatchArray(['value' => "18\u{00A0}000", 'limit' => 'Haneman'])
        ->and($calculator['00-2-20'])->toBe(['possible' => false]);
});

it('range à part les yaku de main fermée quand la main est ouverte', function () {
    $open = (new ScoringSheet)->yakuByHan(open: true);
    $closed = (new ScoringSheet)->yakuByHan(open: false);

    expect($open[0])->toContain(Yaku::Riichi, Yaku::Pinfu)
        ->and($open[5])->toBe([Yaku::Chinitsu])
        ->and(array_key_last($open))->toBe(0)
        ->and($closed)->not->toHaveKey(0)
        ->and($closed[6])->toBe([Yaku::Chinitsu]);
});

it('garde les yaku des tests JS identiques à ceux du serveur', function () {
    $fixture = json_decode(file_get_contents(__DIR__.'/../js/fixtures/yaku-entries.json'), true);

    expect($fixture)->toBe(ScoringSheet::yakuEntries(), 'tests/js/fixtures/yaku-entries.json est périmé : régénérez-le depuis ScoringSheet::yakuEntries().');
});
