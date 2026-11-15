<?php

use App\View\Tiles\TileSprite;

it('présente l’application sur la page d’accueil', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<html lang="fr"', false)
        ->assertSee('Comptez une main de riichi en cinq étapes, sans calculette.')
        ->assertSee('Commencer une main')
        ->assertSee('Défi du jour')
        ->assertSee('Ouvrir le barème');
});

it('affiche le barème dans l’ordre du comptage : han, fu, points', function () {
    $this->get('/bareme')
        ->assertOk()
        ->assertSeeInOrder(['id="tab-han"', 'id="tab-fu"', 'id="tab-points"'], false)
        ->assertSee('Partir de 20 fu')
        ->assertSee('Au-delà de 4 han, les fu ne comptent plus')
        ->assertSee('Chercher un yaku', false)
        ->assertSee('id="points-data-page"', false)
        ->assertSee('7 700')
        ->assertSee('12 000')
        ->assertSee('Sanshoku doujun')
        ->assertSee('Kokushi musou');
});

it('habille la page du décor de la saison', function (string $season, string $marker) {
    $this->get('/?saison='.$season)
        ->assertOk()
        ->assertSee('data-season="'.$season.'"', false)
        ->assertSee($marker, false);
})->with([
    'printemps : la branche de cerisier' => ['printemps', 'id="sc-petal"'],
    'été : la libellule' => ['ete', 'class="sc-dragonfly"'],
    'automne : les susuki' => ['automne', 'class="sc-grass"'],
    'hiver : la neige sur les bambous' => ['hiver', 'class="sc-snow"'],
]);

it('prend la saison du jour sans paramètre', function () {
    $this->travelTo(new DateTimeImmutable('2026-01-15 12:00:00'));

    $this->get('/')->assertSee('data-season="hiver"', false)->assertSee('neige sur les bambous');
});

it('sert le sprite des tuiles avec un cache long', function () {
    $response = $this->get(TileSprite::url());

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml; charset=utf-8')
        ->assertSee('<symbol id="f-33"', false)
        ->assertSee('<symbol id="f-13r"', false);

    expect($response->headers->get('Cache-Control'))->toContain('immutable');
});

it('répond 304 quand le sprite n’a pas changé', function () {
    $this->get(TileSprite::url(), ['If-None-Match' => '"'.TileSprite::version().'"'])
        ->assertStatus(304);
});
