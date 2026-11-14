<?php

namespace App\Domain\Mahjong\Generation;

use App\Domain\Mahjong\Text;

/**
 * Le niveau de difficulté oriente le tirage : kan, situations rares, mains à plusieurs lectures.
 * Le niveau normal tire exactement les mains d'avant les niveaux.
 */
enum Level: string
{
    case Beginner = 'debutant';
    case Standard = 'normal';
    case Expert = 'expert';

    public function label(): string
    {
        return Text::get(match ($this) {
            self::Beginner => 'Débutant',
            self::Standard => 'Normal',
            self::Expert => 'Expert',
        });
    }

    public function gloss(): string
    {
        return Text::get(match ($this) {
            self::Beginner => 'sans kan ni situation rare, une seule lecture',
            self::Standard => 'les mains de tous les jours',
            self::Expert => 'kan, situations rares et mains à plusieurs lectures',
        });
    }

    /**
     * Les probabilités du tirage. Celles du niveau normal sont les valeurs d'origine.
     *
     * @return array{closedKan: float, openKan: float, doubleRiichi: float, ippatsu: float, haitei: float, rinshan: float, chankan: float}
     */
    public function odds(): array
    {
        return match ($this) {
            self::Beginner => ['closedKan' => 0, 'openKan' => 0, 'doubleRiichi' => 0, 'ippatsu' => 0, 'haitei' => 0, 'rinshan' => 0, 'chankan' => 0],
            self::Standard => ['closedKan' => 0.08, 'openKan' => 0.15, 'doubleRiichi' => 0.07, 'ippatsu' => 0.14, 'haitei' => 0.04, 'rinshan' => 0.3, 'chankan' => 0.1],
            self::Expert => ['closedKan' => 0.3, 'openKan' => 0.5, 'doubleRiichi' => 0.15, 'ippatsu' => 0.3, 'haitei' => 0.12, 'rinshan' => 0.7, 'chankan' => 0.3],
        };
    }
}
