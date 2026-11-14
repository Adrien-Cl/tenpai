<?php

namespace App\Http\Controllers;

use App\Domain\Mahjong\Generation\Level;
use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Generation\Theme;
use App\Training\Drill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    /**
     * Tire une graine et redirige vers sa main. Avec ?theme=chinitsu, la main contiendra ce yaku ;
     * avec ?niveau=expert, elle sera plus difficile ; avec ?exercice=fu, elle commencera aux fu
     * (?exercice=attentes et ?exercice=lectures mènent aux exercices à part). Une valeur inconnue est ignorée.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $drill = Drill::tryFrom($request->string('exercice')->toString());
        $theme = Theme::tryFrom($request->string('theme')->toString()) ?? Theme::Free;
        $level = Level::tryFrom($request->string('niveau')->toString()) ?? Level::Standard;

        if ($drill !== null && ! $drill->usesTheme()) {
            $theme = Theme::Free;
        }

        $seed = Seed::themed($theme, $level);

        return redirect($drill?->url($seed) ?? localized_route('counting.hand', ['seed' => $seed->value]));
    }
}
