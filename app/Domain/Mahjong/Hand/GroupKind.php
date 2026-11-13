<?php

namespace App\Domain\Mahjong\Hand;

enum GroupKind: string
{
    case Sequence = 'seq';
    case Triplet = 'trip';
    case Kan = 'kan';
}
