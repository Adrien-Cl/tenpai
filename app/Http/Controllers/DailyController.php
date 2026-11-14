<?php

namespace App\Http\Controllers;

use App\Domain\Mahjong\Generation\Seed;
use Illuminate\Http\RedirectResponse;

class DailyController extends Controller
{
    /**
     * Le défi du jour : la même main pour tout le monde, tirée de la date (heure de Paris).
     */
    public function __invoke(): RedirectResponse
    {
        return redirect(localized_route('counting.hand', ['seed' => Seed::daily(now())->value]));
    }
}
