<?php

namespace App\Http\Controllers;

use App\Support\TableRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RulesController extends Controller
{
    /**
     * Enregistre les règles de la table dans un cookie, puis revient sur la page.
     * Une case décochée n'est pas envoyée : elle vaut « non ».
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $keys = array_column(TableRules::OPTIONS, 0);

        $request->validate(array_fill_keys($keys, ['sometimes', 'boolean']));

        $rules = TableRules::fromArray(array_combine($keys, array_map(fn (string $key): bool => $request->boolean($key), $keys)));

        return back()->withCookie(cookie()->forever(TableRules::COOKIE, json_encode(TableRules::toArray($rules))));
    }
}
