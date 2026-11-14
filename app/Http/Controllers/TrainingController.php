<?php

namespace App\Http\Controllers;

use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Generation\Theme;
use App\Livewire\Training;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    /**
     * Tire une graine et redirige vers sa main. Avec ?theme=chinitsu, la main contiendra ce yaku ;
     * avec ?exercice=fu, elle commencera aux fu. Une valeur inconnue est ignorée.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $theme = Theme::tryFrom($request->string('theme')->toString()) ?? Theme::Free;
        $seed = Seed::themed($theme)->value;
        $drill = $request->string('exercice')->toString();

        return isset(Training::DRILLS[$drill])
            ? redirect(localized_route('drill.hand', ['drill' => $drill, 'seed' => $seed]))
            : redirect(localized_route('training.hand', ['seed' => $seed]));
    }
}
