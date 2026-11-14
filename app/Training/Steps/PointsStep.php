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
        return __('Chaque paiement est arrondi à la centaine supérieure. Les honba et les bâtons de riichi, s’il y en a, sont indiqués sur le tapis.');
    }

    public function scoringTab(): string
    {
        return 'points';
    }

    /**
     * Les paiements à donner, selon ron, tsumo du donneur ou tsumo d'un non-donneur,
     * puis le gain total quand la table porte des bâtons de riichi ou des honba.
     *
     * @return array<string, array{label: string, sub: string}>
     */
    public static function fields(TrainingHand $hand): array
    {
        $evaluation = $hand->evaluation;
        $payment = $evaluation->payment();
        $honba = $payment->honba > 0 ? __(', honba compris') : '';

        $fields = match (true) {
            ! $hand->hand()->tsumo => ['ron' => ['label' => __('Points reçus'), 'sub' => ($evaluation->dealer ? __('ron, donneur') : __('ron, non-donneur')).$honba]],
            $evaluation->dealer => ['each' => ['label' => __('Paiement de chaque adversaire'), 'sub' => __('tsumo du donneur').$honba]],
            default => [
                'fromDealer' => ['label' => __('Paiement du donneur'), 'sub' => 'tsumo'.$honba],
                'fromOthers' => ['label' => __('Paiement de chaque non-donneur'), 'sub' => 'tsumo'.$honba],
            ],
        };

        if ($payment->hasBonus()) {
            $fields['gain'] = ['label' => __('Gain total'), 'sub' => __('paiements et bâtons de riichi ramassés')];
        }

        return $fields;
    }

    /**
     * Ce que porte la table en plus de la main, en une ligne : « 2 honba, 1 bâton de riichi ».
     * Null quand la table ne compte ni honba ni bâton.
     */
    public static function tableLine(TrainingHand $hand): ?string
    {
        if (! $hand->rules->honba) {
            return null;
        }

        $h = $hand->hand();

        if ($h->honba === 0 && $h->riichiSticks === 0) {
            return __('Ni honba ni bâton de riichi');
        }

        return implode(', ', array_filter([
            $h->honba > 0 ? trans_choice(':count honba|:count honba', $h->honba) : null,
            $h->riichiSticks > 0 ? trans_choice(':count bâton de riichi|:count bâtons de riichi', $h->riichiSticks) : null,
        ]));
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
            'gain' => $payment->gain(),
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
