<?php

use App\Http\Controllers\DailyController;
use App\Http\Controllers\FormerCountingController;
use App\Http\Controllers\HandPreviewController;
use App\Http\Controllers\HandTilesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearnController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\RulesController;
use App\Http\Controllers\ScoringController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TileSpriteController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\WaitsController;
use App\Http\Middleware\SetLocale;
use App\Livewire\Readings;
use App\Livewire\Training;
use App\Livewire\Waits;
use App\Support\Locale;
use App\Training\Drill;
use App\View\Learn\Guide;
use Illuminate\Support\Facades\Route;

/*
| Les pages, une fois par langue : le français sans préfixe (noms « counting »…),
| l'anglais sous /en avec ses propres mots (noms « en.counting »…).
*/

$seed = '[A-Za-z0-9][A-Za-z0-9-]{0,39}';

foreach (Locale::LOCALES as $locale => $words) {
    Route::middleware(SetLocale::class.':'.$locale)
        ->prefix($words['prefix'])
        ->name($locale === Locale::DEFAULT ? '' : "{$locale}.")
        ->group(function () use ($words, $seed): void {
            Route::get('/', HomeController::class)->name('home');

            Route::get("/{$words['counting']}", TrainingController::class)->name('counting');

            Route::livewire("/{$words['counting']}/{seed}", Training::class)
                ->where('seed', $seed)
                ->name('counting.hand');

            Route::get("/{$words['formerCounting']}/{seed?}", FormerCountingController::class)
                ->where('seed', $seed);

            Route::get("/{$words['waits']}", WaitsController::class)->name('waits');

            Route::livewire("/{$words['waits']}/{seed}", Waits::class)
                ->where('seed', $seed)
                ->name('waits.hand');

            Route::livewire("/{$words['drill']}/{$words['readings']}/{seed}", Readings::class)
                ->where('seed', $seed)
                ->name('readings.hand');

            Route::livewire("/{$words['drill']}/{drill}/{seed}", Training::class)
                ->whereIn('drill', array_map(fn (Drill $drill): string => $drill->value, Drill::steps()))
                ->where('seed', $seed)
                ->name('drill.hand');

            Route::get("/{$words['preview']}/{seed}.png", HandPreviewController::class)
                ->where('seed', $seed)
                ->middleware('throttle:preview')
                ->name('preview');

            Route::get("/{$words['daily']}", DailyController::class)->name('daily');

            Route::get("/{$words['scoring']}", ScoringController::class)->name('scoring');

            Route::get("/{$words['learn']}", LearnController::class)->name('learn');

            foreach (Guide::cases() as $guide) {
                Route::get("/{$words['learn']}/{$words['guides'][$guide->value]}", LearnController::class)
                    ->name($guide->routeName());
            }

            Route::view("/{$words['offline']}", 'hors-ligne')->name('offline');
        });
}

Route::post('/regles', RulesController::class)->name('rules');

Route::get('/tuiles.svg', TileSpriteController::class)->name('tiles.sprite');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/robots.txt', RobotsController::class)->name('robots');

Route::get('/mains.json', HandTilesController::class)
    ->middleware('throttle:hands')
    ->name('hands.tiles');
