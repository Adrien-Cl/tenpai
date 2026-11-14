<?php

namespace App\Training\Steps;

use App\Domain\Mahjong\Scoring\FuPart;
use App\Domain\Mahjong\Wait;
use App\Training\Answers;
use App\Training\Step;
use App\Training\StepResult;
use App\Training\TrainingHand;

/**
 * Étape 4 : les fu, ligne par ligne.
 */
final class FuStep implements Step
{
    public function key(): string
    {
        return 'fu';
    }

    public function label(): string
    {
        return 'Fu';
    }

    public function title(): string
    {
        return __('Les fu, un par un');
    }

    public function help(): string
    {
        return __('Donnez la valeur de chaque élément. Survolez une ligne pour voir son groupe sur le tapis ; la tuile gagnante est soulignée.');
    }

    public function scoringTab(): string
    {
        return 'fu';
    }

    public function check(TrainingHand $hand, array $answers, bool $reveal): array
    {
        $result = new StepResult($reveal);
        $reading = $hand->evaluation->reading();
        $fu = $reading->fu;
        $ok = true;
        $answers = Answers::emptyAsZero($answers, $reading->chiitoitsu
            ? ['final']
            : [...array_map(fn (FuPart $part): string => $part->key, $fu->parts), 'raw', 'final']);

        if ($reading->chiitoitsu) {
            $ok = $result->mark('final', Answers::int($answers, 'final') === 25, '25', $fu->parts[0]->why);

            return [
                'result' => $result->toArray($ok, '25 fu'),
                'answers' => $reveal ? ['final' => 25] : $answers,
            ];
        }

        foreach ($fu->parts as $part) {
            if ($part->key === 'wait' && $part->wait !== null) {
                $chosen = Wait::tryFrom((string) Answers::string($answers, 'waittype'));
                $ok = $result->mark('waittype', $chosen === $part->wait, $part->wait->label()) && $ok;

                if ($reveal) {
                    $answers['waittype'] = $part->wait->value;
                }
            }

            $ok = $result->mark($part->key, Answers::int($answers, $part->key) === $part->fu, (string) $part->fu, $part->why) && $ok;

            if ($reveal) {
                $answers[$part->key] = $part->fu;
            }
        }

        $sum = implode(' + ', array_map(fn (FuPart $part): int => $part->fu, $fu->parts));
        $ok = $result->mark('raw', Answers::int($answers, 'raw') === $fu->raw, (string) $fu->raw, __('Somme : :sum = :raw.', ['sum' => $sum, 'raw' => $fu->raw])) && $ok;
        $ok = $result->mark('final', Answers::int($answers, 'final') === $fu->fu, (string) $fu->fu, $fu->finalWhy) && $ok;

        if ($reveal) {
            $answers['raw'] = $fu->raw;
            $answers['final'] = $fu->fu;
        }

        return [
            'result' => $result->toArray($ok, "{$fu->fu} fu"),
            'answers' => $answers,
        ];
    }
}
