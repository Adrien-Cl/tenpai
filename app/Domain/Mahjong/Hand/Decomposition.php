<?php

namespace App\Domain\Mahjong\Hand;

/**
 * Une façon de lire les tuiles cachées : une paire et des groupes.
 */
final readonly class Decomposition
{
    /**
     * @param  list<Group>  $groups
     */
    public function __construct(
        public int $pair,
        public array $groups,
    ) {}
}
