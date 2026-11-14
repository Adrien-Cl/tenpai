<?php

use App\View\Backdrop;

it('suit le système sans fond choisi', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('data-theme=', false)
        ->assertSee('<meta name="theme-color" content="#f4eddc" media="(prefers-color-scheme: light)">', false)
        ->assertSee('<meta name="theme-color" content="#131922" media="(prefers-color-scheme: dark)">', false)
        ->assertSee('aria-label="Fond foncé"', false);
});

it('rend le fond choisi par le bouton, écrit en clair par le JavaScript', function (Backdrop $backdrop) {
    $this->withUnencryptedCookie(Backdrop::COOKIE, $backdrop->value)
        ->get('/bareme')
        ->assertOk()
        ->assertSee('data-theme="'.$backdrop->value.'"', false)
        ->assertSee('<meta name="theme-color" content="'.$backdrop->themeColor().'" media="(prefers-color-scheme: light)">', false)
        ->assertSee('<meta name="theme-color" content="'.$backdrop->themeColor().'" media="(prefers-color-scheme: dark)">', false);
})->with([Backdrop::Light, Backdrop::Dark]);

it('ignore un fond inconnu', function () {
    $this->withUnencryptedCookie(Backdrop::COOKIE, 'sepia')
        ->get('/')
        ->assertOk()
        ->assertDontSee('data-theme=', false);
});

it('traduit le bouton du fond', function () {
    $this->get('/en')
        ->assertOk()
        ->assertSee('aria-label="Dark background"', false);
});
