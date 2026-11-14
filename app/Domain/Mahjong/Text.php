<?php

namespace App\Domain\Mahjong;

use Closure;

/**
 * Les textes du moteur. Le français est la langue source : chaque phrase sert aussi de clé de traduction.
 *
 * Le moteur ne connaît pas Laravel. L'application branche sa traduction avec translateWith() ;
 * sans traduction (tests du moteur), la phrase française est rendue telle quelle.
 */
final class Text
{
    /** @var (Closure(string, array<string, string|int>): string)|null */
    private static ?Closure $translator = null;

    /**
     * @param  (Closure(string, array<string, string|int>): string)|null  $translator
     */
    public static function translateWith(?Closure $translator): void
    {
        self::$translator = $translator;
    }

    /**
     * Une phrase, avec ses valeurs « :nom » remplacées.
     *
     * @param  array<string, string|int>  $replace
     */
    public static function get(string $text, array $replace = []): string
    {
        if (self::$translator !== null) {
            return (self::$translator)($text, $replace);
        }

        // Les noms les plus longs d'abord : « :fu » ne doit pas mordre sur « :full ».
        uksort($replace, fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($replace as $key => $value) {
            $text = str_replace(':'.$key, (string) $value, $text);
        }

        return $text;
    }
}
