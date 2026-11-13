<?php

namespace App\Domain\Mahjong\Scoring;

enum EvaluationStatus: string
{
    /** Main complète, avec au moins un yaku. */
    case Valid = 'valid';

    /** Une lecture forme un yakuman : la main est écartée. */
    case Yakuman = 'yakuman';

    /** Main complète, mais aucune lecture n'a de yaku. */
    case NoYaku = 'no-yaku';

    /** Les tuiles ne forment pas une main complète. */
    case Invalid = 'invalid';
}
