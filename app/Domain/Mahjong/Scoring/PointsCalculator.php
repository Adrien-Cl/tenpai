<?php

namespace App\Domain\Mahjong\Scoring;

use App\Domain\Mahjong\Limit;
use App\Domain\Mahjong\Rules;

/**
 * Points de base (fu × 2^(han + 2), plafonnés par les limites), puis paiements arrondis à la centaine.
 */
final class PointsCalculator
{
    public static function points(int $han, int $fu, bool $dealer, bool $tsumo, Rules $rules): Payment
    {
        $rawBase = $fu * 2 ** (2 + $han);
        $limit = self::limit($han, $rawBase, $fu, $rules);
        $base = $limit?->base() ?? $rawBase;

        if (! $tsumo) {
            $ron = self::roundUp($base * ($dealer ? 6 : 4));

            return new Payment($base, $rawBase, $limit, total: $ron, ron: $ron);
        }

        if ($dealer) {
            $each = self::roundUp($base * 2);

            return new Payment($base, $rawBase, $limit, total: $each * 3, each: $each);
        }

        $fromDealer = self::roundUp($base * 2);
        $fromOthers = self::roundUp($base);

        return new Payment(
            $base,
            $rawBase,
            $limit,
            total: $fromDealer + 2 * $fromOthers,
            fromDealer: $fromDealer,
            fromOthers: $fromOthers,
        );
    }

    public static function limit(int $han, int $rawBase, int $fu, Rules $rules): ?Limit
    {
        return match (true) {
            $han >= 13 => Limit::Kazoe,
            $han >= 11 => Limit::Sanbaiman,
            $han >= 8 => Limit::Baiman,
            $han >= 6 => Limit::Haneman,
            $han >= 5, $rawBase >= 2000 => Limit::Mangan,
            $rules->kiriage && (($han === 4 && $fu === 30) || ($han === 3 && $fu === 60)) => Limit::Mangan,
            default => null,
        };
    }

    public static function roundUp(int $points): int
    {
        return (int) (ceil($points / 100) * 100);
    }
}
