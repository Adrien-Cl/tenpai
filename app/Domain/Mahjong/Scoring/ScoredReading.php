<?php

namespace App\Domain\Mahjong\Scoring;

/**
 * Une lecture qui a au moins un yaku, comptée : han (dora compris) et paiement.
 */
final readonly class ScoredReading
{
    public function __construct(
        public Reading $reading,
        public int $han,
        public Payment $payment,
    ) {}

    public function fu(): int
    {
        return $this->reading->fu->fu;
    }

    /**
     * Ce qui distingue le compte de deux lectures : yaku, han et fu. Deux découpages qui
     * donnent la même signature se comptent pareil.
     */
    public function signature(): string
    {
        $yaku = array_map(fn (YakuHan $yaku): string => $yaku->yaku->value.$yaku->han, $this->reading->yaku);
        sort($yaku);

        return implode(',', $yaku).'|'.$this->han.'|'.$this->fu();
    }
}
