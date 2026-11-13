<?php

namespace App\Domain\Mahjong\Scoring;

use App\Domain\Mahjong\Hand\Group;
use App\Domain\Mahjong\Wait;

/**
 * Une ligne du décompte des fu, avec son explication.
 *
 * Clés : base, win, g0…g3 (groupes), pair, wait, chiitoi.
 */
final readonly class FuPart
{
    public function __construct(
        public string $key,
        public int $fu,
        public string $why,
        public ?Group $group = null,
        public ?int $pair = null,
        public ?Wait $wait = null,
    ) {}
}
