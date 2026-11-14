<?php

namespace App\Domain\Mahjong\Generation;

use App\Domain\Mahjong\Hand\Group;
use App\Domain\Mahjong\Hand\Hand;
use App\Domain\Mahjong\Hand\Meld;
use App\Domain\Mahjong\Hand\MeldType;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Scoring\Evaluation;
use App\Domain\Mahjong\Scoring\Evaluator;
use App\Domain\Mahjong\Tile;
use App\Domain\Mahjong\Wind;
use App\Domain\Mahjong\Yaku;
use RuntimeException;

/**
 * Tire une main gagnante valide (au moins un yaku, jamais de yakuman) à partir d'une graine.
 *
 * La main ne dépend que de la graine : les cinq rouges sont toujours tirés, et le tri
 * des mains se fait avec les règles par défaut. Les règles du joueur ne servent qu'au compte.
 * Les honba et les bâtons de riichi sont tirés à part : ils ne changent pas la main.
 */
final class HandGenerator
{
    private const int MAX_ATTEMPTS = 2000;

    /**
     * @param  (callable(GeneratedHand, Evaluation): bool)|null  $accept  condition de plus sur la main (exercices)
     *
     * @throws RuntimeException si aucune main ne convient
     */
    public function generate(Seed $seed, ?callable $accept = null): GeneratedHand
    {
        $random = new SeededRandom($seed);
        $builder = new ThemeBuilder($random);
        $level = $seed->level();

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $result = $this->attempt($seed, $random, $builder, $level);

            if ($result === null) {
                continue;
            }

            [$generated, $evaluation] = $result;

            // On garde une majorité de mains sous le mangan, là où les fu comptent vraiment.
            if ($evaluation->payment()->limit !== null && $attempt < 1000 && $random->chance(0.45)) {
                continue;
            }

            // Débutant : une seule façon de lire la main. Expert : plusieurs, le plus souvent.
            if ($level === Level::Beginner && $evaluation->multiple && $attempt < 1500) {
                continue;
            }

            if ($level === Level::Expert && ! $evaluation->multiple && $attempt < 1000 && $random->chance(0.6)) {
                continue;
            }

            if ($accept !== null && ! $accept($generated, $evaluation)) {
                continue;
            }

            return $generated->withHand($this->table($seed, $generated->hand));
        }

        throw new RuntimeException("Génération impossible pour la graine « {$seed} ».");
    }

    /**
     * Les honba et les bâtons de riichi sur la table, tirés sur un flux à part.
     * Le bâton du gagnant est sur la table s'il a déclaré riichi.
     */
    private function table(Seed $seed, Hand $hand): Hand
    {
        $random = new SeededRandom($seed, 'table');
        $roll = $random->float();
        $honba = match (true) {
            $roll < 0.55 => 0,
            $roll < 0.8 => 1,
            $roll < 0.92 => 2,
            $roll < 0.97 => 3,
            default => 4,
        };

        $roll = $random->float();
        $others = match (true) {
            $roll < 0.6 => 0,
            $roll < 0.9 => 1,
            default => 2,
        };

        return $hand->withTable($honba, $others + ($hand->riichi ? 1 : 0));
    }

    /**
     * @return array{GeneratedHand, Evaluation}|null
     */
    private function attempt(Seed $seed, SeededRandom $random, ThemeBuilder $builder, Level $level): ?array
    {
        $odds = $level->odds();
        $seat = Wind::from($random->int(4));
        $round = $random->chance(0.65) ? Wind::East : Wind::South;
        $theme = $seed->theme() ?? Theme::pick($random);
        $used = array_fill(0, Tile::COUNT, 0);
        $closed = [];
        $melds = [];

        if ($theme === Theme::Chiitoitsu) {
            $pairs = $this->chiitoitsuPairs($random);

            foreach ($pairs as $tile) {
                array_push($closed, $tile, $tile);
                $used[$tile] += 2;
            }
        } else {
            $draft = $builder->build($theme, $seat->tile(), $round->tile());

            foreach ($draft->groups as $group) {
                foreach ($group->tiles() as $tile) {
                    $used[$tile]++;
                }
            }

            $used[$draft->pair] += 2;

            if (max($used) > 4) {
                return null;
            }

            $openIndexes = $this->openIndexes($random, $draft);

            foreach ($draft->groups as $index => $group) {
                $keptClosed = in_array($index, $draft->keepClosed, true);

                if (in_array($index, $openIndexes, true)) {
                    $melds[] = $this->openMeld($random, $group, $used, $odds['openKan']);
                } elseif ($group->isSet() && $used[$group->tile] === 3 && ! $keptClosed && $random->chance($odds['closedKan'])) {
                    $melds[] = new Meld(MeldType::ClosedKan, $group->tile);
                    $used[$group->tile]++;
                } else {
                    array_push($closed, ...$group->tiles());
                }
            }

            array_push($closed, $draft->pair, $draft->pair);
        }

        sort($closed);

        $win = $random->pick($closed);
        $menzen = array_filter($melds, fn (Meld $meld): bool => $meld->type->opensHand()) === [];
        $kans = count(array_filter($melds, fn (Meld $meld): bool => $meld->type->isKan()));
        $tsumo = $random->chance(0.45);
        $riichi = $menzen && $random->chance(0.68);
        $doubleRiichi = $riichi && $random->chance($odds['doubleRiichi']);
        $rinshan = $tsumo && $kans > 0 && $random->chance($odds['rinshan']);

        // Chankan : les trois autres exemplaires de la tuile gagnante sont dans le pon d'un adversaire.
        $chankan = ! $tsumo && $used[$win] === 1 && $random->chance($odds['chankan']);

        if ($chankan) {
            $used[$win] = 4;
        }

        $ippatsu = $riichi && ! $rinshan && $random->chance($odds['ippatsu']);
        $haitei = ! $rinshan && ! $chankan && $random->chance($odds['haitei']);

        $dora = [];
        $ura = [];

        for ($i = 0; $i < 1 + $kans; $i++) {
            $dora[] = $this->pickIndicator($random, $used);
        }

        if ($riichi) {
            for ($i = 0; $i < 1 + $kans; $i++) {
                $ura[] = $this->pickIndicator($random, $used);
            }
        }

        if (in_array(null, $dora, true) || in_array(null, $ura, true)) {
            return null;
        }

        [$handTiles, $winTile, $meldViews, $aka] = $this->layout($random, $closed, $win, $melds);

        $hand = new Hand(
            closed: $closed,
            melds: $melds,
            win: $win,
            tsumo: $tsumo,
            seat: $seat,
            round: $round,
            riichi: $riichi,
            doubleRiichi: $doubleRiichi,
            ippatsu: $ippatsu,
            haitei: $haitei,
            rinshan: $rinshan,
            chankan: $chankan,
            dora: $dora,
            ura: $ura,
            aka: $aka,
        );

        $evaluation = Evaluator::evaluate($hand, Rules::default());

        if (! $evaluation->isValid()) {
            return null;
        }

        // Entraînement ciblé : le thème oriente le tirage, mais seul le yaku obtenu compte.
        if ($seed->theme() !== null && ! $this->containsAny($evaluation, $theme->targets())) {
            return null;
        }

        return [new GeneratedHand($seed, $theme, $hand, $handTiles, $winTile, $meldViews), $evaluation];
    }

    /**
     * @param  list<Yaku>  $targets
     */
    private function containsAny(Evaluation $evaluation, array $targets): bool
    {
        foreach ($evaluation->yaku() as $yakuHan) {
            if (in_array($yakuHan->yaku, $targets, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sept paires différentes, tirées dans un des quatre styles (tout simples, semi-pur, libre).
     *
     * @return list<int>
     */
    private function chiitoitsuPairs(SeededRandom $random): array
    {
        $numbered = range(0, 26);
        $honors = range(27, 33);

        $pool = match ($random->int(4)) {
            0 => array_values(array_filter($numbered, Tile::isSimple(...))),
            1 => (function () use ($random, $numbered, $honors): array {
                $suit = $random->int(3);

                return [...array_filter($numbered, fn (int $tile): bool => Tile::suit($tile) === $suit), ...$honors];
            })(),
            default => [...$numbered, ...$honors],
        };

        return array_slice($random->shuffle($pool), 0, 7);
    }

    /**
     * Les groupes appelés : dans 42 % des mains qui le permettent, un ou deux appels.
     *
     * @return list<int>
     */
    private function openIndexes(SeededRandom $random, ThemeDraft $draft): array
    {
        $order = $random->shuffle([0, 1, 2, 3]);

        if ($draft->closedOnly || ! $random->chance(0.42)) {
            return [];
        }

        $count = 1 + $random->int(2);
        $candidates = array_values(array_filter($order, fn (int $index): bool => ! in_array($index, $draft->keepClosed, true)));

        return array_slice($candidates, 0, $count);
    }

    /**
     * @param  array<int, int>  $used
     */
    private function openMeld(SeededRandom $random, Group $group, array &$used, float $kanChance): Meld
    {
        if ($group->isSequence()) {
            return new Meld(MeldType::Chi, $group->tile);
        }

        if ($used[$group->tile] === 3 && $random->chance($kanChance)) {
            $used[$group->tile]++;

            return new Meld(MeldType::OpenKan, $group->tile);
        }

        return new Meld(MeldType::Pon, $group->tile);
    }

    /**
     * @param  array<int, int>  $used
     */
    private function pickIndicator(SeededRandom $random, array &$used): ?int
    {
        for ($try = 0; $try < 50; $try++) {
            $tile = $random->int(Tile::COUNT);

            if ($used[$tile] < 4) {
                $used[$tile]++;

                return $tile;
            }
        }

        return null;
    }

    /**
     * Les tuiles physiques : cinq rouges, tuile gagnante, tuile couchée de chaque appel.
     *
     * @param  list<int>  $closed
     * @param  list<Meld>  $melds
     * @return array{list<TileInstance>, TileInstance, list<MeldView>, int}
     */
    private function layout(SeededRandom $random, array $closed, int $win, array $melds): array
    {
        $closedRed = array_fill(0, count($closed), false);
        $winIndex = (int) array_key_last(array_filter($closed, fn (int $tile): bool => $tile === $win));

        $meldRed = [];
        $rotations = [];

        foreach ($melds as $index => $meld) {
            $meldRed[$index] = array_fill(0, count($meld->tiles()), false);
            $rotations[$index] = match ($meld->type) {
                MeldType::Chi, MeldType::Pon => $random->int(3),
                MeldType::OpenKan => $random->pick([0, 1, 3]),
                MeldType::ClosedKan => null,
            };
        }

        $aka = 0;

        foreach (Tile::FIVES as $five) {
            $pool = [];

            foreach ($closed as $index => $tile) {
                if ($tile === $five) {
                    $pool[] = ['closed', $index, 0];
                }
            }

            foreach ($melds as $meldIndex => $meld) {
                foreach ($meld->tiles() as $tileIndex => $tile) {
                    if ($tile === $five) {
                        $pool[] = ['meld', $meldIndex, $tileIndex];
                    }
                }
            }

            if ($pool !== [] && $random->chance(0.4)) {
                [$where, $index, $tileIndex] = $random->pick($pool);

                if ($where === 'closed') {
                    $closedRed[$index] = true;
                } else {
                    $meldRed[$index][$tileIndex] = true;
                }

                $aka++;
            }
        }

        $handTiles = [];

        foreach ($closed as $index => $tile) {
            if ($index !== $winIndex) {
                $handTiles[] = new TileInstance($tile, $closedRed[$index]);
            }
        }

        usort($handTiles, fn (TileInstance $a, TileInstance $b): int => $a->tile <=> $b->tile ?: $b->red <=> $a->red);

        $meldViews = [];

        foreach ($melds as $index => $meld) {
            $meldViews[] = new MeldView(
                type: $meld->type,
                tile: $meld->tile,
                tiles: array_map(fn (int $tile, bool $red): TileInstance => new TileInstance($tile, $red), $meld->tiles(), $meldRed[$index]),
                rotated: $rotations[$index],
            );
        }

        // L'ordre des appels sur la table varie.
        if ($random->chance(0.5)) {
            $meldViews = array_reverse($meldViews);
        }

        return [$handTiles, new TileInstance($win, $closedRed[$winIndex]), $meldViews, $aka];
    }
}
