<?php

namespace App\Http\Controllers;

use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Hand\Hand;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Scoring\Evaluator;
use App\Domain\Mahjong\Tile;
use App\Domain\Mahjong\Wind;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * La main de démonstration : tenpai sur 5-8 de bambous, gagnée en tsumo sur le 8.
     * Son compte vient du moteur, comme pendant l'entraînement.
     */
    public function __invoke(): View
    {
        $closed = Tile::parse('345m 567p 234s 678s 88p');
        $win = Tile::parse('8s')[0];

        $hand = new Hand(
            closed: $closed,
            melds: [],
            win: $win,
            tsumo: true,
            seat: Wind::South,
            round: Wind::East,
            riichi: true,
            dora: Tile::parse('3z'),
            ura: Tile::parse('3z'),
            aka: 1,
        );

        $waiting = $closed;
        array_splice($waiting, (int) array_search($win, $waiting, true), 1);

        return view('home', [
            'waiting' => $waiting,
            'redTile' => Tile::parse('5p')[0],
            'win' => $win,
            'evaluation' => Evaluator::evaluate($hand, Rules::default()),
            'today' => Seed::daily(now())->dailyDate(),
        ]);
    }
}
