<?php

namespace App\Domain\Mahjong\Scoring;

use App\Domain\Mahjong\Limit;

/**
 * Ce que rapporte une main. Selon le cas, seuls certains champs sont remplis :
 * ron ; tsumo du donneur (each) ; tsumo d'un non-donneur (fromDealer et fromOthers).
 *
 * Les paiements comprennent les honba (300 par honba en ron, 100 par payeur en tsumo).
 * Les bâtons de riichi ne sont payés par personne : le gagnant les ramasse sur la table.
 */
final readonly class Payment
{
    public const int HONBA_RON = 300;

    public const int HONBA_TSUMO = 100;

    public const int STICK = 1000;

    /**
     * @param  int  $total  somme des paiements, honba compris, sans les bâtons de riichi
     * @param  int  $honba  honba comptés
     * @param  int  $sticks  bâtons de riichi ramassés
     */
    public function __construct(
        public int $base,
        public int $rawBase,
        public ?Limit $limit,
        public int $total,
        public ?int $ron = null,
        public ?int $each = null,
        public ?int $fromDealer = null,
        public ?int $fromOthers = null,
        public int $honba = 0,
        public int $sticks = 0,
    ) {}

    /**
     * Ce que le gagnant reçoit en tout : les paiements et les bâtons de riichi.
     */
    public function gain(): int
    {
        return $this->total + $this->sticks * self::STICK;
    }

    /**
     * La table porte-t-elle quelque chose en plus de la main ?
     */
    public function hasBonus(): bool
    {
        return $this->honba > 0 || $this->sticks > 0;
    }
}
