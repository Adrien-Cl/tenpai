<?php

use App\Http\Middleware\SecurityHeaders;
use App\View\Backdrop;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Le fond est choisi et écrit par le JavaScript, sans passer par le serveur.
        $middleware->encryptCookies(except: [Backdrop::COOKIE]);

        // Seul l'hôte d'APP_URL (et ses sous-domaines) est servi : les adresses absolues ne suivent pas un en-tête Host forgé.
        $middleware->trustHosts();

        $middleware->web(append: [SecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
