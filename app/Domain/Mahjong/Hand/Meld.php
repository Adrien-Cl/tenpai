<?php

namespace App\Domain\Mahjong\Hand;

/**
 * Un appel posé devant le joueur. Pour un chi, la tuile est la plus basse de la suite.
 */
final readonly class Meld
{
    public function __construct(
        public MeldType $type,
        public int $tile,
    ) {}

    /**
     * @return list<int>
     */
    public function tiles(): array
    {
        return Group::tilesOf($this->type->groupKind(), $this->tile);
    }
}
