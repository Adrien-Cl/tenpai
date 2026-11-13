<?php

use App\Domain\Mahjong\Hand\Decomposer;
use App\Domain\Mahjong\Hand\Group;
use App\Domain\Mahjong\Tile;

function readings(string $notation): array
{
    return array_map(
        fn ($decomposition) => [
            'pair' => Tile::label($decomposition->pair),
            'groups' => array_map(fn (Group $group) => $group->kind->value.':'.Tile::label($group->tile), $decomposition->groups),
        ],
        Decomposer::decompose(Tile::counts(tiles($notation))),
    );
}

it('trouve toutes les lectures d’une main ambiguë', function () {
    expect(readings('222333444m 55p 678s'))->toEqualCanonicalizing([
        ['pair' => '5p', 'groups' => ['trip:2m', 'trip:3m', 'trip:4m', 'seq:6s']],
        ['pair' => '5p', 'groups' => ['seq:2m', 'seq:2m', 'seq:2m', 'seq:6s']],
    ]);
});

it('ne trouve rien quand les tuiles ne forment pas une main', function () {
    expect(readings('13579m 2468p 1357s 1z'))->toBe([]);
});

it('ne fait pas de suite à cheval sur deux familles', function () {
    expect(readings('89m 1p 123s 456s 789s 11z'))->toBe([]);
});
