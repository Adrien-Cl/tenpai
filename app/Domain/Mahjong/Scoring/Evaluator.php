<?php

namespace App\Domain\Mahjong\Scoring;

use App\Domain\Mahjong\Hand\Decomposer;
use App\Domain\Mahjong\Hand\Group;
use App\Domain\Mahjong\Hand\Hand;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Wait;

/**
 * Compte une main : essaie toutes ses lectures et garde celle qui rapporte le plus.
 */
final class Evaluator
{
    public static function evaluate(Hand $hand, Rules $rules): Evaluation
    {
        $menzen = $hand->isClosed();
        $meldGroups = array_map(Group::fromMeld(...), $hand->melds);
        $candidates = [];
        $yakuman = false;

        foreach (Decomposer::decompose($hand->closedCounts()) as $decomposition) {
            $seen = [];

            foreach (self::winSlots($decomposition->pair, $decomposition->groups, $hand->win) as [$slot, $wait]) {
                $key = $slot === -1 ? 'p' : $decomposition->groups[$slot]->kind->value.$decomposition->groups[$slot]->tile.$wait->value;

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $groups = [...self::closedGroups($decomposition->groups, $slot, $hand->tsumo), ...$meldGroups];
                $tiles = YakuDetector::tilesOf($groups, $decomposition->pair);

                if (YakuDetector::isYakuman($groups, $decomposition->pair, $tiles, $menzen)) {
                    $yakuman = true;

                    continue;
                }

                $pinfu = YakuDetector::isPinfu($groups, $decomposition->pair, $wait, $hand, $menzen);

                $candidates[] = new Reading(
                    yaku: YakuDetector::standard($groups, $decomposition->pair, $wait, $hand, $menzen),
                    fu: FuCalculator::standard($groups, $decomposition->pair, $wait, $hand, $menzen, $pinfu, $rules),
                    wait: $wait,
                    groups: $groups,
                    pair: $decomposition->pair,
                    pairWin: $slot === -1,
                );
            }
        }

        if ($hand->melds === [] && count(array_filter($hand->closedCounts(), fn (int $count): bool => $count === 2)) === 7) {
            if (YakuDetector::isYakuman([], null, $hand->closed, true)) {
                $yakuman = true;
            } else {
                $candidates[] = new Reading(
                    yaku: YakuDetector::chiitoitsu($hand->closed, $hand),
                    fu: FuCalculator::chiitoitsu(),
                    wait: Wait::Tanki,
                    groups: [],
                    pair: null,
                    chiitoitsu: true,
                    pairs: array_values(array_unique($hand->closed)),
                    pairWin: true,
                );
            }
        }

        if ($yakuman) {
            return Evaluation::rejected(EvaluationStatus::Yakuman);
        }

        if ($candidates === []) {
            return Evaluation::rejected(EvaluationStatus::Invalid);
        }

        return self::best($hand, $rules, $menzen, $candidates);
    }

    /**
     * @param  list<Reading>  $candidates
     */
    private static function best(Hand $hand, Rules $rules, bool $menzen, array $candidates): Evaluation
    {
        $allTiles = $hand->allTiles();
        $dora = DoraCount::count($allTiles, $hand->dora);
        $ura = $hand->riichi ? DoraCount::count($allTiles, $hand->ura) : DoraCount::none();
        $aka = $rules->aka ? $hand->aka : 0;
        $dealer = $hand->isDealer();

        $best = null;
        $variants = [];

        foreach ($candidates as $reading) {
            if (! $reading->hasYaku()) {
                continue;
            }

            $han = $reading->yakuHan() + $dora->count + $ura->count + $aka;
            $payment = PointsCalculator::points($han, $reading->fu->fu, $dealer, $hand->tsumo, $rules);
            $variants[$reading->wait->value.':'.$reading->fu->fu.':'.$reading->yakuHan()] = true;

            if ($best === null || self::isBetter($payment, $han, $reading->fu->fu, $best)) {
                $best = ['reading' => $reading, 'payment' => $payment, 'han' => $han];
            }
        }

        if ($best === null) {
            return Evaluation::rejected(EvaluationStatus::NoYaku);
        }

        return new Evaluation(
            status: EvaluationStatus::Valid,
            reading: $best['reading'],
            payment: $best['payment'],
            han: $best['han'],
            dora: $dora,
            ura: $ura,
            aka: $aka,
            menzen: $menzen,
            dealer: $dealer,
            multiple: count($variants) > 1,
        );
    }

    /**
     * Plus de points, puis plus de han, puis plus de fu.
     *
     * @param  array{reading: Reading, payment: Payment, han: int}  $best
     */
    private static function isBetter(Payment $payment, int $han, int $fu, array $best): bool
    {
        $comparison = $payment->total <=> $best['payment']->total
            ?: $han <=> $best['han']
            ?: $fu <=> $best['reading']->fu->fu;

        return $comparison > 0;
    }

    /**
     * Les places possibles de la tuile gagnante dans une lecture : la paire (-1) ou un groupe.
     *
     * @param  list<Group>  $groups
     * @return list<array{int, Wait}>
     */
    private static function winSlots(int $pair, array $groups, int $win): array
    {
        $slots = [];

        if ($pair === $win) {
            $slots[] = [-1, Wait::Tanki];
        }

        foreach ($groups as $index => $group) {
            if ($group->isSet() && $group->tile === $win) {
                $slots[] = [$index, Wait::Shanpon];
            }

            if ($group->isSequence() && $win >= $group->tile && $win <= $group->tile + 2) {
                $slots[] = [$index, self::sequenceWait($group->tile, $win)];
            }
        }

        return $slots;
    }

    private static function sequenceWait(int $start, int $win): Wait
    {
        $position = $win - $start;
        $number = $start % 9;

        return match ($position) {
            1 => Wait::Kanchan,
            0 => $number === 6 ? Wait::Penchan : Wait::Ryanmen,
            default => $number === 0 ? Wait::Penchan : Wait::Ryanmen,
        };
    }

    /**
     * Les groupes cachés d'une lecture, avec la tuile gagnante placée.
     * Un brelan complété par ron compte comme ouvert.
     *
     * @param  list<Group>  $groups
     * @return list<Group>
     */
    private static function closedGroups(array $groups, int $slot, bool $tsumo): array
    {
        $result = [];

        foreach ($groups as $index => $group) {
            $ronDone = $group->isSet() && $index === $slot && ! $tsumo;

            $result[] = new Group(
                kind: $group->kind,
                tile: $group->tile,
                open: $ronDone,
                concealed: $group->isSet() && ! $ronDone,
                ronDone: $ronDone,
                winHere: $index === $slot,
            );
        }

        return $result;
    }
}
