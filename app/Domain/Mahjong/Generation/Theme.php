<?php

namespace App\Domain\Mahjong\Generation;

/**
 * Le thème oriente le tirage vers un yaku, pour varier les mains.
 */
enum Theme: string
{
    case Free = 'libre';
    case Tanyao = 'tanyao';
    case Pinfu = 'pinfu';
    case Yakuhai = 'yakuhai';
    case Honitsu = 'honitsu';
    case Chinitsu = 'chinitsu';
    case Toitoi = 'toitoi';
    case Chiitoitsu = 'chiitoi';
    case Sanshoku = 'sanshoku';
    case Ittsu = 'ittsu';
    case Chanta = 'chanta';
    case Junchan = 'junchan';
    case Iipeikou = 'iipeikou';
    case Ryanpeikou = 'ryanpeikou';
    case Sanankou = 'sanankou';
    case Shousangen = 'shousangen';
    case Honroutou = 'honroutou';
    case Doukou = 'doukou';

    public function weight(): float
    {
        return match ($this) {
            self::Free => 3,
            self::Tanyao, self::Pinfu => 2,
            self::Yakuhai => 2.5,
            self::Honitsu => 1.5,
            self::Chinitsu, self::Junchan => 0.6,
            self::Toitoi, self::Chiitoitsu, self::Sanshoku, self::Ittsu, self::Iipeikou => 1,
            self::Chanta, self::Sanankou => 0.8,
            self::Ryanpeikou => 0.25,
            self::Shousangen => 0.5,
            self::Honroutou, self::Doukou => 0.3,
        };
    }

    public static function pick(SeededRandom $random): self
    {
        $total = array_sum(array_map(fn (self $theme): float => $theme->weight(), self::cases()));
        $roll = $random->float() * $total;

        foreach (self::cases() as $theme) {
            $roll -= $theme->weight();

            if ($roll <= 0) {
                return $theme;
            }
        }

        return self::Free;
    }
}
