<?php

namespace App\Support;

final class Format
{
    /**
     * Nombre à la française : 12 000, avec une espace insécable.
     * (L'espace fine U+202F manque dans Dela Gothic One, la police des chiffres.)
     */
    public static function points(int $points): string
    {
        return number_format($points, 0, ',', "\u{00A0}");
    }
}
