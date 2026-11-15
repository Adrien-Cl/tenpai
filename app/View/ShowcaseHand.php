<?php

namespace App\View;

use App\Domain\Mahjong\Hand\Hand;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Scoring\Evaluation;
use App\Domain\Mahjong\Scoring\Evaluator;
use App\Domain\Mahjong\Tile;
use App\Domain\Mahjong\Wind;
use Illuminate\Support\Arr;

/**
 * Une main de la table d'accueil : tenpai, la tuile gagnante arrive, et son compte vient du moteur.
 * Toutes sont fermées, pour que la rangée garde ses treize tuiles et la place de la quatorzième.
 */
final readonly class ShowcaseHand
{
    /**
     * @param  ?int  $redTile  le cinq dessiné en rouge, quand la main en compte un
     */
    public function __construct(
        public Hand $hand,
        public ?int $redTile = null,
    ) {}

    /**
     * Une main au hasard, différente d'une visite à l'autre.
     */
    public static function random(): self
    {
        return Arr::random(self::all());
    }

    /**
     * Des mains variées : tsumo et ron, donneur ou non, du petit 3 200 au haneman.
     *
     * @return list<self>
     */
    public static function all(): array
    {
        return [
            // Riichi, tsumo, pinfu, tanyao, aka-dora : mangan, 2 000 / 4 000.
            new self(self::hand('345m 567p 234s 678s 88p', '8s', tsumo: true, seat: Wind::South, dora: '3z', ura: '3z', aka: 1), redTile: Tile::parse('5p')[0]),
            // Riichi, chiitoitsu, attente sur le dragon rouge seul : 3 200.
            new self(self::hand('11m 55p 77p 22s 99s 11z 77z', '7z', tsumo: false, seat: Wind::West, dora: '3m', ura: '8p')),
            // Le donneur, sans riichi : honitsu, ittsu, haku, tsumo. Haneman, 6 000 chacun.
            new self(self::hand('123p 456p 789p 11p 555z', '9p', tsumo: true, seat: Wind::East, riichi: false, dora: '2m')),
            // Riichi, pinfu, tanyao, sanshoku : mangan en ron, 8 000.
            new self(self::hand('234m 567m 234p 234s 88p', '4s', tsumo: false, seat: Wind::North, dora: '9m', ura: '1z')),
            // Le donneur : riichi, pinfu, iipeikou, aka-dora. 11 600 en ron.
            new self(self::hand('112233m 456p 789s 55s', '9s', tsumo: false, seat: Wind::East, dora: '1z', ura: '8p', aka: 1), redTile: Tile::parse('5p')[0]),
            // Riichi, chun, un dora, gagnée en kanchan sur le 6 de cercles : 5 200.
            new self(self::hand('234m 567p 789s 99m 777z', '6p', tsumo: false, seat: Wind::South, dora: '1m', ura: '2z')),
        ];
    }

    /**
     * Les treize tuiles en attente, sans la gagnante.
     *
     * @return list<int>
     */
    public function waiting(): array
    {
        $waiting = $this->hand->closed;
        array_splice($waiting, (int) array_search($this->hand->win, $waiting, true), 1);

        return $waiting;
    }

    public function evaluate(): Evaluation
    {
        return Evaluator::evaluate($this->hand, Rules::default());
    }

    private static function hand(string $closed, string $win, bool $tsumo, Wind $seat, string $dora, ?string $ura = null, bool $riichi = true, int $aka = 0): Hand
    {
        return new Hand(
            closed: Tile::parse($closed),
            melds: [],
            win: Tile::parse($win)[0],
            tsumo: $tsumo,
            seat: $seat,
            round: Wind::East,
            riichi: $riichi,
            dora: Tile::parse($dora),
            ura: $riichi && $ura !== null ? Tile::parse($ura) : [],
            aka: $aka,
        );
    }
}
