<?php

namespace App\Support;

/**
 * Les textes écrits par le JavaScript de l'interface, traduits dans la langue de la page.
 * Le français est la source : rien à envoyer en français.
 */
final class ScriptText
{
    /**
     * Les phrases demandées par resources/js (t('…') et les messages de NOT_YAKU).
     * Un test vérifie que la liste suit le code.
     *
     * @var list<string>
     */
    public const array KEYS = [
        'Tenpai · défi du :day',
        ', sans faute',
        'L M M J V S D',
        ' : pas joué',
        'Ron du donneur',
        'Ron d’un non-donneur',
        'Tsumo du donneur : chaque adversaire paie',
        'Tsumo : chaque non-donneur / le donneur',
        '20 fu en tsumo, c’est un pinfu : 2 han au moins (pinfu et tsumo).',
        '20 fu n’existe pas en ron : un pinfu en ron vaut 30 fu.',
        '25 fu, c’est un chiitoitsu : en tsumo, 3 han au moins.',
        '25 fu, c’est un chiitoitsu : 2 han au moins.',
        'Aucun yaku ne correspond à « :query ».',
        ':count yaku trouvés.',
        ':count yaku trouvé.',
        'Ce yaku est déjà dans votre liste.',
        ':yaku ajouté.',
        ':yaku retiré.',
        'Les dora ne sont pas des yaku : ils se comptent à l’étape 3.',
        'Nagashi mangan se gagne sur les défausses, pas avec une main : il n’apparaît pas ici.',
        'Les han, les fu et les limites viennent aux étapes suivantes.',
    ];

    /**
     * @return array<string, string>
     */
    public static function translations(): array
    {
        if (app()->getLocale() === Locale::DEFAULT) {
            return [];
        }

        // Sans remplacement : les valeurs « :nom » sont remplies par le JavaScript.
        return collect(self::KEYS)->mapWithKeys(fn (string $key): array => [$key => trans()->get($key, [], null, false)])->all();
    }
}
