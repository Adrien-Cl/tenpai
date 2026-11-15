<?php

namespace App\Http\Controllers;

use App\Domain\Mahjong\Hand\Hand;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Scoring\Evaluation;
use App\Domain\Mahjong\Scoring\Evaluator;
use App\Domain\Mahjong\Tile;
use App\Domain\Mahjong\Wind;
use App\View\Learn\Guide;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LearnController extends Controller
{
    /**
     * La partie « Apprendre » : son accueil, ou l'un des guides, reconnu au nom de la route.
     */
    public function __invoke(Request $request): View
    {
        $guide = Guide::fromRouteName($request->route()?->getName() ?? '');

        if ($guide === null) {
            return view('apprendre.index');
        }

        return view($guide->view(), [
            'guide' => $guide,
            'example' => $guide === Guide::Scoring ? $this->example() : null,
        ]);
    }

    /**
     * La main comptée de bout en bout dans le guide des points : riichi et Chun, ron sur une attente kanchan.
     * Son compte vient du moteur, comme pendant l'entraînement.
     *
     * @return array{hand: Hand, waiting: list<int>, evaluation: Evaluation}
     */
    private function example(): array
    {
        $waiting = Tile::parse('23499m 678p 57s 777z');
        $win = Tile::parse('6s')[0];

        $hand = new Hand(
            closed: [...$waiting, $win],
            melds: [],
            win: $win,
            tsumo: false,
            seat: Wind::South,
            round: Wind::East,
            riichi: true,
            dora: Tile::parse('1m'),
            ura: Tile::parse('8s'),
        );

        return [
            'hand' => $hand,
            'waiting' => $waiting,
            'evaluation' => Evaluator::evaluate($hand, Rules::default()),
        ];
    }
}
