<?php

namespace App\Domain\Mahjong\Scoring;

use App\Domain\Mahjong\Hand\Group;
use App\Domain\Mahjong\Hand\GroupKind;
use App\Domain\Mahjong\Hand\Hand;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Tile;
use App\Domain\Mahjong\Wait;

/**
 * Décompte des fu, ligne par ligne, avec l'explication de chaque valeur.
 */
final class FuCalculator
{
    /**
     * @param  list<Group>  $groups
     */
    public static function standard(array $groups, int $pair, Wait $wait, Hand $hand, bool $menzen, bool $pinfu, Rules $rules): FuCount
    {
        $parts = [new FuPart('base', 20, 'Toute main normale part de 20 fu (fuutei).')];
        $parts[] = self::winPart($hand, $menzen, $pinfu);

        foreach ($groups as $index => $group) {
            $parts[] = self::groupPart($index, $group);
        }

        $parts[] = self::pairPart($pair, $hand, $rules);
        $parts[] = new FuPart('wait', $wait->fu(), $wait->description()." {$wait->fu()} fu.", wait: $wait);

        $raw = array_sum(array_map(fn (FuPart $part): int => $part->fu, $parts));
        $fu = (int) ceil($raw / 10) * 10;
        $finalWhy = $raw % 10 !== 0
            ? "On arrondit {$raw} à la dizaine supérieure : {$fu}."
            : "{$raw} est déjà une dizaine.";

        if (! $menzen && $fu === 20) {
            $fu = 30;
            $finalWhy = 'Une main ouverte à 20 fu est portée à 30 fu.';
        }

        return new FuCount($fu, $raw, $parts, $finalWhy);
    }

    public static function chiitoitsu(): FuCount
    {
        return new FuCount(
            fu: 25,
            raw: 25,
            parts: [new FuPart('chiitoi', 25, 'Chiitoitsu : 25 fu fixes, sans arrondi ni autre fu.', wait: Wait::Tanki)],
            finalWhy: 'Chiitoitsu : 25 fu, jamais arrondis.',
        );
    }

    private static function winPart(Hand $hand, bool $menzen, bool $pinfu): FuPart
    {
        if ($hand->tsumo) {
            return $pinfu
                ? new FuPart('win', 0, 'Tsumo en pinfu : pas de fu de tsumo, la main vaut 20 fu pile.')
                : new FuPart('win', 2, 'Victoire par tsumo : +2 fu.');
        }

        return $menzen
            ? new FuPart('win', 10, 'Ron avec une main fermée (menzen kafu) : +10 fu.')
            : new FuPart('win', 0, 'Ron avec une main ouverte : 0 fu.');
    }

    private static function groupPart(int $index, Group $group): FuPart
    {
        $terminalOrHonor = Tile::isTerminalOrHonor($group->tile);
        $kind = $terminalOrHonor ? 'terminales/honneurs' : 'simples';
        $state = $group->concealed ? 'fermé' : 'ouvert';

        [$fu, $why] = match ($group->kind) {
            GroupKind::Sequence => [0, 'Suite : 0 fu.'],
            GroupKind::Triplet => self::tripletFu($group, $terminalOrHonor, $kind, $state),
            GroupKind::Kan => [
                $fu = ($terminalOrHonor ? 16 : 8) * ($group->concealed ? 2 : 1),
                "Carré {$state} de {$kind} : {$fu} fu.",
            ],
        };

        return new FuPart('g'.$index, $fu, $why, group: $group);
    }

    /**
     * @return array{int, string}
     */
    private static function tripletFu(Group $group, bool $terminalOrHonor, string $kind, string $state): array
    {
        $fu = ($terminalOrHonor ? 4 : 2) * ($group->concealed ? 2 : 1);

        $why = $group->ronDone
            ? "Brelan complété par ron : il compte comme ouvert. Brelan ouvert de {$kind} : {$fu} fu."
            : "Brelan {$state} de {$kind} : {$fu} fu.";

        return [$fu, $why];
    }

    private static function pairPart(int $pair, Hand $hand, Rules $rules): FuPart
    {
        $seat = $hand->seat->tile();
        $round = $hand->round->tile();

        [$fu, $why] = match (true) {
            Tile::isDragon($pair) => [2, 'Paire de dragons : 2 fu.'],
            $pair === $seat && $pair === $round => [
                $fu = $rules->doubleWind4 ? 4 : 2,
                "Paire du vent du siège qui est aussi le vent de la manche : {$fu} fu.",
            ],
            $pair === $seat => [2, 'Paire de votre vent de siège : 2 fu.'],
            $pair === $round => [2, 'Paire du vent de la manche : 2 fu.'],
            default => [0, 'Paire sans valeur : 0 fu.'],
        };

        return new FuPart('pair', $fu, $why, pair: $pair);
    }
}
