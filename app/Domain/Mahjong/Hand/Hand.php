<?php

namespace App\Domain\Mahjong\Hand;

use App\Domain\Mahjong\Tile;
use App\Domain\Mahjong\Wind;

/**
 * Une main gagnante et sa situation : tout ce qu'il faut pour la compter.
 */
final readonly class Hand
{
    /**
     * @param  list<int>  $closed  tuiles cachées, tuile gagnante comprise
     * @param  list<Meld>  $melds  appels posés
     * @param  int  $win  tuile gagnante
     * @param  list<int>  $dora  indicateurs de dora (un de plus par kan)
     * @param  list<int>  $ura  indicateurs d'ura-dora (révélés après un riichi)
     * @param  int  $aka  nombre de cinq rouges dans la main
     */
    public function __construct(
        public array $closed,
        public array $melds,
        public int $win,
        public bool $tsumo,
        public Wind $seat,
        public Wind $round,
        public bool $riichi = false,
        public bool $doubleRiichi = false,
        public bool $ippatsu = false,
        public bool $haitei = false,
        public bool $rinshan = false,
        public bool $chankan = false,
        public array $dora = [],
        public array $ura = [],
        public int $aka = 0,
    ) {}

    /**
     * Fermée : aucun appel, sauf des kan fermés.
     */
    public function isClosed(): bool
    {
        foreach ($this->melds as $meld) {
            if ($meld->type->opensHand()) {
                return false;
            }
        }

        return true;
    }

    public function isDealer(): bool
    {
        return $this->seat === Wind::East;
    }

    public function kanCount(): int
    {
        return count(array_filter($this->melds, fn (Meld $meld): bool => $meld->type->isKan()));
    }

    /**
     * Toutes les tuiles de la main, appels compris (avec la quatrième tuile des kan).
     *
     * @return list<int>
     */
    public function allTiles(): array
    {
        $tiles = $this->closed;

        foreach ($this->melds as $meld) {
            array_push($tiles, ...$meld->tiles());
        }

        return $tiles;
    }

    /**
     * @return array<int, int>
     */
    public function closedCounts(): array
    {
        return Tile::counts($this->closed);
    }
}
