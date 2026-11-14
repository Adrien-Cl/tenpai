<?php

namespace App\Training;

use App\Domain\Mahjong\Generation\GeneratedHand;
use App\Domain\Mahjong\Generation\HandGenerator;
use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Generation\SeededRandom;
use App\Domain\Mahjong\Hand\Group;
use App\Domain\Mahjong\Hand\Hand;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Scoring\Evaluation;
use App\Domain\Mahjong\Scoring\Evaluator;
use App\Domain\Mahjong\Scoring\FuPart;
use App\Domain\Mahjong\Scoring\ScoredReading;
use App\Domain\Mahjong\Scoring\YakuHan;
use App\Support\Format;

/**
 * L'exercice des lectures : une main qui se découpe de plusieurs façons. Pour chaque lecture,
 * le joueur donne les han et les fu, puis choisit celle qui compte : la plus payante.
 */
final readonly class ReadingsHand
{
    /** Lectures montrées au plus. */
    public const int MAX = 3;

    public const array LETTERS = ['A', 'B', 'C'];

    /**
     * @param  list<ScoredReading>  $readings  dans l'ordre d'affichage
     */
    public function __construct(
        public TrainingHand $training,
        public array $readings,
    ) {}

    /**
     * @throws \RuntimeException si aucune main à plusieurs lectures ne sort de la graine
     */
    public static function fromSeed(Seed $seed, Rules $rules): self
    {
        // « multiple » (des lectures qui se comptent différemment) est nécessaire : on l'essaie d'abord, il ne coûte rien.
        $generated = (new HandGenerator)->generate(
            $seed,
            fn (GeneratedHand $generated, Evaluation $evaluation): bool => $evaluation->multiple && self::isAmbiguous($generated->hand),
        );
        $readings = array_slice(Evaluator::scoredReadings($generated->hand, $rules), 0, self::MAX);
        $order = (new SeededRandom($seed, 'lectures'))->shuffle(array_keys($readings));

        return new self(
            new TrainingHand($generated, Evaluator::evaluate($generated->hand, $rules), $rules),
            array_map(fn (int $index): ScoredReading => $readings[$index], $order),
        );
    }

    /**
     * Une main convient si deux lectures au moins ont un yaku, et que la meilleure paie strictement plus.
     * Le tri se fait avec les règles par défaut, comme pour la main elle-même.
     */
    public static function isAmbiguous(Hand $hand): bool
    {
        $readings = Evaluator::scoredReadings($hand, Rules::default());

        return count($readings) >= 2 && $readings[0]->payment->total > $readings[1]->payment->total;
    }

    /**
     * Les lectures qui paient le plus, avec les règles du joueur : souvent une seule, parfois deux à égalité.
     *
     * @return list<int>
     */
    public function bestIndexes(): array
    {
        $best = max(array_map(fn (ScoredReading $reading): int => $reading->payment->total, $this->readings));

        return array_keys(array_filter($this->readings, fn (ScoredReading $reading): bool => $reading->payment->total === $best));
    }

    /**
     * Corrige les han et les fu de chaque lecture, puis le choix de la lecture qui compte.
     *
     * @param  array<string, mixed>  $answers
     * @return array{result: array{ok: bool, revealed: bool, summary: string, fields: array<string, array{state: string, expected: string, why: string|null}>}, answers: array<string, mixed>}
     */
    public function check(array $answers, bool $reveal): array
    {
        $result = new StepResult($reveal);
        $ok = true;

        foreach ($this->readings as $index => $reading) {
            $ok = $result->mark("han{$index}", Answers::int($answers, "han{$index}") === $reading->han, (string) $reading->han, self::hanWhy($reading)) && $ok;
            $ok = $result->mark("fu{$index}", Answers::int($answers, "fu{$index}") === $reading->fu(), (string) $reading->fu(), self::fuWhy($reading)) && $ok;

            if ($reveal) {
                $answers["han{$index}"] = $reading->han;
                $answers["fu{$index}"] = $reading->fu();
            }
        }

        $best = $this->bestIndexes();
        $choice = Answers::string($answers, 'choice');
        $ok = $result->mark(
            'choice',
            $choice !== null && ctype_digit($choice) && in_array((int) $choice, $best, true),
            implode(' / ', array_map(fn (int $index): string => self::LETTERS[$index], $best)),
            $this->choiceWhy(),
        ) && $ok;

        if ($reveal) {
            $answers['choice'] = (string) $best[0];
        }

        $winner = $this->readings[$best[0]];

        return [
            'result' => $result->toArray($ok, __('Lecture :letter : :han han, :fu fu', ['letter' => self::LETTERS[$best[0]], 'han' => $winner->han, 'fu' => $winner->fu()])),
            'answers' => $answers,
        ];
    }

    /**
     * Ce que paie une lecture, en une valeur courte.
     */
    public function payLine(ScoredReading $reading): string
    {
        $payment = $reading->payment;
        $hand = $this->training->hand();

        return match (true) {
            ! $hand->tsumo => Format::points((int) $payment->ron),
            $hand->isDealer() => __(':each chacun (:total)', ['each' => Format::points((int) $payment->each), 'total' => Format::points($payment->total)]),
            default => Format::points((int) $payment->fromOthers).' / '.Format::points((int) $payment->fromDealer).' ('.Format::points($payment->total).')',
        };
    }

    /**
     * Les groupes d'une lecture, prêts à dessiner : tuiles, nom du groupe, tuile gagnante.
     *
     * @return list<array{tiles: list<int>, label: string, win: int|null}>
     */
    public function groups(ScoredReading $scored): array
    {
        $reading = $scored->reading;
        $win = $this->training->hand()->win;

        if ($reading->chiitoitsu) {
            return array_map(fn (int $pair): array => [
                'tiles' => [$pair, $pair],
                'label' => __('paire'),
                'win' => $pair === $win ? 1 : null,
            ], $reading->pairs);
        }

        $rows = [];

        foreach ($reading->groups as $group) {
            $tiles = $group->tiles();

            $rows[] = [
                'tiles' => $tiles,
                'label' => self::groupLabel($group),
                'win' => match (true) {
                    ! $group->winHere => null,
                    $group->isSet() => count($tiles) - 1,
                    default => (int) array_search($win, $tiles, true),
                },
            ];
        }

        $rows[] = ['tiles' => [(int) $reading->pair, (int) $reading->pair], 'label' => __('paire'), 'win' => $reading->pairWin ? 1 : null];

        return $rows;
    }

    private static function groupLabel(Group $group): string
    {
        if ($group->meld !== null) {
            return $group->meld->label();
        }

        return match (true) {
            $group->isSequence() => __('suite'),
            $group->ronDone => __('brelan complété par ron'),
            default => __('brelan caché'),
        };
    }

    private static function hanWhy(ScoredReading $scored): string
    {
        $parts = array_map(fn (YakuHan $yaku): string => $yaku->yaku->shortLabel().' '.$yaku->han, $scored->reading->yaku);
        $dora = $scored->han - $scored->reading->yakuHan();

        if ($dora > 0) {
            $parts[] = __('dora :count', ['count' => $dora]);
        }

        return implode(' + ', $parts).' = '.$scored->han.' han';
    }

    private static function fuWhy(ScoredReading $scored): string
    {
        $fu = $scored->reading->fu;

        if ($scored->reading->chiitoitsu) {
            return $fu->parts[0]->why;
        }

        $sum = implode(' + ', array_map(fn (FuPart $part): int => $part->fu, array_filter($fu->parts, fn (FuPart $part): bool => $part->fu > 0)));

        return __('Somme : :sum = :raw.', ['sum' => $sum, 'raw' => $fu->raw]).' '.$fu->finalWhy;
    }

    private function choiceWhy(): string
    {
        $best = $this->bestIndexes();

        if (count($best) > 1) {
            return __('Ces lectures paient autant : l’une ou l’autre convient. On garde toujours la lecture la plus payante.');
        }

        return __('On garde toujours la lecture la plus payante : ici la lecture :letter, :points.', [
            'letter' => self::LETTERS[$best[0]],
            'points' => $this->payLine($this->readings[$best[0]]),
        ]);
    }
}
