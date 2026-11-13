<?php

namespace App\Domain\Mahjong\Scoring;

use App\Domain\Mahjong\Hand\Group;
use App\Domain\Mahjong\Wait;

/**
 * Une lecture complète de la main : découpage, attente, yaku et fu.
 */
final readonly class Reading
{
    /**
     * @param  list<YakuHan>  $yaku
     * @param  list<Group>  $groups  groupes cachés puis appels (vide pour chiitoitsu)
     * @param  int|null  $pair  paire (null pour chiitoitsu)
     * @param  list<int>  $pairs  les sept paires d'un chiitoitsu
     * @param  bool  $pairWin  la tuile gagnante complète la paire
     */
    public function __construct(
        public array $yaku,
        public FuCount $fu,
        public Wait $wait,
        public array $groups,
        public ?int $pair,
        public bool $chiitoitsu = false,
        public array $pairs = [],
        public bool $pairWin = false,
    ) {}

    public function yakuHan(): int
    {
        return array_sum(array_map(fn (YakuHan $yaku): int => $yaku->han, $this->yaku));
    }

    public function hasYaku(): bool
    {
        return $this->yaku !== [];
    }
}
