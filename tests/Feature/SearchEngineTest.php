<?php

/**
 * Les données JSON-LD d'une page, décodées.
 *
 * @return array<int|string, mixed>
 */
function structuredData(string $html): array
{
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);

    expect($match)->not->toBeEmpty('La page n’a pas de données structurées.');

    return json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
}

it('liste dans le sitemap les pages à indexer, reliées à leurs traductions', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=utf-8');

    $xml = simplexml_load_string($response->getContent());
    $urls = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));

    expect($urls)->toContain(url('/'), url('/en'), url('/apprendre'), url('/en/learn/glossary'), url('/apprendre/calcul-des-points'), url('/bareme'))
        ->and($urls)->toHaveCount(14)
        ->and(implode(' ', $urls))->not->toContain('/comptage');

    $response->assertSee('<xhtml:link rel="alternate" hreflang="en" href="'.url('/en/learn/how-to-play').'"/>', false);
});

it('indique le sitemap aux robots', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
        ->assertSee('Sitemap: '.url('/sitemap.xml'))
        ->assertSee('Disallow: /mains.json');
});

it('garde hors des moteurs de recherche les mains tirées au hasard', function (string $url) {
    $this->get($url)->assertSee('<meta name="robots" content="noindex, follow">', false);
})->with(['/comptage/tenpai', '/exercice/fu/tenpai', '/attentes/tenpai', '/exercice/lectures/tenpai']);

it('laisse indexer les pages de contenu, chacune à son adresse canonique', function (string $url) {
    $this->get($url.'?saison=hiver')
        ->assertDontSee('name="robots"', false)
        ->assertSee('<link rel="canonical" href="'.url($url).'">', false);
})->with(['/', '/bareme', '/apprendre', '/en/learn/tiles']);

it('ne glisse pas les valeurs internes d’une route dans les adresses traduites', function () {
    $this->get('/hors-ligne')
        ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/en/offline').'">', false)
        ->assertSee('<meta name="robots" content="noindex">', false);
});

it('décrit l’application aux moteurs de recherche sur l’accueil', function () {
    $data = structuredData($this->get('/')->getContent());

    expect(array_column($data, '@type'))->toBe(['WebSite', 'WebApplication'])
        ->and($data[1])->toMatchArray(['name' => 'Tenpai', 'inLanguage' => 'fr-FR', 'applicationCategory' => 'EducationalApplication']);
});

it('décrit chaque guide comme un article avec son fil d’Ariane', function () {
    $data = structuredData($this->get('/en/learn/scoring-basics')->getContent());

    expect(array_column($data, '@type'))->toBe(['BreadcrumbList', 'Article'])
        ->and(array_column($data[0]['itemListElement'], 'item'))->toBe([url('/en'), url('/en/learn'), url('/en/learn/scoring-basics')])
        ->and($data[1])->toMatchArray(['headline' => 'Scoring in riichi mahjong: the beginner’s guide', 'inLanguage' => 'en-GB']);
});

it('publie les définitions du lexique', function () {
    $terms = structuredData($this->get('/apprendre/lexique')->getContent())[2];

    expect($terms['@type'])->toBe('DefinedTermSet')
        ->and(collect($terms['hasDefinedTerm'])->firstWhere('name', 'Tenpai'))->toMatchArray([
            'alternateName' => '聴牌',
            'url' => url('/apprendre/lexique').'#tenpai',
        ]);
});

it('sert une favicon .ico, déclarée dans chaque page', function () {
    expect(filesize(public_path('favicon.ico')))->toBeGreaterThan(0);

    $this->get('/')->assertSee('<link rel="icon" href="'.asset('favicon.ico').'" sizes="48x48">', false);
});
