<?php

use App\Support\Format;
use App\View\ShowcaseHand;

it('compte chaque main de l’accueil comme prévu', function (int $index, bool $tsumo, int $han, string $points) {
    $showcase = ShowcaseHand::all()[$index];
    $evaluation = $showcase->evaluate();
    $payment = $evaluation->payment();

    $shown = match (true) {
        ! $tsumo => Format::points($payment->ron),
        $evaluation->dealer => Format::points($payment->each),
        default => Format::points($payment->fromOthers).' / '.Format::points($payment->fromDealer),
    };

    expect($evaluation->isValid())->toBeTrue()
        ->and($showcase->hand->tsumo)->toBe($tsumo)
        ->and($showcase->hand->isClosed())->toBeTrue()
        ->and($showcase->waiting())->toHaveCount(13)
        ->and($evaluation->han)->toBe($han)
        ->and($shown)->toBe($points);
})->with([
    'riichi tsumo pinfu tanyao' => [0, true, 5, "2\u{00A0}000 / 4\u{00A0}000"],
    'chiitoitsu en ron' => [1, false, 3, "3\u{00A0}200"],
    'honitsu du donneur en tsumo' => [2, true, 7, "6\u{00A0}000"],
    'sanshoku en ron' => [3, false, 5, "8\u{00A0}000"],
    'iipeikou du donneur en ron' => [4, false, 4, "11\u{00A0}600"],
    'chun en kanchan' => [5, false, 3, "5\u{00A0}200"],
]);

it('n’oublie aucune main dans les cas testés', function () {
    expect(ShowcaseHand::all())->toHaveCount(6);
});

it('pose une des mains sur la table de l’accueil', function () {
    $content = $this->get('/')->assertOk()->content();

    expect($content)->toMatch('/Tsumo !|Ron !/')
        ->and($content)->toContain('class="tiles hero-rack"');
});
