<?php

use App\Domain\Mahjong\Hand\Hand;
use App\Domain\Mahjong\Hand\Meld;
use App\Domain\Mahjong\Hand\MeldType;
use App\Domain\Mahjong\Tile;
use App\Domain\Mahjong\Wind;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Cas de test
|--------------------------------------------------------------------------
|
| Seuls les tests « Feature » démarrent Laravel. Les tests du moteur (Unit)
| tournent sur des classes PHP pures.
|
*/

pest()->extend(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Aides
|--------------------------------------------------------------------------
*/

/**
 * Lit une notation compacte de tuiles : « 123m 456p 77z ».
 * Familles : m (caractères), p (cercles), s (bambous), z (1-4 vents Est→Nord, 5-7 Haku, Hatsu, Chun).
 *
 * @return list<int>
 */
function tiles(string $notation): array
{
    return Tile::parse($notation);
}

function tile(string $notation): int
{
    return Tile::parse($notation)[0];
}

/**
 * Construit une main de test à partir de notations compactes.
 *
 * @param  array<string, mixed>  $options
 */
function hand(string $closed, string $win, array $options = []): Hand
{
    $melds = array_map(function (string $meld): Meld {
        [$type, $tile] = explode(':', $meld);

        return new Meld(MeldType::from($type), tile($tile));
    }, $options['melds'] ?? []);

    return new Hand(
        closed: tiles($closed),
        melds: $melds,
        win: tile($win),
        tsumo: $options['tsumo'] ?? false,
        seat: $options['seat'] ?? Wind::South,
        round: $options['round'] ?? Wind::East,
        riichi: $options['riichi'] ?? false,
        doubleRiichi: $options['doubleRiichi'] ?? false,
        ippatsu: $options['ippatsu'] ?? false,
        haitei: $options['haitei'] ?? false,
        rinshan: $options['rinshan'] ?? false,
        chankan: $options['chankan'] ?? false,
        // Par défaut, l'indicateur Ouest désigne le Nord : aucune main de test n'en contient.
        dora: isset($options['dora']) ? tiles($options['dora']) : [tile('3z')],
        ura: isset($options['ura']) ? tiles($options['ura']) : [],
        aka: $options['aka'] ?? 0,
    );
}
