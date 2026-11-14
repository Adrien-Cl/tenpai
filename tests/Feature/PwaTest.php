<?php

it('déclare le manifeste et l’icône Apple dans chaque page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<link rel="manifest" href="'.asset('manifest.webmanifest').'">', false)
        ->assertSee('<link rel="apple-touch-icon" href="'.asset('icons/apple-touch-icon.png').'">', false);
});

it('décrit l’application dans un manifeste dont les icônes existent', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)->toMatchArray(['short_name' => 'Tenpai', 'lang' => 'fr', 'start_url' => '/', 'display' => 'standalone'])
        ->and(array_column($manifest['icons'], 'sizes'))->toContain('192x192', '512x512')
        ->and(array_column($manifest['icons'], 'purpose'))->toContain('maskable');

    foreach ($manifest['icons'] as $icon) {
        expect(public_path(ltrim($icon['src'], '/')))->toBeFile();
    }
});

it('garde hors ligne l’accueil, le barème et une page qui explique la coupure', function () {
    $worker = file_get_contents(public_path('sw.js'));

    expect($worker)->toContain("const KEPT_PAGES = ['/', '/bareme', OFFLINE, '/en', '/en/scoring', OFFLINE_EN]")
        ->and($worker)->toContain("const OFFLINE = '/hors-ligne'")->and($worker)->toContain("const OFFLINE_EN = '/en/offline'");

    $this->get('/hors-ligne')
        ->assertOk()
        ->assertSee('Pas de réseau')
        ->assertSee(route('scoring'), false);
});

it('prépare l’aperçu des liens partagés avec l’image du site', function () {
    expect(public_path('og.png'))->toBeFile();

    $this->get('/')
        ->assertSee('<meta property="og:image" content="'.asset('og.png').'">', false)
        ->assertSee('<meta property="og:title" content="Tenpai · Comptez vos mains de riichi">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
});

it('décrit chaque main partagée sans rien dévoiler de son compte', function (string $url, string $title, string $description) {
    $this->get($url)
        ->assertOk()
        ->assertSee('<meta property="og:title" content="'.$title.' · Tenpai">', false)
        ->assertSee('<meta property="og:description" content="'.e($description).'">', false)
        ->assertSee('<meta property="og:url" content="'.url($url).'">', false);
})->with([
    'main libre' => ['/comptage/tenpai', 'Comptage', 'Une main de riichi à compter en cinq étapes. Le lien redonne toujours la même main.'],
    'défi du jour' => ['/comptage/defi-2026-11-13', 'Défi du jour', 'Le défi du 13 novembre : la même main de riichi pour tout le monde. Saurez-vous la compter ?'],
    'thème' => ['/comptage/chinitsu-test0001', 'Comptage : Chinitsu', 'Une main avec Chinitsu (pur) à compter en cinq étapes. Le lien redonne toujours la même main.'],
    'exercice' => ['/exercice/fu/tenpai', 'Exercice : Fu et points', 'Une main de riichi à compter à partir des fu. Le lien redonne toujours la même main.'],
]);

it('dessine l’aperçu d’une main partagée', function () {
    $response = $this->get('/apercu/tenpai.png');

    $response->assertOk()->assertHeader('Content-Type', 'image/png');
    expect($response->headers->get('Cache-Control'))->toContain('max-age=604800');

    [$width, $height] = getimagesizefromstring($response->getContent());

    expect([$width, $height])->toBe([1200, 630]);
})->skip(fn () => ! function_exists('imagecreatefrompng'), 'GD n’est pas installé.');

it('refuse l’aperçu d’une graine invalide', function () {
    $this->get('/apercu/-mauvaise.png')->assertNotFound();
});

it('donne à chaque page de main l’aperçu de ses tuiles', function () {
    $this->get('/comptage/tenpai')
        ->assertSee('<meta property="og:image" content="'.route('preview', ['seed' => 'tenpai']).'">', false);

    $this->get('/bareme')
        ->assertSee('<meta property="og:image" content="'.asset('og.png').'">', false);
});
