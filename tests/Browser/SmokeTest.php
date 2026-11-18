<?php

it('affiche les pages principales sans erreur JavaScript', function () {
    visit(['/', '/bareme', '/comptage/tenpai', '/exercice/fu/tenpai', '/exercice/lectures/tenpai', '/attentes/tenpai', '/hors-ligne', '/apprendre', '/apprendre/les-tuiles', '/apprendre/deroulement-d-une-partie', '/apprendre/calcul-des-points', '/apprendre/lexique', '/en/learn'])->assertNoSmoke();
});

it('coupe les sons depuis l’en-tête et garde ce choix', function () {
    $page = visit('/attentes/tenpai');

    $page->assertAttribute('[data-sound-toggle]', 'aria-pressed', 'true')
        ->click('[data-sound-toggle]')
        ->assertAttribute('[data-sound-toggle]', 'aria-pressed', 'false')
        ->assertScript('localStorage.getItem("tenpai-sons")', 'false')
        ->refresh()
        ->assertAttribute('[data-sound-toggle]', 'aria-pressed', 'false')
        ->click('[data-sound-toggle]')
        ->assertAttribute('[data-sound-toggle]', 'aria-pressed', 'true')
        ->assertNoJavaScriptErrors();
});
