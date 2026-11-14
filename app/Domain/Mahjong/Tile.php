<?php

namespace App\Domain\Mahjong;

use InvalidArgumentException;

/**
 * Les 34 tuiles du riichi, numérotées comme dans le moteur de référence :
 * 0-8 caractères (man), 9-17 cercles (pin), 18-26 bambous (sou),
 * 27 Est, 28 Sud, 29 Ouest, 30 Nord, 31 Haku, 32 Hatsu, 33 Chun.
 */
final class Tile
{
    public const int COUNT = 34;

    public const int EAST = 27;

    public const int SOUTH = 28;

    public const int WEST = 29;

    public const int NORTH = 30;

    public const int HAKU = 31;

    public const int HATSU = 32;

    public const int CHUN = 33;

    /** Les cinq de chaque famille, qui peuvent être rouges. */
    public const array FIVES = [4, 13, 22];

    /** Tuiles du « tout vert » (ryuuiisou) : 2, 3, 4, 6, 8 de bambous et Hatsu. */
    public const array GREENS = [19, 20, 21, 23, 25, 32];

    private const array SUIT_LETTERS = ['m', 'p', 's'];

    private const array SUIT_NAMES = ['caractères', 'cercles', 'bambous'];

    private const array HONOR_LABELS = ['Est', 'Sud', 'Ouest', 'Nord', 'Haku', 'Hatsu', 'Chun'];

    private const array HONOR_NAMES = ['Est', 'Sud', 'Ouest', 'Nord', 'Haku (dragon blanc)', 'Hatsu (dragon vert)', 'Chun (dragon rouge)'];

    public static function isHonor(int $tile): bool
    {
        return $tile >= 27;
    }

    public static function isTerminal(int $tile): bool
    {
        return $tile < 27 && ($tile % 9 === 0 || $tile % 9 === 8);
    }

    public static function isTerminalOrHonor(int $tile): bool
    {
        return self::isHonor($tile) || self::isTerminal($tile);
    }

    public static function isSimple(int $tile): bool
    {
        return ! self::isTerminalOrHonor($tile);
    }

    public static function isDragon(int $tile): bool
    {
        return $tile >= 31;
    }

    public static function isWind(int $tile): bool
    {
        return $tile >= 27 && $tile <= 30;
    }

    /**
     * Famille : 0 caractères, 1 cercles, 2 bambous, 3 honneurs.
     */
    public static function suit(int $tile): int
    {
        return $tile < 27 ? intdiv($tile, 9) : 3;
    }

    /**
     * Valeur de 1 à 9 pour une tuile numérotée.
     */
    public static function number(int $tile): int
    {
        return $tile % 9 + 1;
    }

    /**
     * La tuile désignée par un indicateur de dora : la suivante dans sa famille,
     * en boucle (9 → 1, Nord → Est, Chun → Haku).
     */
    public static function doraFrom(int $indicator): int
    {
        if ($indicator < 27) {
            return intdiv($indicator, 9) * 9 + ($indicator % 9 + 1) % 9;
        }

        if ($indicator <= 30) {
            return 27 + ($indicator - 27 + 1) % 4;
        }

        return 31 + ($indicator - 31 + 1) % 3;
    }

    /**
     * Libellé court : « 5m », « Est », « Haku ».
     */
    public static function label(int $tile, bool $red = false): string
    {
        $label = $tile < 27
            ? self::number($tile).self::SUIT_LETTERS[self::suit($tile)]
            : Text::get(self::HONOR_LABELS[$tile - 27]);

        return $red ? Text::get(':tile rouge', ['tile' => $label]) : $label;
    }

    /**
     * Nom complet, pour les lecteurs d'écran : « 5 de cercles rouge ».
     */
    public static function name(int $tile, bool $red = false): string
    {
        $name = $tile < 27
            ? Text::get(':number de '.self::SUIT_NAMES[self::suit($tile)], ['number' => self::number($tile)])
            : Text::get(self::HONOR_NAMES[$tile - 27]);

        return $red ? Text::get(':tile rouge', ['tile' => $name]) : $name;
    }

    /**
     * Lit une notation compacte (« 123m 456p 11z ») et renvoie les tuiles dans l'ordre.
     * Le chiffre 0 vaut un 5 ; les honneurs s'écrivent 1z à 7z (Est, Sud, Ouest, Nord, Haku, Hatsu, Chun).
     *
     * @return list<int>
     */
    public static function parse(string $notation): array
    {
        $tiles = [];
        $digits = [];

        foreach (str_split(str_replace(' ', '', $notation)) as $char) {
            if (ctype_digit($char)) {
                $digits[] = (int) $char;

                continue;
            }

            $base = match ($char) {
                'm' => 0,
                'p' => 9,
                's' => 18,
                'z' => 27,
                default => throw new InvalidArgumentException("Famille inconnue « {$char} » dans « {$notation} »."),
            };

            foreach ($digits as $digit) {
                $value = $digit === 0 ? 5 : $digit;

                if ($char === 'z' && ($value < 1 || $value > 7)) {
                    throw new InvalidArgumentException("Honneur inconnu « {$digit}z ».");
                }

                $tiles[] = $base + $value - 1;
            }

            $digits = [];
        }

        if ($digits !== []) {
            throw new InvalidArgumentException("Famille manquante à la fin de « {$notation} ».");
        }

        return $tiles;
    }

    /**
     * @param  list<int>  $tiles
     * @return array<int, int> nombre d'exemplaires par tuile, indexé de 0 à 33
     */
    public static function counts(array $tiles): array
    {
        $counts = array_fill(0, self::COUNT, 0);

        foreach ($tiles as $tile) {
            $counts[$tile]++;
        }

        return $counts;
    }
}
