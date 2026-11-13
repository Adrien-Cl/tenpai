<?php

namespace App\Domain\Mahjong\Scoring;

use App\Domain\Mahjong\Limit;

/**
 * Ce que rapporte une main. Selon le cas, seuls certains champs sont remplis :
 * ron ; tsumo du donneur (each) ; tsumo d'un non-donneur (fromDealer et fromOthers).
 */
final readonly class Payment
{
    public function __construct(
        public int $base,
        public int $rawBase,
        public ?Limit $limit,
        public int $total,
        public ?int $ron = null,
        public ?int $each = null,
        public ?int $fromDealer = null,
        public ?int $fromOthers = null,
    ) {}
}
