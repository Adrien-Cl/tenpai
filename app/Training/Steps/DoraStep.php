<?php

namespace App\Training\Steps;

use App\Training\Answers;
use App\Training\Step;
use App\Training\StepResult;
use App\Training\TrainingHand;

/**
 * Étape 3 : dora et total des han.
 */
final class DoraStep implements Step
{
    public function key(): string
    {
        return 'dora';
    }

    public function label(): string
    {
        return 'Dora';
    }

    public function title(): string
    {
        return __('Dora et total des han');
    }

    public function help(): string
    {
        return __('L’indicateur désigne la tuile suivante : 9 → 1, Nord → Est, Chun → Haku. Chaque kan ajoute un indicateur.');
    }

    public function scoringTab(): string
    {
        return 'han';
    }

    /**
     * Les champs à remplir pour cette main.
     *
     * @return array<string, array{label: string, sub: string}>
     */
    public static function fields(TrainingHand $hand): array
    {
        $fields = ['dora' => ['label' => 'Dora', 'sub' => __('tuile suivant chaque indicateur')]];

        if ($hand->rules->aka) {
            $fields['aka'] = ['label' => 'Aka-dora', 'sub' => __('cinq rouges')];
        }

        if ($hand->hand()->riichi) {
            $fields['ura'] = ['label' => 'Ura-dora', 'sub' => __('révélés grâce au riichi')];
        }

        $fields['han'] = ['label' => __('Total des han'), 'sub' => __('yaku + tous les dora')];

        return $fields;
    }

    public function check(TrainingHand $hand, array $answers, bool $reveal): array
    {
        $result = new StepResult($reveal);
        $evaluation = $hand->evaluation;
        $fields = self::fields($hand);
        $answers = Answers::emptyAsZero($answers, array_values(array_diff(array_keys($fields), ['han'])));

        $expected = [
            'dora' => $evaluation->dora->count,
            'aka' => $evaluation->aka,
            'ura' => $evaluation->ura->count,
            'han' => $evaluation->han,
        ];

        $parts = ["{$evaluation->yakuHan()} (yaku)", "{$evaluation->dora->count} (dora)"];

        if (isset($fields['aka'])) {
            $parts[] = "{$evaluation->aka} (aka)";
        }

        if (isset($fields['ura'])) {
            $parts[] = "{$evaluation->ura->count} (ura)";
        }

        $why = ['han' => implode(' + ', $parts)." = {$evaluation->han} han."];
        $ok = true;

        foreach (array_keys($fields) as $key) {
            $ok = $result->mark($key, Answers::int($answers, $key) === $expected[$key], (string) $expected[$key], $why[$key] ?? null) && $ok;

            if ($reveal) {
                $answers[$key] = $expected[$key];
            }
        }

        $count = $evaluation->doraHan();

        return [
            'result' => $result->toArray($ok, ($count === 0 ? __('Aucun dora, :han han au total', ['han' => $evaluation->han]) : __(':count dora, :han han au total', ['count' => $count, 'han' => $evaluation->han]))),
            'answers' => $answers,
        ];
    }
}
