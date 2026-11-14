<?php

namespace App\Domain\Mahjong\Hand;

use App\Domain\Mahjong\Tile;

/**
 * Les attentes d'une main tenpai : les tuiles qui la complètent, par la forme seule (yaku ou non).
 * Une tuile dont les quatre exemplaires sont déjà dans la main n'est pas une attente : elle ne peut plus arriver.
 */
final class WaitFinder
{
    /** Les treize terminales et honneurs du kokushi musou. */
    private const array ORPHANS = [0, 8, 9, 17, 18, 26, 27, 28, 29, 30, 31, 32, 33];

    /**
     * @param  list<int>  $closed  tuiles cachées (13 moins 3 par appel)
     * @param  list<Meld>  $melds
     * @return list<int>
     */
    public static function waits(array $closed, array $melds): array
    {
        $counts = Tile::counts($closed);
        $held = self::held($closed, $melds);

        $waits = [];

        for ($tile = 0; $tile < Tile::COUNT; $tile++) {
            if ($held[$tile] >= 4) {
                continue;
            }

            $counts[$tile]++;

            if (self::isComplete($counts, $melds === [])) {
                $waits[] = $tile;
            }

            $counts[$tile]--;
        }

        return $waits;
    }

    /**
     * Les tuiles que la main attend mais dont les quatre exemplaires sont déjà chez le joueur (« karaten »).
     *
     * @param  list<int>  $closed
     * @param  list<Meld>  $melds
     * @return list<int>
     */
    public static function exhausted(array $closed, array $melds): array
    {
        $counts = Tile::counts($closed);
        $held = self::held($closed, $melds);

        $exhausted = [];

        for ($tile = 0; $tile < Tile::COUNT; $tile++) {
            if ($held[$tile] < 4) {
                continue;
            }

            // Une cinquième tuile : on vérifie la forme comme si elle pouvait encore arriver.
            $counts[$tile]++;

            if (self::isComplete($counts, $melds === [])) {
                $exhausted[] = $tile;
            }

            $counts[$tile]--;
        }

        return $exhausted;
    }

    /**
     * Les exemplaires de chaque tuile chez le joueur : main cachée et appels (quatrième tuile des kan comprise).
     *
     * @param  list<int>  $closed
     * @param  list<Meld>  $melds
     * @return array<int, int>
     */
    private static function held(array $closed, array $melds): array
    {
        $tiles = $closed;

        foreach ($melds as $meld) {
            array_push($tiles, ...$meld->tiles());
        }

        return Tile::counts($tiles);
    }

    /**
     * @param  array<int, int>  $counts  tuiles cachées, la tuile ajoutée comprise
     */
    private static function isComplete(array $counts, bool $closedOnly): bool
    {
        if (Decomposer::decompose($counts) !== []) {
            return true;
        }

        if (! $closedOnly) {
            return false;
        }

        if (count(array_filter($counts, fn (int $count): bool => $count === 2)) === 7) {
            return true;
        }

        return self::isKokushi($counts);
    }

    /**
     * @param  array<int, int>  $counts
     */
    private static function isKokushi(array $counts): bool
    {
        $pair = false;

        foreach ($counts as $tile => $count) {
            if ($count === 0) {
                continue;
            }

            if (! in_array($tile, self::ORPHANS, true) || $count > 2 || ($count === 2 && $pair)) {
                return false;
            }

            $pair = $pair || $count === 2;
        }

        return $pair && count(array_filter($counts)) === 13;
    }
}
