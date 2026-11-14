<?php

namespace App\Support;

final class Format
{
    /**
     * Nombre dans la langue de la page : 12 000 en français, avec une espace insécable
     * (l'espace fine U+202F manque dans Dela Gothic One, la police des chiffres) ; 12,000 en anglais.
     */
    public static function points(int $points): string
    {
        return app()->getLocale() === 'en'
            ? number_format($points)
            : number_format($points, 0, ',', "\u{00A0}");
    }
}
