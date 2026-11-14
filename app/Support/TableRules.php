<?php

namespace App\Support;

use App\Domain\Mahjong\Rules;
use Illuminate\Http\Request;

/**
 * Les règles choisies par le joueur, gardées dans un cookie (pas de compte).
 * Sans cookie, ou avec un cookie illisible : les règles par défaut.
 */
final class TableRules
{
    public const string COOKIE = 'regles';

    /** Les options, dans l'ordre du panneau : [clé, libellé, aide]. */
    public const array OPTIONS = [
        ['aka', 'Cinq rouges (aka-dora)', 'Un 5 rouge par famille, chacun vaut un dora.'],
        ['kiriage', 'Kiriage mangan', '4 han 30 fu et 3 han 60 fu comptent comme mangan.'],
        ['doubleWind4', 'Double vent à 4 fu', 'Une paire de vent à la fois du siège et de la manche : 4 fu plutôt que 2.'],
    ];

    public static function fromRequest(Request $request): Rules
    {
        $stored = json_decode((string) $request->cookie(self::COOKIE), true);

        return is_array($stored) ? self::fromArray($stored) : Rules::default();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function fromArray(array $values): Rules
    {
        $default = Rules::default();

        return new Rules(
            aka: (bool) ($values['aka'] ?? $default->aka),
            kiriage: (bool) ($values['kiriage'] ?? $default->kiriage),
            doubleWind4: (bool) ($values['doubleWind4'] ?? $default->doubleWind4),
        );
    }

    /**
     * @return array{aka: bool, kiriage: bool, doubleWind4: bool}
     */
    public static function toArray(Rules $rules): array
    {
        return ['aka' => $rules->aka, 'kiriage' => $rules->kiriage, 'doubleWind4' => $rules->doubleWind4];
    }

    public static function isDefault(Rules $rules): bool
    {
        return self::toArray($rules) === self::toArray(Rules::default());
    }

    /**
     * Les règles en une ligne : « cinq rouges, sans kiriage, double vent à 4 fu ».
     */
    public static function describe(Rules $rules): string
    {
        return implode(', ', [
            $rules->aka ? __('cinq rouges') : __('sans rouges'),
            $rules->kiriage ? __('kiriage mangan') : __('sans kiriage'),
            $rules->doubleWind4 ? __('double vent à 4 fu') : __('double vent à 2 fu'),
        ]);
    }
}
