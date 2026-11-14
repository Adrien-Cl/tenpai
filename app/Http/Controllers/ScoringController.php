<?php

namespace App\Http\Controllers;

use App\Support\TableRules;
use App\View\ScoringSheet;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ScoringController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('bareme', ['sheet' => new ScoringSheet(TableRules::fromRequest($request))]);
    }
}
