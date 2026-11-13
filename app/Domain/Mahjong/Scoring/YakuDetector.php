<?php

namespace App\Domain\Mahjong\Scoring;

use App\Domain\Mahjong\Hand\Group;
use App\Domain\Mahjong\Hand\Hand;
use App\Domain\Mahjong\Tile;
use App\Domain\Mahjong\Wait;
use App\Domain\Mahjong\Yaku;

/**
 * Repère les yaku d'une lecture de la main, et les yakuman pour les écarter.
 */
final class YakuDetector
{
    /**
     * Yaku d'une lecture standard (quatre groupes et une paire).
     *
     * @param  list<Group>  $groups  groupes cachés et appels
     * @return list<YakuHan>
     */
    public static function standard(array $groups, int $pair, Wait $wait, Hand $hand, bool $menzen): array
    {
        $tiles = self::tilesOf($groups, $pair);
        $sequences = array_values(array_filter($groups, fn (Group $group): bool => $group->isSequence()));
        $sets = array_values(array_filter($groups, fn (Group $group): bool => $group->isSet()));
        $seat = $hand->seat->tile();
        $round = $hand->round->tile();

        $found = self::situationYaku($hand, $menzen);

        if (self::isPinfu($groups, $pair, $wait, $hand, $menzen)) {
            $found[] = Yaku::Pinfu;
        }

        if (self::all($tiles, Tile::isSimple(...))) {
            $found[] = Yaku::Tanyao;
        }

        if ($menzen) {
            $identicalPairs = 0;

            foreach (array_count_values(array_map(fn (Group $group): int => $group->tile, $sequences)) as $count) {
                $identicalPairs += intdiv($count, 2);
            }

            if ($identicalPairs >= 2) {
                $found[] = Yaku::Ryanpeikou;
            } elseif ($identicalPairs === 1) {
                $found[] = Yaku::Iipeikou;
            }
        }

        if (count($sets) === 4) {
            $found[] = Yaku::Toitoi;
        }

        if (count(array_filter($sets, fn (Group $group): bool => $group->concealed)) === 3) {
            $found[] = Yaku::Sanankou;
        }

        if (count(array_filter($sets, fn (Group $group): bool => $group->isKan())) === 3) {
            $found[] = Yaku::Sankantsu;
        }

        if (self::hasInEverySuit($sequences, range(0, 6))) {
            $found[] = Yaku::Sanshoku;
        }

        if (self::hasInEverySuit($sets, range(0, 8))) {
            $found[] = Yaku::SanshokuDoukou;
        }

        for ($suit = 0; $suit < 3; $suit++) {
            $starts = array_map(fn (Group $group): int => $group->tile, $sequences);

            if (array_diff([$suit * 9, $suit * 9 + 3, $suit * 9 + 6], $starts) === []) {
                $found[] = Yaku::Ittsu;
                break;
            }
        }

        if (self::all($tiles, Tile::isTerminalOrHonor(...))) {
            $found[] = Yaku::Honroutou;
        } elseif (
            self::all($groups, fn (Group $group): bool => $group->hasTerminalOrHonor())
            && Tile::isTerminalOrHonor($pair)
            && $sequences !== []
        ) {
            $found[] = self::any($tiles, Tile::isHonor(...)) ? Yaku::Chanta : Yaku::Junchan;
        }

        foreach ($sets as $set) {
            if ($set->tile === Tile::HAKU) {
                $found[] = Yaku::Haku;
            }

            if ($set->tile === Tile::HATSU) {
                $found[] = Yaku::Hatsu;
            }

            if ($set->tile === Tile::CHUN) {
                $found[] = Yaku::Chun;
            }

            if ($set->tile === $seat) {
                $found[] = Yaku::SeatWind;
            }

            if ($set->tile === $round) {
                $found[] = Yaku::RoundWind;
            }
        }

        if (count(array_filter($sets, fn (Group $group): bool => Tile::isDragon($group->tile))) === 2 && Tile::isDragon($pair)) {
            $found[] = Yaku::Shousangen;
        }

        $flush = self::flush($tiles);

        if ($flush !== null) {
            $found[] = $flush;
        }

        return self::ordered($found, $menzen);
    }

    /**
     * Yaku d'une main de sept paires.
     *
     * @param  list<int>  $tiles
     * @return list<YakuHan>
     */
    public static function chiitoitsu(array $tiles, Hand $hand): array
    {
        $found = self::situationYaku($hand, true);
        $found[] = Yaku::Chiitoitsu;

        if (self::all($tiles, Tile::isSimple(...))) {
            $found[] = Yaku::Tanyao;
        }

        if (self::all($tiles, Tile::isTerminalOrHonor(...))) {
            $found[] = Yaku::Honroutou;
        }

        $flush = self::flush($tiles);

        if ($flush !== null) {
            $found[] = $flush;
        }

        return self::ordered($found, true);
    }

    /**
     * Pinfu : main fermée, quatre suites, paire sans valeur, attente ryanmen.
     *
     * @param  list<Group>  $groups
     */
    public static function isPinfu(array $groups, int $pair, Wait $wait, Hand $hand, bool $menzen): bool
    {
        $valuePair = Tile::isDragon($pair) || $pair === $hand->seat->tile() || $pair === $hand->round->tile();

        return $menzen
            && self::all($groups, fn (Group $group): bool => $group->isSequence())
            && count($groups) === 4
            && ! $valuePair
            && $wait === Wait::Ryanmen;
    }

    /**
     * La lecture forme-t-elle un yakuman ?
     *
     * @param  list<Group>  $groups
     * @param  list<int>  $tiles  quatorze tuiles (trois par carré)
     */
    public static function isYakuman(array $groups, ?int $pair, array $tiles, bool $menzen): bool
    {
        $sets = array_values(array_filter($groups, fn (Group $group): bool => $group->isSet()));

        if (self::all($tiles, Tile::isHonor(...))
            || self::all($tiles, Tile::isTerminal(...))
            || self::all($tiles, fn (int $tile): bool => in_array($tile, Tile::GREENS, true))) {
            return true;
        }

        if (count(array_filter($sets, fn (Group $group): bool => Tile::isDragon($group->tile))) === 3) {
            return true;
        }

        $windSets = count(array_filter($sets, fn (Group $group): bool => Tile::isWind($group->tile)));

        if ($windSets === 4 || ($windSets === 3 && $pair !== null && Tile::isWind($pair))) {
            return true;
        }

        if (count(array_filter($sets, fn (Group $group): bool => $group->concealed)) === 4) {
            return true;
        }

        if (count(array_filter($groups, fn (Group $group): bool => $group->isKan())) === 4) {
            return true;
        }

        if ($menzen && ! self::any($groups, fn (Group $group): bool => $group->isKan())) {
            $suit = Tile::suit($tiles[0]);

            if ($suit < 3 && self::all($tiles, fn (int $tile): bool => Tile::suit($tile) === $suit)) {
                $numbers = array_fill(0, 9, 0);

                foreach ($tiles as $tile) {
                    $numbers[$tile % 9]++;
                }

                if ($numbers[0] >= 3 && $numbers[8] >= 3 && min(array_slice($numbers, 1, 7)) >= 1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Les quatorze tuiles d'une lecture : trois par groupe (un carré compte pour trois), plus la paire.
     *
     * @param  list<Group>  $groups
     * @return list<int>
     */
    public static function tilesOf(array $groups, ?int $pair): array
    {
        $tiles = [];

        foreach ($groups as $group) {
            array_push($tiles, ...array_slice($group->tiles(), 0, 3));
        }

        if ($pair !== null) {
            array_push($tiles, $pair, $pair);
        }

        return $tiles;
    }

    /**
     * Yaku liés à la situation plutôt qu'à la forme de la main.
     *
     * @return list<Yaku>
     */
    private static function situationYaku(Hand $hand, bool $menzen): array
    {
        $found = [];

        if ($hand->riichi) {
            $found[] = $hand->doubleRiichi ? Yaku::DoubleRiichi : Yaku::Riichi;
        }

        if ($hand->ippatsu) {
            $found[] = Yaku::Ippatsu;
        }

        if ($menzen && $hand->tsumo) {
            $found[] = Yaku::MenzenTsumo;
        }

        if ($hand->haitei) {
            $found[] = $hand->tsumo ? Yaku::Haitei : Yaku::Houtei;
        }

        if ($hand->rinshan) {
            $found[] = Yaku::Rinshan;
        }

        if ($hand->chankan) {
            $found[] = Yaku::Chankan;
        }

        return $found;
    }

    /**
     * Honitsu (une famille et des honneurs) ou chinitsu (une seule famille).
     *
     * @param  list<int>  $tiles
     */
    private static function flush(array $tiles): ?Yaku
    {
        $numbered = array_filter($tiles, fn (int $tile): bool => ! Tile::isHonor($tile));
        $suits = array_unique(array_map(Tile::suit(...), $numbered));

        if (count($suits) !== 1) {
            return null;
        }

        return self::any($tiles, Tile::isHonor(...)) ? Yaku::Honitsu : Yaku::Chinitsu;
    }

    /**
     * Une même valeur de départ dans les trois familles (sanshoku, doukou).
     *
     * @param  list<Group>  $groups
     * @param  list<int>  $offsets
     */
    private static function hasInEverySuit(array $groups, array $offsets): bool
    {
        $starts = array_map(fn (Group $group): int => $group->tile, $groups);

        foreach ($offsets as $offset) {
            if (array_diff([$offset, 9 + $offset, 18 + $offset], $starts) === []) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dédoublonne, remet dans l'ordre d'annonce et attribue les han.
     *
     * @param  list<Yaku>  $found
     * @return list<YakuHan>
     */
    private static function ordered(array $found, bool $menzen): array
    {
        $result = [];

        foreach (Yaku::cases() as $yaku) {
            if (in_array($yaku, $found, true)) {
                $result[] = new YakuHan($yaku, $yaku->han($menzen));
            }
        }

        return $result;
    }

    /**
     * @template T
     *
     * @param  array<T>  $items
     * @param  callable(T): bool  $test
     */
    private static function all(array $items, callable $test): bool
    {
        foreach ($items as $item) {
            if (! $test($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @template T
     *
     * @param  array<T>  $items
     * @param  callable(T): bool  $test
     */
    private static function any(array $items, callable $test): bool
    {
        foreach ($items as $item) {
            if ($test($item)) {
                return true;
            }
        }

        return false;
    }
}
