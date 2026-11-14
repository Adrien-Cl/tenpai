<?php

use App\Http\Controllers\DailyController;
use App\Http\Controllers\HandPreviewController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RulesController;
use App\Http\Controllers\ScoringController;
use App\Http\Controllers\TileSpriteController;
use App\Http\Controllers\TrainingController;
use App\Http\Middleware\SetLocale;
use App\Livewire\Training;
use App\Support\Locale;
use Illuminate\Support\Facades\Route;

/*
| Les pages, une fois par langue : le français sans préfixe (noms « training »…),
| l'anglais sous /en avec ses propres mots (noms « en.training »…).
*/

$seed = '[A-Za-z0-9][A-Za-z0-9-]{0,39}';

foreach (Locale::LOCALES as $locale => $words) {
    Route::middleware(SetLocale::class.':'.$locale)
        ->prefix($words['prefix'])
        ->name($locale === Locale::DEFAULT ? '' : "{$locale}.")
        ->group(function () use ($words, $seed): void {
            Route::get('/', HomeController::class)->name('home');

            Route::get("/{$words['training']}", TrainingController::class)->name('training');

            Route::livewire("/{$words['training']}/{seed}", Training::class)
                ->where('seed', $seed)
                ->name('training.hand');

            Route::livewire("/{$words['drill']}/{drill}/{seed}", Training::class)
                ->whereIn('drill', array_keys(Training::DRILLS))
                ->where('seed', $seed)
                ->name('drill.hand');

            Route::get("/{$words['preview']}/{seed}.png", HandPreviewController::class)
                ->where('seed', $seed)
                ->name('preview');

            Route::get("/{$words['daily']}", DailyController::class)->name('daily');

            Route::get("/{$words['scoring']}", ScoringController::class)->name('scoring');

            Route::view("/{$words['offline']}", 'hors-ligne')->name('offline');
        });
}

Route::post('/regles', RulesController::class)->name('rules');

Route::get('/tuiles.svg', TileSpriteController::class)->name('tiles.sprite');
