<?php

namespace App\Http\Controllers;

use App\View\ScoringSheet;
use Illuminate\Contracts\View\View;

class ScoringController extends Controller
{
    public function __invoke(): View
    {
        return view('bareme', ['sheet' => new ScoringSheet]);
    }
}
