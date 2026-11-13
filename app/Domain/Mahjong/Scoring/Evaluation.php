<?php

namespace App\Domain\Mahjong\Scoring;

use App\Domain\Mahjong\Wait;
use LogicException;

/**
 * Le compte d'une main : la meilleure lecture, les dora et le paiement.
 */
final readonly class Evaluation
{
    /**
     * @param  bool  $multiple  plusieurs lectures donnent des comptes différents
     */
    public function __construct(
        public EvaluationStatus $status,
        public ?Reading $reading = null,
        public ?Payment $payment = null,
        public int $han = 0,
        public DoraCount $dora = new DoraCount(0, []),
        public DoraCount $ura = new DoraCount(0, []),
        public int $aka = 0,
        public bool $menzen = false,
        public bool $dealer = false,
        public bool $multiple = false,
    ) {}

    public static function rejected(EvaluationStatus $status): self
    {
        return new self($status);
    }

    public function isValid(): bool
    {
        return $this->status === EvaluationStatus::Valid;
    }

    public function reading(): Reading
    {
        return $this->reading ?? throw new LogicException('Main sans lecture valide.');
    }

    public function payment(): Payment
    {
        return $this->payment ?? throw new LogicException('Main sans lecture valide.');
    }

    /**
     * @return list<YakuHan>
     */
    public function yaku(): array
    {
        return $this->reading()->yaku;
    }

    public function yakuHan(): int
    {
        return $this->reading()->yakuHan();
    }

    public function fu(): int
    {
        return $this->reading()->fu->fu;
    }

    public function wait(): Wait
    {
        return $this->reading()->wait;
    }

    /**
     * Tous les dora : indicateurs, cinq rouges et ura.
     */
    public function doraHan(): int
    {
        return $this->dora->count + $this->aka + $this->ura->count;
    }
}
