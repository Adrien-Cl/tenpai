<?php

namespace App\View;

use App\Support\Locale;
use App\View\Learn\Glossary;
use App\View\Learn\Guide;

/**
 * Les données structurées (schema.org, JSON-LD) qui décrivent les pages aux moteurs de recherche :
 * l'application sur l'accueil, l'article et son fil d'Ariane sur un guide, les définitions du lexique.
 */
final class StructuredData
{
    /**
     * Le site et l'application d'entraînement, pour l'accueil.
     *
     * @return list<array<string, mixed>>
     */
    public static function home(): array
    {
        $home = localized_route('home');

        return [
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'Tenpai',
                'url' => $home,
                'inLanguage' => self::language(),
                'description' => __('Entraîneur gratuit au calcul des points du mahjong riichi, avec des guides pour débutants.'),
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebApplication',
                'name' => 'Tenpai',
                'url' => $home,
                'applicationCategory' => 'EducationalApplication',
                'operatingSystem' => 'All',
                'browserRequirements' => 'Requires JavaScript',
                'inLanguage' => self::language(),
                'description' => __('Comptez une main de mahjong riichi en cinq étapes corrigées et expliquées : yaku, han, dora, fu et points.'),
                'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR'],
                'image' => asset(__('og.png')),
            ],
        ];
    }

    /**
     * L'accueil de la partie « Apprendre » : le fil d'Ariane et la liste des guides.
     *
     * @return list<array<string, mixed>>
     */
    public static function learn(): array
    {
        return [
            self::breadcrumb([[__('Apprendre'), localized_route('learn')]]),
            [
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => __('Apprendre le mahjong riichi'),
                'itemListElement' => array_map(fn (Guide $guide): array => [
                    '@type' => 'ListItem',
                    'position' => array_search($guide, Guide::cases(), true) + 1,
                    'name' => $guide->title(),
                    'url' => localized_route($guide->routeName()),
                ], Guide::cases()),
            ],
        ];
    }

    /**
     * Un guide : l'article, son fil d'Ariane, et pour le lexique ses définitions.
     *
     * @return list<array<string, mixed>>
     */
    public static function guide(Guide $guide): array
    {
        $url = localized_route($guide->routeName());

        $article = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $guide->title(),
            'description' => $guide->description(),
            'url' => $url,
            'mainEntityOfPage' => $url,
            'inLanguage' => self::language(),
            'image' => asset(__('og.png')),
            'author' => ['@type' => 'Organization', 'name' => 'Tenpai', 'url' => localized_route('home')],
            'publisher' => ['@type' => 'Organization', 'name' => 'Tenpai', 'url' => localized_route('home')],
            'about' => ['@type' => 'Thing', 'name' => __('Mahjong riichi')],
        ];

        $data = [
            self::breadcrumb([[__('Apprendre'), localized_route('learn')], [$guide->label(), $url]]),
            $article,
        ];

        if ($guide === Guide::Glossary) {
            $data[] = [
                '@context' => 'https://schema.org',
                '@type' => 'DefinedTermSet',
                'name' => $guide->title(),
                'url' => $url,
                'inLanguage' => self::language(),
                'hasDefinedTerm' => array_map(fn (array $entry): array => [
                    '@type' => 'DefinedTerm',
                    'name' => $entry['term'],
                    'alternateName' => $entry['japanese'],
                    'description' => $entry['definition'],
                    'url' => "{$url}#{$entry['id']}",
                ], Glossary::entries()),
            ];
        }

        return $data;
    }

    /**
     * Le barème : son fil d'Ariane.
     *
     * @return list<array<string, mixed>>
     */
    public static function scoring(): array
    {
        return [self::breadcrumb([[__('Barème'), localized_route('scoring')]])];
    }

    /**
     * Les données, prêtes à poser dans une balise <script type="application/ld+json">.
     *
     * @param  list<array<string, mixed>>  $data
     */
    public static function toJson(array $data): string
    {
        $data = count($data) === 1 ? $data[0] : $data;

        // JSON_HEX_TAG : un « </script> » dans un texte ne peut pas fermer la balise.
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_THROW_ON_ERROR);
    }

    /**
     * Le fil d'Ariane, à partir de l'accueil.
     *
     * @param  list<array{0: string, 1: string}>  $crumbs
     * @return array<string, mixed>
     */
    private static function breadcrumb(array $crumbs): array
    {
        $crumbs = [['Tenpai', localized_route('home')], ...$crumbs];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $crumb, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb[0],
                'item' => $crumb[1],
            ], $crumbs, array_keys($crumbs)),
        ];
    }

    private static function language(): string
    {
        return str_replace('_', '-', Locale::LOCALES[app()->getLocale()]['native']);
    }
}
