<?php

namespace App\Http\Controllers;

use App\Domain\Mahjong\Generation\Seed;
use App\View\ShowcaseHand;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * La main de démonstration change à chaque visite : tenpai, puis la tuile gagnante arrive.
     * Son compte vient du moteur, comme pendant l'entraînement.
     */
    public function __invoke(): View
    {
        $showcase = ShowcaseHand::random();

        return view('home', [
            'waiting' => $showcase->waiting(),
            'redTile' => $showcase->redTile,
            'win' => $showcase->hand->win,
            'tsumo' => $showcase->hand->tsumo,
            'evaluation' => $showcase->evaluate(),
            'today' => Seed::daily(now())->dailyDate(),
        ]);
    }
}
