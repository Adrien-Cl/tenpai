<?php

namespace App\Training\Steps;

use App\Training\Answers;
use App\Training\Step;
use App\Training\StepResult;
use App\Training\TrainingHand;

/**
 * Étape 2 : combien de han vaut chaque yaku ?
 */
final class HanStep implements Step
{
    public function key(): string
    {
        return 'han';
    }

    public function label(): string
    {
        return 'Han';
    }

    public function title(): string
    {
        return 'Combien de han vaut chaque yaku ?';
    }

    public function help(): string
    {
        return 'Attention : plusieurs yaku perdent un han quand la main est ouverte, et certains exigent une main fermée.';
    }

    public function scoringTab(): string
    {
        return 'han';
    }

    public function check(TrainingHand $hand, array $answers, bool $reveal): array
    {
        $result = new StepResult($reveal);
        $evaluation = $hand->evaluation;
        $ok = true;

        foreach ($evaluation->yaku() as $yakuHan) {
            $yaku = $yakuHan->yaku;
            $why = ! $evaluation->menzen && $yaku->losesHanWhenOpen()
                ? "{$yaku->closedHan()} han fermé, {$yaku->openHan()} une fois la main ouverte."
                : null;

            $ok = $result->mark($yaku->value, Answers::int($answers, $yaku->value) === $yakuHan->han, (string) $yakuHan->han, $why) && $ok;

            if ($reveal) {
                $answers[$yaku->value] = $yakuHan->han;
            }
        }

        return [
            'result' => $result->toArray($ok, $evaluation->yakuHan().' han de yaku'),
            'answers' => $answers,
        ];
    }
}
