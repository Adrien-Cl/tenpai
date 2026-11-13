<?php

namespace App\Domain\Mahjong\Generation;

/**
 * Une tuile physique posée sur la table : sa valeur, et si c'est un cinq rouge.
 */
final readonly class TileInstance
{
    public function __construct(
        public int $tile,
        public bool $red = false,
    ) {}
}
