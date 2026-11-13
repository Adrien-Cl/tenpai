<?php

namespace App\Domain\Mahjong\Scoring;

use App\Domain\Mahjong\Yaku;

final readonly class YakuHan
{
    public function __construct(
        public Yaku $yaku,
        public int $han,
    ) {}
}
