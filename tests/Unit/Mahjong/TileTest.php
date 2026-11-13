<?php

use App\Domain\Mahjong\Tile;

it('désigne la tuile suivante comme dora, en boucle dans chaque famille', function (string $indicator, string $dora) {
    expect(Tile::doraFrom(tile($indicator)))->toBe(tile($dora));
})->with([
    '4m → 5m' => ['4m', '5m'],
    '9m → 1m' => ['9m', '1m'],
    '9p → 1p' => ['9p', '1p'],
    '9s → 1s' => ['9s', '1s'],
    'Est → Sud' => ['1z', '2z'],
    'Nord → Est' => ['4z', '1z'],
    'Haku → Hatsu' => ['5z', '6z'],
    'Chun → Haku' => ['7z', '5z'],
]);

it('classe les tuiles', function () {
    expect(Tile::isTerminal(tile('1m')))->toBeTrue()
        ->and(Tile::isTerminal(tile('9s')))->toBeTrue()
        ->and(Tile::isTerminal(tile('5p')))->toBeFalse()
        ->and(Tile::isHonor(tile('1z')))->toBeTrue()
        ->and(Tile::isSimple(tile('2m')))->toBeTrue()
        ->and(Tile::isSimple(tile('1z')))->toBeFalse()
        ->and(Tile::isDragon(tile('5z')))->toBeTrue()
        ->and(Tile::isWind(tile('4z')))->toBeTrue()
        ->and(Tile::isWind(tile('5z')))->toBeFalse()
        ->and(Tile::suit(tile('7s')))->toBe(2)
        ->and(Tile::suit(tile('7z')))->toBe(3);
});

it('lit la notation compacte', function () {
    expect(tiles('123m 0p 77z'))->toBe([0, 1, 2, 13, 33, 33]);
});

it('refuse une notation invalide', function () {
    tiles('123x');
})->throws(InvalidArgumentException::class);

it('nomme les tuiles en français', function () {
    expect(Tile::label(tile('5p'), red: true))->toBe('5p rouge')
        ->and(Tile::name(tile('5p'), red: true))->toBe('5 de cercles rouge')
        ->and(Tile::name(tile('6z')))->toBe('Hatsu (dragon vert)');
});
