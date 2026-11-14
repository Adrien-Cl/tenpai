<?php

use App\Support\Locale;

if (! function_exists('localized_route')) {
    /**
     * L'adresse d'une page dans la langue en cours (ou celle demandée).
     *
     * @param  array<string, mixed>  $parameters
     */
    function localized_route(string $name, array $parameters = [], ?string $locale = null): string
    {
        return route(Locale::routeName($name, $locale), $parameters);
    }
}
