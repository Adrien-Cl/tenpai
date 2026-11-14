<?php

namespace App\Support;

use App\Domain\Mahjong\Scoring\Payment;

/**
 * Le calcul des points, expliqué ligne par ligne (HTML : seuls des nombres y sont insérés).
 */
final class PointsFormula
{
    /**
     * @return list<string>
     */
    public static function lines(Payment $payment, int $han, int $fu, bool $dealer, bool $tsumo): array
    {
        $base = $payment->base;
        $lines = [];

        if ($payment->limit !== null) {
            $why = match (true) {
                $han >= 13 => __('13 han ou plus'),
                $han >= 11 => __('11 ou 12 han'),
                $han >= 8 => __('8 à 10 han'),
                $han >= 6 => __('6 ou 7 han'),
                $han >= 5 => '5 han',
                $payment->rawBase >= 2000 => __(':fu × 2<sup>:exponent</sup> = :raw, plafonné à :cap', [
                    'fu' => $fu,
                    'exponent' => $han + 2,
                    'raw' => Format::points($payment->rawBase),
                    'cap' => Format::points(2000),
                ]),
                default => 'kiriage mangan',
            };
            $lines[] = __('<b>:limit</b> (:why) : points de base :base.', ['limit' => $payment->limit->label(), 'why' => $why, 'base' => Format::points($base)]);
        } else {
            $lines[] = __('Points de base : :fu × 2<sup>:han + 2</sup> = :fu × :power = <b>:base</b>.', ['fu' => $fu, 'han' => $han, 'power' => 2 ** ($han + 2), 'base' => Format::points($base)]);
        }

        $rounded = fn (int $raw, int $paid): string => $raw !== $paid ? __(', arrondi à :points', ['points' => Format::points($paid)]) : '';

        if (! $tsumo) {
            $factor = $dealer ? 6 : 4;
            $lines[] = __($dealer ? 'Ron du donneur : :base × :factor = :total:rounded.' : 'Ron d’un non-donneur : :base × :factor = :total:rounded.', [
                'base' => Format::points($base),
                'factor' => $factor,
                'total' => Format::points($base * $factor),
                'rounded' => $rounded($base * $factor, (int) $payment->ron),
            ]);
        } elseif ($dealer) {
            $lines[] = __('Tsumo du donneur : chacun paie :base × 2 = :double:rounded. Total :total.', [
                'base' => Format::points($base),
                'double' => Format::points($base * 2),
                'rounded' => $rounded($base * 2, (int) $payment->each),
                'total' => Format::points($payment->total),
            ]);
        } else {
            $lines[] = __('Tsumo : le donneur paie :base × 2 = :double:dealerRounded, chaque non-donneur :base:othersRounded. Total :total.', [
                'base' => Format::points($base),
                'double' => Format::points($base * 2),
                'dealerRounded' => $rounded($base * 2, (int) $payment->fromDealer),
                'othersRounded' => $rounded($base, (int) $payment->fromOthers),
                'total' => Format::points($payment->total),
            ]);
        }

        return $lines;
    }

    /**
     * Ce que rapporte la main, en une valeur courte : « 7 700 », « 2 000 / 3 900 », « 3 900 chacun ».
     */
    public static function short(Payment $payment, bool $dealer, bool $tsumo): string
    {
        return match (true) {
            ! $tsumo => Format::points((int) $payment->ron),
            $dealer => Format::points((int) $payment->each),
            default => Format::points((int) $payment->fromOthers).' / '.Format::points((int) $payment->fromDealer),
        };
    }
}
