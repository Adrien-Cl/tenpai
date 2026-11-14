<?php

use App\Domain\Mahjong\Hand\Meld;
use App\Domain\Mahjong\Hand\MeldType;
use App\Domain\Mahjong\Hand\WaitFinder;
use App\Domain\Mahjong\Tile;

/**
 * Les attentes d'une main, en notation courte : « 1m, 4m ».
 *
 * @param  list<string>  $melds  appels « type:tuile », comme pour hand()
 * @return list<string>
 */
function waitsOf(string $closed, array $melds = []): array
{
    $melds = array_map(function (string $meld): Meld {
        [$type, $tile] = explode(':', $meld);

        return new Meld(MeldType::from($type), tile($tile));
    }, $melds);

    return array_map(fn (int $tile): string => Tile::label($tile), WaitFinder::waits(tiles($closed), $melds));
}

it('trouve les attentes d’une main standard', function (string $closed, array $waits) {
    expect(waitsOf($closed))->toBe($waits);
})->with([
    'ryanmen' => ['23m 456p 789s 111z 55s', ['1m', '4m']],
    'kanchan' => ['13m 456p 789s 111z 55s', ['2m']],
    'penchan' => ['12m 456p 789s 111z 55s', ['3m']],
    'shanpon' => ['22m 456p 789s 111z 55s', ['2m', '5s']],
    'tanki' => ['123m 456p 789s 111z 5s', ['5s']],
    'nobetan' => ['1234m 567p 888s 123s', ['1m', '4m']],
]);

it('trouve les neuf attentes des neuf portes', function () {
    expect(waitsOf('1112345678999m'))->toBe(['1m', '2m', '3m', '4m', '5m', '6m', '7m', '8m', '9m']);
});

it('trouve l’attente des sept paires', function () {
    expect(waitsOf('113355m 7799p 22s 5z'))->toBe(['Haku']);
});

it('trouve les treize attentes du kokushi musou', function () {
    expect(waitsOf('19m 19p 19s 1234567z'))->toHaveCount(13);
});

it('tient compte des appels', function () {
    expect(waitsOf('45m 789s 55p', ['pon:1z']))->toBe(['3m', '6m']);
});

it('écarte une attente dont les quatre exemplaires sont déjà dans la main', function () {
    $closed = tiles('1111m 234p 567p 888s');

    expect(WaitFinder::waits($closed, []))->toBe([])
        ->and(WaitFinder::exhausted($closed, []))->toBe([tile('1m')]);
});

it('compte les tuiles d’un kan pour savoir si une attente est épuisée', function () {
    $closed = tiles('46p 234m 567s 99s');
    $kan = [new Meld(MeldType::ClosedKan, tile('5p'))];

    expect(WaitFinder::waits($closed, $kan))->toBe([])
        ->and(WaitFinder::exhausted($closed, $kan))->toBe([tile('5p')]);
});
