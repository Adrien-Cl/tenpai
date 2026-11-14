<?php

namespace App\View;

use App\Domain\Mahjong\Limit;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Scoring\Payment;
use App\Domain\Mahjong\Scoring\PointsCalculator;
use App\Domain\Mahjong\Wait;
use App\Domain\Mahjong\Yaku;
use App\Domain\Mahjong\Yakuman;
use App\Support\Format;
use App\Support\PointsFormula;

/**
 * Les données du barème, toutes calculées par le moteur : elles suivent les règles de la table.
 */
final class ScoringSheet
{
    public const array FU_ROWS = [20, 25, 30, 40, 50, 60, 70, 80, 90, 100, 110];

    /** Choix de han du calculateur : [valeur utilisée, libellé]. */
    public const array HAN_CHOICES = [[1, '1'], [2, '2'], [3, '3'], [4, '4'], [5, '5'], [6, '6-7'], [8, '8-10'], [11, '11-12'], [13, '13+']];

    public function __construct(public readonly Rules $rules = new Rules) {}

    /**
     * Une combinaison han × fu existe-t-elle ? 20 fu n'existe qu'en pinfu tsumo (2 han au moins),
     * 25 fu qu'en chiitoitsu (2 han, 3 avec le tsumo).
     */
    public static function isPossible(int $han, int $fu, bool $tsumo): bool
    {
        if ($han >= 5) {
            return true;
        }

        return match ($fu) {
            20 => $tsumo && $han >= 2,
            25 => $han >= ($tsumo ? 3 : 2),
            default => true,
        };
    }

    /**
     * Le tableau des points pour un gagnant et un mode de victoire : un chiffre par case,
     * et la zone des limites regroupée en une seule bande par ligne.
     *
     * Pour un tsumo de non-donneur, value est le paiement de chaque non-donneur et detail celui du donneur.
     *
     * @return list<array{fu: int, cells: list<array{han: int, value: string|null, detail: string|null}>, limit: array{from: int, span: int, label: string, value: string, detail: string|null}|null}>
     */
    public function pointGrid(bool $dealer, bool $tsumo): array
    {
        $rows = [];

        foreach (self::FU_ROWS as $fu) {
            $cells = [];
            $limit = null;

            foreach ([1, 2, 3, 4] as $han) {
                if (! self::isPossible($han, $fu, $tsumo)) {
                    $cells[] = ['han' => $han, 'value' => null, 'detail' => null];

                    continue;
                }

                $payment = PointsCalculator::points($han, $fu, $dealer, $tsumo, $this->rules);
                [$value, $detail] = self::cellValues($payment, $dealer, $tsumo);

                if ($payment->limit !== null) {
                    $limit = [
                        'from' => $han,
                        'span' => 5 - $han,
                        'label' => $payment->limit->label(),
                        'value' => $value,
                        'detail' => $detail,
                    ];
                    break;
                }

                $cells[] = ['han' => $han, 'value' => $value, 'detail' => $detail];
            }

            if ($limit === null && array_filter($cells, fn (array $cell): bool => $cell['value'] !== null) === []) {
                continue;
            }

            $rows[] = ['fu' => $fu, 'cells' => $cells, 'limit' => $limit];
        }

        return $rows;
    }

    /**
     * Les cinq limites, pour un gagnant et un mode de victoire.
     *
     * @return list<array{label: string, condition: string, value: string, detail: string|null, han: int}>
     */
    public function limitLine(bool $dealer, bool $tsumo): array
    {
        return array_map(function (Limit $limit) use ($dealer, $tsumo): array {
            [$value, $detail] = self::cellValues(PointsCalculator::points($limit->minimumHan(), 30, $dealer, $tsumo, $this->rules), $dealer, $tsumo);

            return [
                'label' => $limit->label(),
                'condition' => $limit->condition(),
                'value' => $value,
                'detail' => $detail,
                'han' => $limit->minimumHan(),
            ];
        }, Limit::cases());
    }

    /**
     * Toutes les réponses du calculateur, indexées « donneur tsumo-han-fu » (ex. « 01-3-30 »).
     * Au-delà de 4 han, les fu ne comptent plus : la clé porte 0.
     *
     * @return array<string, array{possible: bool, value?: string, limit?: string|null, formula?: list<string>}>
     */
    public function calculator(): array
    {
        $table = [];

        foreach ([false, true] as $dealer) {
            foreach ([false, true] as $tsumo) {
                foreach (self::HAN_CHOICES as [$han]) {
                    foreach ($han >= 5 ? [0] : self::FU_ROWS as $fu) {
                        $key = (int) $dealer.(int) $tsumo.'-'.$han.'-'.$fu;

                        if (! self::isPossible($han, $fu, $tsumo)) {
                            $table[$key] = ['possible' => false];

                            continue;
                        }

                        $payment = PointsCalculator::points($han, $fu === 0 ? 30 : $fu, $dealer, $tsumo, $this->rules);

                        $table[$key] = [
                            'possible' => true,
                            'value' => PointsFormula::short($payment, $dealer, $tsumo),
                            'limit' => $payment->limit?->label(),
                            'formula' => PointsFormula::lines($payment, $han, $fu === 0 ? 30 : $fu, $dealer, $tsumo),
                        ];
                    }
                }
            }
        }

        return $table;
    }

    /**
     * Les yaku regroupés par valeur, en main fermée ou ouverte.
     * En main ouverte, ceux qui exigent une main fermée sont rangés à part (clé 0).
     *
     * @return array<int, list<Yaku>>
     */
    public function yakuByHan(bool $open): array
    {
        $groups = [];

        foreach (Yaku::cases() as $yaku) {
            $han = $open ? ($yaku->openHan() ?? 0) : $yaku->closedHan();
            $groups[$han][] = $yaku;
        }

        ksort($groups);

        if (isset($groups[0])) {
            $closedOnly = $groups[0];
            unset($groups[0]);
            $groups[0] = $closedOnly;
        }

        return $groups;
    }

    /**
     * @return list<Yakuman>
     */
    public function yakuman(): array
    {
        return Yakuman::cases();
    }

    /**
     * Les attentes, avec un exemple : tuiles en attente et tuiles qui complètent la main.
     *
     * @return list<array{wait: Wait, waiting: string, completes: string}>
     */
    public function waits(): array
    {
        return [
            ['wait' => Wait::Ryanmen, 'waiting' => '34m', 'completes' => '25m'],
            ['wait' => Wait::Shanpon, 'waiting' => '88p22s', 'completes' => '8p2s'],
            ['wait' => Wait::Kanchan, 'waiting' => '46m', 'completes' => '5m'],
            ['wait' => Wait::Penchan, 'waiting' => '12s', 'completes' => '3s'],
            ['wait' => Wait::Tanki, 'waiting' => '7p', 'completes' => '7p'],
        ];
    }

    /**
     * Les entrées de la recherche de yaku (étape 1 et barème).
     *
     * @return list<array{id: string, label: string, name: string, gloss: string, description: string, aliases: list<string>, yakuman: bool, han: int}>
     */
    public static function yakuEntries(): array
    {
        $entries = array_map(fn (Yaku $yaku): array => [
            'id' => $yaku->value,
            'label' => $yaku->shortLabel(),
            'name' => $yaku->label(),
            'gloss' => $yaku->gloss(),
            'description' => $yaku->description(),
            'aliases' => $yaku->aliases(),
            'yakuman' => false,
            'han' => $yaku->closedHan(),
        ], Yaku::cases());

        foreach (Yakuman::cases() as $yakuman) {
            $entries[] = [
                'id' => $yakuman->value,
                'label' => $yakuman->label(),
                'name' => $yakuman->label(),
                'gloss' => $yakuman->gloss(),
                'description' => $yakuman->description(),
                'aliases' => $yakuman->aliases(),
                'yakuman' => true,
                'han' => 13,
            ];
        }

        return $entries;
    }

    /**
     * Le chiffre d'une case, et pour un tsumo de non-donneur, le paiement du donneur en dessous.
     *
     * @return array{string, string|null}
     */
    private static function cellValues(Payment $payment, bool $dealer, bool $tsumo): array
    {
        return match (true) {
            ! $tsumo => [Format::points((int) $payment->ron), null],
            $dealer => [Format::points((int) $payment->each), null],
            default => [Format::points((int) $payment->fromOthers), Format::points((int) $payment->fromDealer)],
        };
    }

    /**
     * Format court d'un nombre de points, pour les vues.
     */
    public static function points(int $points): string
    {
        return Format::points($points);
    }
}
