<?php

use App\Domain\Mahjong\Generation\Seed;
use App\Training\TrainingHand;

it('annonce un yaku au clavier, malgré une faute de frappe', function () {
    $page = visit('/comptage/tenpai');

    $page->type('#yaku-search', 'richi')
        ->assertSee('déclaration')
        ->keys('#yaku-search', 'Enter')
        ->assertSee('Riichi ajouté.')
        ->assertPresent('[aria-label="Retirer Riichi"]')
        ->assertNoJavaScriptErrors();
});

it('mène une main jusqu’au récapitulatif et l’enregistre dans les progrès', function () {
    $evaluation = TrainingHand::fromSeed(Seed::fromString('tenpai'))->evaluation;

    $page = visit('/comptage/tenpai');

    foreach (range(1, 5) as $step) {
        $page->click('Voir la réponse')
            ->assertSee('Réponse')
            ->click($step === 5 ? 'Voir le récapitulatif' : 'Étape suivante');
    }

    $page->assertSee('0 étape juste sur 5')
        ->assertSee("{$evaluation->han} han")
        ->assertSee('Main suivante')
        ->assertScript('JSON.parse(localStorage.getItem("tenpai-progres")).hands', 1)
        ->assertScript('JSON.parse(localStorage.getItem("tenpai-a-revoir"))[0].seed', 'tenpai')
        ->assertNoJavaScriptErrors();
});

it('commence un exercice court aux points, les étapes précédentes données', function () {
    $page = visit('/exercice/points/tenpai');

    $page->assertSee('Combien rapporte la main ?')
        ->assertSee('Étape 5 sur 5')
        ->click('Voir la réponse')
        ->click('Voir le récapitulatif')
        ->assertSee('Exercice points seulement')
        ->assertScript('JSON.parse(localStorage.getItem("tenpai-exercices")).points.hands', 1)
        ->assertScript('JSON.parse(localStorage.getItem("tenpai-progres")).hands', 0)
        ->assertNoJavaScriptErrors();
});

it('change les règles depuis l’en-tête', function () {
    $page = visit('/bareme');

    $page->assertSee('Calculé avec : cinq rouges, sans kiriage')
        ->click('[aria-label="Règles de la table"]')
        ->assertSee('Règles de la table')
        ->check('kiriage')
        ->press('Enregistrer')
        ->assertSee('Calculé avec : cinq rouges, kiriage mangan')
        ->assertSee('Kiriage : 4 han 30 fu et 3 han 60 fu comptent comme mangan.')
        ->assertPresent('header button .bg-shu')
        ->assertNoJavaScriptErrors();
});

it('choisit un thème et tire une main de ce thème', function () {
    $page = visit('/comptage/tenpai');

    $page->assertMissing('#picker-themes')
        ->click('[aria-controls="picker-themes"]')
        ->assertVisible('#picker-themes')
        ->click('#picker-themes a[href*="theme=chinitsu"]')
        ->assertPathBeginsWith('/comptage/chinitsu-')
        ->assertSee('Thème Chinitsu')
        ->assertNoJavaScriptErrors();
});

it('passe en exercice court et en expert d’un clic, sans liste déroulante', function () {
    $page = visit('/comptage/tenpai');

    $page->click('[data-picker] a[href*="exercice=fu"]')
        ->assertPathBeginsWith('/exercice/fu/')
        ->click('[data-picker] a[href*="niveau=expert"]')
        ->assertPathBeginsWith('/exercice/fu/expert-')
        ->assertSee('Étape 4 sur 5')
        ->assertNoJavaScriptErrors();
});

it('ouvre l’exercice des attentes depuis le menu', function () {
    $page = visit('/');

    $page->click('header nav a[href$="/attentes"]')
        ->assertPathBeginsWith('/attentes/')
        ->assertSee('Quelles tuiles complètent la main ?')
        ->assertNoJavaScriptErrors();
});

it('parle anglais jusque dans les messages du JavaScript', function () {
    $page = visit('/en/counting/tenpai');

    $page->assertSee('Which yaku?')
        ->type('#yaku-search', 'dora')
        ->assertSee('Dora aren’t yaku: they are counted in step 3.')
        ->clear('#yaku-search')
        ->type('#yaku-search', 'richi')
        ->assertSee('declaration')
        ->keys('#yaku-search', 'Enter')
        ->assertSee('Riichi added.')
        ->click('Show the answer')
        ->assertSee('The yaku in this hand:')
        ->assertNoJavaScriptErrors();
});
