<?php

use App\Domain\Mahjong\Generation\Seed;
use App\Http\Controllers\DailyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ScoringController;
use App\Http\Controllers\TileSpriteController;
use App\Livewire\Training;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/entrainement', fn () => redirect()->route('training.hand', ['seed' => Seed::random()->value]))
    ->name('training');

Route::livewire('/entrainement/{seed}', Training::class)
    ->where('seed', '[A-Za-z0-9][A-Za-z0-9-]{0,39}')
    ->name('training.hand');

Route::get('/defi', DailyController::class)->name('daily');

Route::get('/bareme', ScoringController::class)->name('scoring');

Route::get('/tuiles.svg', TileSpriteController::class)->name('tiles.sprite');
