<?php

namespace App\Training\Steps;

use App\Domain\Mahjong\Limit;
use App\Support\Format;
use App\Support\PointsFormula;
use App\Training\Answers;
use App\Training\Step;
use App\Training\StepResult;
use App\Training\TrainingHand;

/**
 * Étape 5 : combien rapporte la main ?
 */
final class PointsStep implements Step
{
    public function key(): string
    {
        return 'points';
    }

    public function label(): string
    {
        return 'Points';
    }

    public function title(): string
    {
        return __('Combien rapporte la main ?');
    }

    public function help(): string
    {
        return __('Sans honba ni bâton de riichi. Chaque paiement est arrondi à la centaine supérieure.');
    }

    public function scoringTab(): string
    {
        return 'points';
    }

    /**
     * Les paiements à donner, selon ron, tsumo du donneur ou tsumo d'un non-donneur.
     *
     * @return array<string, array{label: string, sub: string}>
     */
    public static function fields(TrainingHand $hand): array
    {
        $evaluation = $hand->evaluation;

        if (! $hand->hand()->tsumo) {
            return ['ron' => ['label' => __('Points reçus'), 'sub' => $evaluation->dealer ? __('ron, donneur') : __('ron, non-donneur')]];
        }

        if ($evaluation->dealer) {
            return ['each' => ['label' => __('Paiement de chaque adversaire'), 'sub' => __('tsumo du donneur')]];
        }

        return [
            'fromDealer' => ['label' => __('Paiement du donneur'), 'sub' => 'tsumo'],
            'fromOthers' => ['label' => __('Paiement de chaque non-donneur'), 'sub' => 'tsumo'],
        ];
    }

    /**
     * Les limites proposées, de « aucune » au yakuman compté.
     *
     * @return array<string, string>
     */
    public static function limits(): array
    {
        $limits = ['none' => __('Aucune')];

        foreach (Limit::cases() as $limit) {
            $limits[$limit->value] = $limit->label();
        }

        return $limits;
    }

    /**
     * Ce que rapporte la main, en une ligne : « 1 300 / 2 600 (5 200) ».
     */
    public static function payLine(TrainingHand $hand): string
    {
        $payment = $hand->evaluation->payment();

        return match (true) {
            ! $hand->hand()->tsumo => __(':points points', ['points' => Format::points((int) $payment->ron)]),
            $hand->evaluation->dealer => __(':each chacun (:total)', ['each' => Format::points((int) $payment->each), 'total' => Format::points($payment->total)]),
            default => Format::points((int) $payment->fromOthers).' / '.Format::points((int) $payment->fromDealer).' ('.Format::points($payment->total).')',
        };
    }

    /**
     * Le calcul détaillé, ligne par ligne (HTML : seuls des nombres y sont insérés).
     *
     * @return list<string>
     */
    public static function formula(TrainingHand $hand): array
    {
        $evaluation = $hand->evaluation;

        return PointsFormula::lines($evaluation->payment(), $evaluation->han, $evaluation->fu(), $evaluation->dealer, $hand->hand()->tsumo);
    }

    public function check(TrainingHand $hand, array $answers, bool $reveal): array
    {
        $result = new StepResult($reveal);
        $payment = $hand->evaluation->payment();
        $limit = $payment->limit?->value ?? 'none';

        if (! isset(self::limits()[(string) Answers::string($answers, 'limit')])) {
            $answers['limit'] = 'none';
        }

        $ok = $result->mark('limit', Answers::string($answers, 'limit') === $limit, self::limits()[$limit]);

        if ($reveal) {
            $answers['limit'] = $limit;
        }

        $expected = [
            'ron' => $payment->ron,
            'each' => $payment->each,
            'fromDealer' => $payment->fromDealer,
            'fromOthers' => $payment->fromOthers,
        ];

        foreach (array_keys(self::fields($hand)) as $key) {
            $value = (int) $expected[$key];
            $ok = $result->mark($key, Answers::int($answers, $key) === $value, Format::points($value)) && $ok;

            if ($reveal) {
                $answers[$key] = $value;
            }
        }

        return [
            'result' => $result->toArray($ok, self::payLine($hand)),
            'answers' => $answers,
        ];
    }
}
