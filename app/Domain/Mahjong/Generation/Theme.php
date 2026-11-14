<?php

namespace App\Domain\Mahjong\Generation;

use App\Domain\Mahjong\Text;
use App\Domain\Mahjong\Yaku;

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

    /**
     * Les yaku visés : une main tirée pour ce thème en contient au moins un. Aucun pour la main libre.
     *
     * @return list<Yaku>
     */
    public function targets(): array
    {
        return match ($this) {
            self::Free => [],
            self::Yakuhai => [Yaku::Haku, Yaku::Hatsu, Yaku::Chun, Yaku::SeatWind, Yaku::RoundWind],
            self::Doukou => [Yaku::SanshokuDoukou],
            default => [Yaku::from($this->value)],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Free => Text::get('Main libre'),
            self::Yakuhai => 'Yakuhai',
            default => $this->targets()[0]->label(),
        };
    }

    public function gloss(): string
    {
        return match ($this) {
            self::Free => Text::get('tous les yaku'),
            self::Yakuhai => Text::get('dragons et vents'),
            default => $this->targets()[0]->gloss(),
        };
    }

    /**
     * Les thèmes que l'on peut choisir pour s'entraîner, dans l'ordre du barème.
     *
     * @return list<self>
     */
    public static function trainable(): array
    {
        return [
            self::Tanyao, self::Pinfu, self::Yakuhai, self::Iipeikou, self::Chiitoitsu, self::Toitoi,
            self::Sanankou, self::Sanshoku, self::Doukou, self::Ittsu, self::Chanta, self::Honroutou,
            self::Shousangen, self::Ryanpeikou, self::Junchan, self::Honitsu, self::Chinitsu,
        ];
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
