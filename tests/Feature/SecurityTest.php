<?php

use App\Http\Middleware\SecurityHeaders;
use App\Support\TableRules;
use Illuminate\Http\Request;

afterEach(function () {
    // La liste des hôtes acceptés est statique : on la vide pour les tests suivants.
    Request::setTrustedHosts([]);
});

it('pose les en-têtes de sécurité sur les pages', function (string $path) {
    $response = $this->get($path);

    foreach (SecurityHeaders::HEADERS as $name => $value) {
        $response->assertHeader($name, $value);
    }

    $response->assertHeaderMissing('Strict-Transport-Security');
})->with(['/', '/bareme', '/en/scoring']);

it('impose le HTTPS une fois servi en HTTPS', function () {
    $this->get('https://localhost/')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('limite le rythme des aperçus', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->get('/apercu/tenpai.png')->assertOk();
    }

    $this->get('/apercu/tenpai.png')->assertTooManyRequests();
});

it('refuse en production un hôte qui n’est pas celui de l’application', function () {
    config(['app.url' => 'https://tenpai.example']);
    $this->app['env'] = 'production';

    $this->get('https://evil.example/defi')
        ->assertBadRequest()
        ->assertHeaderMissing('Location');

    $this->get('https://tenpai.example/defi')->assertRedirect();
});

it('revient sur la page d’origine après les règles, jamais sur un autre site', function () {
    $this->from('/bareme')
        ->post('/regles', ['aka' => '1'])
        ->assertRedirect('/bareme')
        ->assertCookie(TableRules::COOKIE);

    $this->from('https://evil.example/piege')
        ->post('/regles', ['aka' => '1'])
        ->assertRedirect(route('home'));
});
