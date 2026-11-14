<?php

namespace App\Providers;

use App\Domain\Mahjong\Text;
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
    }
}
