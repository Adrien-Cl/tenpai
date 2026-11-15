<?php

namespace App\Http\Controllers;

use App\Support\Locale;
use App\View\Learn\Guide;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Les pages à indexer, dans chaque langue, reliées à leurs traductions.
     * Les mains tirées au hasard n'y sont pas : elles sont innombrables et marquées « noindex ».
     */
    public function __invoke(): Response
    {
        $routes = ['home', 'learn', ...array_map(fn (Guide $guide): string => $guide->routeName(), Guide::cases()), 'scoring'];

        $pages = [];

        foreach ($routes as $route) {
            $alternates = collect(array_keys(Locale::LOCALES))
                ->mapWithKeys(fn (string $locale): array => [$locale => localized_route($route, locale: $locale)])
                ->all();

            foreach ($alternates as $url) {
                $pages[] = ['url' => $url, 'alternates' => $alternates];
            }
        }

        return response()
            ->view('sitemap', ['pages' => $pages, 'default' => Locale::DEFAULT])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
