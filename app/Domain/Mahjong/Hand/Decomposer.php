<?php

namespace App\Domain\Mahjong\Hand;

use App\Domain\Mahjong\Tile;

/**
 * Trouve toutes les lectures « une paire + groupes » d'un ensemble de tuiles.
 */
final class Decomposer
{
    /**
     * @param  array<int, int>  $counts  exemplaires par tuile (0 à 33)
     * @return list<Decomposition>
     */
    public static function decompose(array $counts): array
    {
        $found = [];

        for ($pair = 0; $pair < Tile::COUNT; $pair++) {
            if ($counts[$pair] < 2) {
                continue;
            }

            $counts[$pair] -= 2;
            self::groups($counts, 0, [], function (array $groups) use ($pair, &$found): void {
                $found[] = new Decomposition($pair, $groups);
            });
            $counts[$pair] += 2;
        }

        return $found;
    }

    /**
     * Parcours récursif : à chaque tuile restante, on essaie un brelan puis une suite.
     *
     * @param  array<int, int>  $counts
     * @param  list<Group>  $groups
     * @param  callable(list<Group>): void  $found
     */
    private static function groups(array &$counts, int $index, array $groups, callable $found): void
    {
        while ($index < Tile::COUNT && $counts[$index] === 0) {
            $index++;
        }

        if ($index === Tile::COUNT) {
            $found($groups);

            return;
        }

        if ($counts[$index] >= 3) {
            $counts[$index] -= 3;
            self::groups($counts, $index, [...$groups, Group::triplet($index)], $found);
            $counts[$index] += 3;
        }

        if ($index < 27 && $index % 9 <= 6 && $counts[$index + 1] > 0 && $counts[$index + 2] > 0) {
            $counts[$index]--;
            $counts[$index + 1]--;
            $counts[$index + 2]--;
            self::groups($counts, $index, [...$groups, Group::sequence($index)], $found);
            $counts[$index]++;
            $counts[$index + 1]++;
            $counts[$index + 2]++;
        }
    }
}
