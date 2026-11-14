<?php

namespace App\Providers;

use App\Domain\Mahjong\Text;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Les textes du moteur passent par les fichiers de langue (lang/en.json…), comme ceux des vues.
        Text::translateWith(fn (string $text, array $replace): string => __($text, $replace));

        // Chaque nouvelle graine d'aperçu coûte un dessin GD et une place en cache : on en limite le rythme.
        RateLimiter::for('preview', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
    }
}
