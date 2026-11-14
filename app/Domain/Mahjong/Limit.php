<?php

namespace App\Domain\Mahjong;

/**
 * Les limites de points : au-delà, les fu ne comptent plus.
 */
enum Limit: string
{
    case Mangan = 'mangan';
    case Haneman = 'haneman';
    case Baiman = 'baiman';
    case Sanbaiman = 'sanbaiman';
    case Kazoe = 'kazoe';

    public function label(): string
    {
        return Text::get(match ($this) {
            self::Mangan => 'Mangan',
            self::Haneman => 'Haneman',
            self::Baiman => 'Baiman',
            self::Sanbaiman => 'Sanbaiman',
            self::Kazoe => 'Yakuman compté',
        });
    }

    /**
     * Points de base de la limite.
     */
    public function base(): int
    {
        return match ($this) {
            self::Mangan => 2000,
            self::Haneman => 3000,
            self::Baiman => 4000,
            self::Sanbaiman => 6000,
            self::Kazoe => 8000,
        };
    }

    /**
     * Nombre de han à partir duquel la limite s'applique d'office.
     */
    public function minimumHan(): int
    {
        return match ($this) {
            self::Mangan => 5,
            self::Haneman => 6,
            self::Baiman => 8,
            self::Sanbaiman => 11,
            self::Kazoe => 13,
        };
    }

    public function condition(): string
    {
        return Text::get(match ($this) {
            self::Mangan => '5 han, ou 4 han dès 40 fu, 3 han dès 70 fu',
            self::Haneman => '6 ou 7 han',
            self::Baiman => '8 à 10 han',
            self::Sanbaiman => '11 ou 12 han',
            self::Kazoe => '13 han et plus',
        });
    }
}
