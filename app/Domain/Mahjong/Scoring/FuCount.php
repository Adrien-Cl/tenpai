<?php

namespace App\Domain\Mahjong\Scoring;

final readonly class FuCount
{
    /**
     * @param  int  $fu  fu de la main, après arrondi
     * @param  int  $raw  somme avant arrondi
     * @param  list<FuPart>  $parts
     */
    public function __construct(
        public int $fu,
        public int $raw,
        public array $parts,
        public string $finalWhy,
    ) {}
}
