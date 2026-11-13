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
                $han >= 13 => '13 han ou plus',
                $han >= 11 => '11 ou 12 han',
                $han >= 8 => '8 à 10 han',
                $han >= 6 => '6 ou 7 han',
                $han >= 5 => '5 han',
                $payment->rawBase >= 2000 => "{$fu} × 2<sup>".($han + 2).'</sup> = '.Format::points($payment->rawBase).', plafonné à 2'."\u{00A0}".'000',
                default => 'kiriage mangan',
            };
            $lines[] = '<b>'.$payment->limit->label()."</b> ({$why}) : points de base ".Format::points($base).'.';
        } else {
            $lines[] = "Points de base : {$fu} × 2<sup>{$han} + 2</sup> = {$fu} × ".(2 ** ($han + 2)).' = <b>'.Format::points($base).'</b>.';
        }

        $rounded = fn (int $raw, int $paid): string => $raw !== $paid ? ', arrondi à '.Format::points($paid) : '';

        if (! $tsumo) {
            $factor = $dealer ? 6 : 4;
            $lines[] = 'Ron '.($dealer ? 'du donneur' : 'd’un non-donneur').' : '.Format::points($base)." × {$factor} = ".Format::points($base * $factor).$rounded($base * $factor, (int) $payment->ron).'.';
        } elseif ($dealer) {
            $lines[] = 'Tsumo du donneur : chacun paie '.Format::points($base).' × 2 = '.Format::points($base * 2).$rounded($base * 2, (int) $payment->each).'. Total '.Format::points($payment->total).'.';
        } else {
            $lines[] = 'Tsumo : le donneur paie '.Format::points($base).' × 2 = '.Format::points($base * 2).$rounded($base * 2, (int) $payment->fromDealer)
                .', chaque non-donneur '.Format::points($base).$rounded($base, (int) $payment->fromOthers).'. Total '.Format::points($payment->total).'.';
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
