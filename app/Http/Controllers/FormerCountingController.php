<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FormerCountingController extends Controller
{
    /**
     * Les anciennes adresses du comptage (/entrainement, /en/training) : les liens déjà partagés
     * et les mains à revoir gardées dans les navigateurs y mènent encore. Redirection définitive,
     * graine et paramètres (thème, niveau, exercice) compris.
     */
    public function __invoke(Request $request, ?string $seed = null): RedirectResponse
    {
        $target = $seed === null
            ? localized_route('counting', $request->query())
            : localized_route('counting.hand', ['seed' => $seed]);

        return redirect($target, 301);
    }
}
