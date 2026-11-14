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
        ['kazoe', 'Yakuman compté', '13 han et plus valent un yakuman ; sinon, ils restent un sanbaiman.'],
        ['honba', 'Honba et bâtons de riichi', 'La table en porte parfois : 300 points par honba, 1 000 par bâton, à ajouter au gain.'],
    ];

    /**
     * Les règles de calcul des grandes familles de tables. Les honba n'en font pas partie :
     * ils dépendent de l'entraînement voulu, pas de la table.
     *
     * @var array<string, array{aka: bool, kiriage: bool, doubleWind4: bool, kazoe: bool}>
     */
    public const array PRESETS = [
        'japon' => ['aka' => true, 'kiriage' => false, 'doubleWind4' => true, 'kazoe' => true],
        'tournoi' => ['aka' => false, 'kiriage' => true, 'doubleWind4' => false, 'kazoe' => false],
    ];

    public static function fromRequest(Request $request): Rules
    {
        $stored = json_decode((string) $request->cookie(self::COOKIE), true);

        return is_array($stored) ? self::fromArray($stored) : Rules::default();
    }

    /**
     * Une option absente (cookie d'avant cette option) garde sa valeur par défaut.
     *
     * @param  array<string, mixed>  $values
     */
    public static function fromArray(array $values): Rules
    {
        $default = Rules::default();

        return new Rules(
            aka: (bool) ($values['aka'] ?? $default->aka),
            kiriage: (bool) ($values['kiriage'] ?? $default->kiriage),
            doubleWind4: (bool) ($values['doubleWind4'] ?? $default->doubleWind4),
            kazoe: (bool) ($values['kazoe'] ?? $default->kazoe),
            honba: (bool) ($values['honba'] ?? $default->honba),
        );
    }

    /**
     * @return array{aka: bool, kiriage: bool, doubleWind4: bool, kazoe: bool, honba: bool}
     */
    public static function toArray(Rules $rules): array
    {
        return [
            'aka' => $rules->aka,
            'kiriage' => $rules->kiriage,
            'doubleWind4' => $rules->doubleWind4,
            'kazoe' => $rules->kazoe,
            'honba' => $rules->honba,
        ];
    }

    public static function isDefault(Rules $rules): bool
    {
        return self::toArray($rules) === self::toArray(Rules::default());
    }

    /**
     * Le préréglage dont les règles de calcul sont celles-ci, s'il y en a un.
     */
    public static function preset(Rules $rules): ?string
    {
        $values = self::toArray($rules);
        unset($values['honba']);

        $found = array_search($values, self::PRESETS, true);

        return $found === false ? null : $found;
    }

    /**
     * @return array{label: string, tables: string}
     */
    public static function presetLabel(string $preset): array
    {
        return match ($preset) {
            'japon' => ['label' => __('Salle japonaise et en ligne'), 'tables' => 'Tenhou, Mahjong Soul'],
            'tournoi' => ['label' => __('Tournoi'), 'tables' => 'EMA, WRC'],
        };
    }

    /**
     * Le nom des règles en cours : le préréglage, ou « Vos règles ».
     */
    public static function name(Rules $rules): string
    {
        $preset = self::preset($rules);

        return $preset === null ? __('Vos règles') : self::presetLabel($preset)['label'];
    }

    /**
     * Les règles de calcul en une ligne : « cinq rouges, sans kiriage, double vent à 4 fu, yakuman compté ».
     */
    public static function describe(Rules $rules): string
    {
        return implode(', ', [
            $rules->aka ? __('cinq rouges') : __('sans rouges'),
            $rules->kiriage ? __('kiriage mangan') : __('sans kiriage'),
            $rules->doubleWind4 ? __('double vent à 4 fu') : __('double vent à 2 fu'),
            $rules->kazoe ? __('yakuman compté') : __('13 han plafonnés au sanbaiman'),
        ]);
    }
}
