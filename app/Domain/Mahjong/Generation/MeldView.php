<?php

namespace App\Domain\Mahjong\Generation;

use App\Domain\Mahjong\Hand\MeldType;

/**
 * Un appel tel qu'il est posé sur la table.
 */
final readonly class MeldView
{
    /**
     * @param  list<TileInstance>  $tiles
     * @param  int|null  $rotated  index de la tuile couchée (celle prise à un adversaire), null pour un kan fermé
     */
    public function __construct(
        public MeldType $type,
        public int $tile,
        public array $tiles,
        public ?int $rotated,
    ) {}
}
