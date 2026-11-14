<?php

namespace App\Http\Controllers;

use App\Domain\Mahjong\Generation\Level;
use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Generation\Theme;
use App\Training\Drill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WaitsController extends Controller
{
    /**
     * Tire une graine et redirige vers l'exercice des attentes. Avec ?theme=chinitsu, la main
     * contiendra ce yaku ; avec ?niveau=expert, elle sera plus difficile. Une valeur inconnue est ignorée.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $theme = Theme::tryFrom($request->string('theme')->toString()) ?? Theme::Free;
        $level = Level::tryFrom($request->string('niveau')->toString()) ?? Level::Standard;

        return redirect(Drill::Waits->url(Seed::themed($theme, $level)));
    }
}
