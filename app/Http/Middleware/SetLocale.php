<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * La langue vient de l'adresse : chaque groupe de routes déclare la sienne.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $locale = Locale::DEFAULT): Response
    {
        Locale::apply($locale);

        return $next($request);
    }
}
