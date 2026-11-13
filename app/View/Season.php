<?php

namespace App\View;

use DateTimeInterface;

/**
 * La saison du décor, comme les variantes saisonnières des estampes « bambou et moineau » de Hiroshige.
 */
enum Season: string
{
    case Spring = 'printemps';
    case Summer = 'ete';
    case Autumn = 'automne';
    case Winter = 'hiver';

    public static function at(DateTimeInterface $date): self
    {
        return match ((int) $date->format('n')) {
            3, 4, 5 => self::Spring,
            6, 7, 8 => self::Summer,
            9, 10, 11 => self::Autumn,
            default => self::Winter,
        };
    }

    /**
     * La saison du jour, ou celle demandée (?saison=hiver) pour voir les autres décors.
     */
    public static function current(DateTimeInterface $date, ?string $requested = null): self
    {
        return self::tryFrom((string) $requested) ?? self::at($date);
    }

    public function label(): string
    {
        return match ($this) {
            self::Spring => 'printemps',
            self::Summer => 'été',
            self::Autumn => 'automne',
            self::Winter => 'hiver',
        };
    }

    /**
     * Le mot de saison (kigo) qui signe le décor, comme une inscription d'estampe.
     */
    public function kigo(): string
    {
        return match ($this) {
            self::Spring => 'Cerisiers en fleurs',
            self::Summer => 'Bambous et moineaux',
            self::Autumn => 'Érables et lune',
            self::Winter => 'Neige sur les bambous',
        };
    }
}
