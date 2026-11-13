<?php

namespace App\Domain\Mahjong\Hand;

enum MeldType: string
{
    case Chi = 'chi';
    case Pon = 'pon';
    case OpenKan = 'minkan';
    case ClosedKan = 'ankan';

    public function label(): string
    {
        return match ($this) {
            self::Chi => 'chi',
            self::Pon => 'pon',
            self::OpenKan => 'kan ouvert',
            self::ClosedKan => 'kan fermé',
        };
    }

    public function groupKind(): GroupKind
    {
        return match ($this) {
            self::Chi => GroupKind::Sequence,
            self::Pon => GroupKind::Triplet,
            self::OpenKan, self::ClosedKan => GroupKind::Kan,
        };
    }

    /**
     * Seul le kan fermé laisse la main fermée.
     */
    public function opensHand(): bool
    {
        return $this !== self::ClosedKan;
    }

    public function isKan(): bool
    {
        return $this === self::OpenKan || $this === self::ClosedKan;
    }
}
