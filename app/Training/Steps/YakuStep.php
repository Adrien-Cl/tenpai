<?php

namespace App\Training\Steps;

use App\Domain\Mahjong\Scoring\YakuHan;
use App\Domain\Mahjong\Yaku;
use App\Domain\Mahjong\Yakuman;
use App\Training\FieldState;
use App\Training\Step;
use App\Training\StepResult;
use App\Training\TrainingHand;

/**
 * Étape 1 : quels yaku ? On les annonce par leur nom.
 */
final class YakuStep implements Step
{
    public function key(): string
    {
        return 'yaku';
    }

    public function label(): string
    {
        return 'Yaku';
    }

    public function title(): string
    {
        return 'Quels yaku ?';
    }

    public function help(): string
    {
        return 'Tapez le nom de chaque yaku : des suggestions apparaissent dès les premières lettres. Les dora ne sont pas des yaku, ils viennent à l’étape 3.';
    }

    public function scoringTab(): string
    {
        return 'han';
    }

    public function check(TrainingHand $hand, array $answers, bool $reveal): array
    {
        $result = new StepResult($reveal);
        $correct = array_map(fn (YakuHan $yaku): string => $yaku->yaku->value, $hand->evaluation->yaku());
        $picked = $reveal ? $correct : self::picked($answers);

        $wrong = array_values(array_diff($picked, $correct));
        $missed = array_values(array_diff($correct, $picked));

        foreach ($correct as $id) {
            $result->set($id, match (true) {
                $reveal => FieldState::Revealed,
                in_array($id, $missed, true) => FieldState::Missed,
                default => FieldState::Ok,
            });
        }

        foreach ($wrong as $id) {
            $result->set($id, FieldState::Extra);
        }

        $summary = implode(', ', array_map(fn (YakuHan $yaku): string => $yaku->yaku->shortLabel(), $hand->evaluation->yaku()));

        return [
            'result' => $result->toArray($wrong === [] && $missed === [], $summary),
            'answers' => ['picked' => $picked],
        ];
    }

    /**
     * Les yaku annoncés, débarrassés des doublons et des identifiants inconnus.
     *
     * @param  array<string, mixed>  $answers
     * @return list<string>
     */
    public static function picked(array $answers): array
    {
        $picked = is_array($answers['picked'] ?? null) ? $answers['picked'] : [];
        $known = [...array_column(Yaku::cases(), 'value'), ...array_column(Yakuman::cases(), 'value')];

        return array_values(array_unique(array_filter($picked, fn (mixed $id): bool => is_string($id) && in_array($id, $known, true))));
    }
}
