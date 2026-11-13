<?php

use App\View\Season;

it('choisit la saison du décor selon le mois', function (string $date, Season $season) {
    expect(Season::at(new DateTimeImmutable($date)))->toBe($season);
})->with([
    'mars' => ['2026-03-01', Season::Spring],
    'mai' => ['2026-05-31', Season::Spring],
    'juin' => ['2026-06-01', Season::Summer],
    'août' => ['2026-08-31', Season::Summer],
    'novembre' => ['2026-11-13', Season::Autumn],
    'décembre' => ['2026-12-01', Season::Winter],
    'février' => ['2027-02-28', Season::Winter],
]);

it('accepte une saison demandée, et ignore une valeur inconnue', function () {
    $november = new DateTimeImmutable('2026-11-13');

    expect(Season::current($november, 'hiver'))->toBe(Season::Winter)
        ->and(Season::current($november, 'mousson'))->toBe(Season::Autumn)
        ->and(Season::current($november))->toBe(Season::Autumn);
});
