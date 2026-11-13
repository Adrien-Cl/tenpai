<?php

namespace App\Training;

use App\Domain\Mahjong\Generation\GeneratedHand;
use App\Domain\Mahjong\Generation\HandGenerator;
use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Generation\TileInstance;
use App\Domain\Mahjong\Hand\Hand;
use App\Domain\Mahjong\Hand\MeldType;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Scoring\Evaluation;
use App\Domain\Mahjong\Scoring\Evaluator;
use App\Domain\Mahjong\Scoring\FuPart;

/**
 * La main d'un exercice : tirée de sa graine, comptée selon les règles de la table.
 */
final readonly class TrainingHand
{
    public function __construct(
        public GeneratedHand $generated,
        public Evaluation $evaluation,
        public Rules $rules,
    ) {}

    public static function fromSeed(Seed $seed, ?Rules $rules = null): self
    {
        $rules ??= Rules::default();
        $generated = (new HandGenerator)->generate($seed);

        return new self($generated, Evaluator::evaluate($generated->hand, $rules), $rules);
    }

    public function hand(): Hand
    {
        return $this->generated->hand;
    }

    /**
     * Les lignes de groupes du décompte des fu, avec leurs tuiles telles qu'elles sont sur la table.
     *
     * @return array<string, list<array{tile: int, red: bool, back: bool, win: bool}>>
     */
    public function fuTiles(): array
    {
        $reading = $this->evaluation->reading();
        $redPool = [];

        foreach ([...$this->generated->handTiles, $this->generated->winTile] as $instance) {
            if ($instance->red) {
                $redPool[$instance->tile] = ($redPool[$instance->tile] ?? 0) + 1;
            }
        }

        $winMarked = false;
        $usedMelds = [];
        $rows = [];

        foreach ($reading->fu->parts as $part) {
            if ($part->group !== null && $part->group->meld !== null) {
                $rows[$part->key] = $this->meldTiles($part, $usedMelds);
            } elseif ($part->group !== null) {
                $rows[$part->key] = $this->closedTiles($part->group->tiles(), $part->group->winHere, $redPool, $winMarked);
            } elseif ($part->key === 'pair' && $part->pair !== null) {
                $rows[$part->key] = $this->closedTiles([$part->pair, $part->pair], $reading->pairWin, $redPool, $winMarked);
            }
        }

        return $rows;
    }

    /**
     * Pour chaque tuile posée sur la table, la ligne du décompte des fu à laquelle elle appartient.
     * Sert à éclairer un groupe sur le tapis quand on survole sa ligne.
     *
     * @return array{hand: array<int, string>, win: string|null, melds: array<int, string>}
     */
    public function tableGroups(): array
    {
        $reading = $this->evaluation->reading();
        $map = ['hand' => [], 'win' => null, 'melds' => []];

        if ($reading->chiitoitsu) {
            return $map;
        }

        $needs = [];

        foreach ($reading->fu->parts as $part) {
            if ($part->group !== null && $part->group->meld === null) {
                $tiles = $part->group->tiles();

                if ($part->group->winHere && $map['win'] === null) {
                    $map['win'] = $part->key;
                    array_splice($tiles, (int) array_search($this->hand()->win, $tiles, true), 1);
                }

                $needs[] = [$part->key, $tiles];
            } elseif ($part->key === 'pair' && $part->pair !== null) {
                $tiles = [$part->pair, $part->pair];

                if ($reading->pairWin && $map['win'] === null) {
                    $map['win'] = 'pair';
                    array_pop($tiles);
                }

                $needs[] = ['pair', $tiles];
            }
        }

        foreach ($needs as [$key, $tiles]) {
            foreach ($tiles as $tile) {
                foreach ($this->generated->handTiles as $index => $instance) {
                    if ($instance->tile === $tile && ! isset($map['hand'][$index])) {
                        $map['hand'][$index] = $key;
                        break;
                    }
                }
            }
        }

        $used = [];

        foreach ($reading->fu->parts as $part) {
            if ($part->group?->meld === null) {
                continue;
            }

            foreach ($this->generated->melds as $index => $meld) {
                if (! isset($used[$index]) && $meld->type === $part->group->meld && $meld->tile === $part->group->tile) {
                    $used[$index] = true;
                    $map['melds'][$index] = $part->key;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * @param  list<int>  $tiles
     * @param  array<int, int>  $redPool
     * @return list<array{tile: int, red: bool, back: bool, win: bool}>
     */
    private function closedTiles(array $tiles, bool $winHere, array &$redPool, bool &$winMarked): array
    {
        $row = [];

        foreach ($tiles as $tile) {
            $red = false;

            if (($redPool[$tile] ?? 0) > 0) {
                $red = true;
                $redPool[$tile]--;
            }

            $win = false;

            if ($winHere && ! $winMarked && $tile === $this->hand()->win) {
                $win = true;
                $winMarked = true;
            }

            $row[] = ['tile' => $tile, 'red' => $red, 'back' => false, 'win' => $win];
        }

        return $row;
    }

    /**
     * @param  array<int, true>  $usedMelds
     * @return list<array{tile: int, red: bool, back: bool, win: bool}>
     */
    private function meldTiles(FuPart $part, array &$usedMelds): array
    {
        foreach ($this->generated->melds as $index => $meld) {
            if (isset($usedMelds[$index]) || $meld->type !== $part->group?->meld || $meld->tile !== $part->group->tile) {
                continue;
            }

            $usedMelds[$index] = true;

            return array_map(
                fn (TileInstance $instance, int $position): array => [
                    'tile' => $instance->tile,
                    'red' => $instance->red,
                    'back' => $meld->type === MeldType::ClosedKan && ($position === 0 || $position === 3),
                    'win' => false,
                ],
                $meld->tiles,
                array_keys($meld->tiles),
            );
        }

        return [];
    }
}
