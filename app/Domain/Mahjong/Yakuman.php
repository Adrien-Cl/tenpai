<?php

namespace App\Domain\Mahjong;

/**
 * Les mains limites. Le générateur ne les distribue jamais : elles servent à les
 * reconnaître, et à ne pas les accepter à tort quand on les annonce.
 */
enum Yakuman: string
{
    case Kokushi = 'kokushi';
    case Suuankou = 'suuankou';
    case Daisangen = 'daisangen';
    case Shousuushii = 'shousuushii';
    case Daisuushii = 'daisuushii';
    case Tsuuiisou = 'tsuuiisou';
    case Chinroutou = 'chinroutou';
    case Ryuuiisou = 'ryuuiisou';
    case Chuuren = 'chuuren';
    case Suukantsu = 'suukantsu';
    case Tenhou = 'tenhou';
    case Chiihou = 'chiihou';

    public function label(): string
    {
        return match ($this) {
            self::Kokushi => 'Kokushi musou',
            self::Suuankou => 'Suuankou',
            self::Daisangen => 'Daisangen',
            self::Shousuushii => 'Shousuushii',
            self::Daisuushii => 'Daisuushii',
            self::Tsuuiisou => 'Tsuuiisou',
            self::Chinroutou => 'Chinroutou',
            self::Ryuuiisou => 'Ryuuiisou',
            self::Chuuren => 'Chuuren poutou',
            self::Suukantsu => 'Suukantsu',
            self::Tenhou => 'Tenhou',
            self::Chiihou => 'Chiihou',
        };
    }

    public function gloss(): string
    {
        return Text::get(match ($this) {
            self::Kokushi => 'treize orphelins',
            self::Suuankou => 'quatre brelans cachés',
            self::Daisangen => 'grands trois dragons',
            self::Shousuushii => 'petits quatre vents',
            self::Daisuushii => 'grands quatre vents',
            self::Tsuuiisou => 'tout honneurs',
            self::Chinroutou => 'tout terminales',
            self::Ryuuiisou => 'tout vert',
            self::Chuuren => 'neuf portes',
            self::Suukantsu => 'quatre carrés',
            self::Tenhou => 'main du ciel',
            self::Chiihou => 'main de la terre',
        });
    }

    public function description(): string
    {
        return Text::get(match ($this) {
            self::Kokushi => 'Une de chaque terminale et de chaque honneur, plus un double de l’une d’elles.',
            self::Suuankou => 'Quatre brelans ou carrés fermés, main fermée.',
            self::Daisangen => 'Trois brelans de dragons.',
            self::Shousuushii => 'Trois brelans de vents et une paire du quatrième.',
            self::Daisuushii => 'Quatre brelans de vents.',
            self::Tsuuiisou => 'Uniquement des vents et des dragons.',
            self::Chinroutou => 'Uniquement des 1 et des 9.',
            self::Ryuuiisou => 'Uniquement 2, 3, 4, 6, 8 de bambous et Hatsu.',
            self::Chuuren => '1-1-1-2-3-4-5-6-7-8-9-9-9 d’une famille plus une tuile de la même famille, main fermée.',
            self::Suukantsu => 'Quatre kan.',
            self::Tenhou => 'Le donneur gagne sur sa main de départ.',
            self::Chiihou => 'Un non-donneur gagne sur sa première pioche, sans appel avant.',
        });
    }

    /**
     * @return list<string>
     */
    public function aliases(): array
    {
        return match ($this) {
            self::Kokushi => ['kokushi musou', 'thirteen orphans'],
            self::Suuankou => ['suu ankou'],
            self::Shousuushii => ['shousuushi'],
            self::Daisuushii => ['daisuushi'],
            self::Tsuuiisou => ['tsuisou'],
            self::Ryuuiisou => ['ryuisou', 'all green'],
            self::Chuuren => ['chuuren poutou', 'chuuren pooto', 'nine gates'],
            self::Daisangen, self::Chinroutou, self::Suukantsu, self::Tenhou, self::Chiihou => [],
        };
    }
}
