<?php

namespace App\Domain\Mahjong\Scoring;

use App\Domain\Mahjong\Tile;

final readonly class DoraCount
{
    /**
     * @param  int  $count  nombre total de dora dans la main
     * @param  list<array{indicator: int, dora: int, count: int}>  $detail  un élément par indicateur
     */
    public function __construct(
        public int $count,
        public array $detail,
    ) {}

    public static function none(): self
    {
        return new self(0, []);
    }

    /**
     * @param  list<int>  $tiles  toutes les tuiles de la main, appels compris
     * @param  list<int>  $indicators
     */
    public static function count(array $tiles, array $indicators): self
    {
        $total = 0;
        $detail = [];
        $counts = Tile::counts($tiles);

        foreach ($indicators as $indicator) {
            $dora = Tile::doraFrom($indicator);
            $total += $counts[$dora];
            $detail[] = ['indicator' => $indicator, 'dora' => $dora, 'count' => $counts[$dora]];
        }

        return new self($total, $detail);
    }
}
