<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Les langues du site. Le français, langue source, n'a pas de préfixe ; l'anglais vit sous /en,
 * avec ses propres mots dans les adresses. Un lien partagé garde ainsi sa langue.
 */
final class Locale
{
    public const string DEFAULT = 'fr';

    /**
     * Préfixe d'adresse et mots des pages, par langue.
     *
     * @var array<string, array{prefix: string, name: string, native: string, counting: string, formerCounting: string, drill: string, waits: string, readings: string, daily: string, scoring: string, offline: string, preview: string, learn: string, guides: array<string, string>}>
     */
    public const array LOCALES = [
        'fr' => [
            'prefix' => '',
            'name' => 'Français',
            'native' => 'fr_FR',
            'counting' => 'comptage',
            'formerCounting' => 'entrainement',
            'drill' => 'exercice',
            'waits' => 'attentes',
            'readings' => 'lectures',
            'daily' => 'defi',
            'scoring' => 'bareme',
            'offline' => 'hors-ligne',
            'preview' => 'apercu',
            'learn' => 'apprendre',
            'guides' => [
                'tiles' => 'les-tuiles',
                'game' => 'deroulement-d-une-partie',
                'scoring' => 'calcul-des-points',
                'glossary' => 'lexique',
            ],
        ],
        'en' => [
            'prefix' => 'en',
            'name' => 'English',
            'native' => 'en_GB',
            'counting' => 'counting',
            'formerCounting' => 'training',
            'drill' => 'drill',
            'waits' => 'waits',
            'readings' => 'readings',
            'daily' => 'daily',
            'scoring' => 'scoring',
            'offline' => 'offline',
            'preview' => 'preview',
            'learn' => 'learn',
            'guides' => [
                'tiles' => 'tiles',
                'game' => 'how-to-play',
                'scoring' => 'scoring-basics',
                'glossary' => 'glossary',
            ],
        ],
    ];

    /**
     * Fixe la langue de l'application et des dates.
     */
    public static function apply(string $locale): void
    {
        $locale = isset(self::LOCALES[$locale]) ? $locale : self::DEFAULT;

        app()->setLocale($locale);
        Carbon::setLocale($locale);
    }

    /**
     * Le nom d'une route dans une langue : « counting » en français, « en.counting » en anglais.
     */
    public static function routeName(string $name, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $locale === self::DEFAULT ? $name : "{$locale}.{$name}";
    }

    /**
     * Le nom d'une route sans sa langue : « en.counting » → « counting ».
     */
    public static function baseRouteName(string $name): string
    {
        foreach (array_keys(self::LOCALES) as $locale) {
            if ($locale !== self::DEFAULT && str_starts_with($name, "{$locale}.")) {
                return substr($name, strlen($locale) + 1);
            }
        }

        return $name;
    }

    /**
     * L'adresse de la page en cours dans chaque langue (balises hreflang, lien de langue).
     *
     * @return array<string, string>
     */
    public static function alternates(Request $request): array
    {
        $route = $request->route();
        $name = $route?->getName();

        if ($name === null || ! app('router')->has(self::routeName(self::baseRouteName($name), 'en'))) {
            return [];
        }

        $base = self::baseRouteName($name);

        // Seuls les paramètres de l'adresse : les valeurs par défaut d'une route (vue, statut…) n'y ont pas leur place.
        $parameters = array_intersect_key($route->parameters(), array_flip($route->parameterNames()));

        return collect(array_keys(self::LOCALES))
            ->mapWithKeys(fn (string $locale): array => [$locale => route(self::routeName($base, $locale), $parameters)])
            ->all();
    }
}
