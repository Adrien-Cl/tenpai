<?php

use App\Domain\Mahjong\Generation\Seed;
use App\Livewire\Training;
use App\Training\Steps\DoraStep;
use App\Training\TrainingHand;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

/**
 * Les bonnes réponses d'une main, étape par étape, telles qu'un joueur les taperait.
 *
 * @return array<string, array<string, mixed>>
 */
function correctAnswers(string $seed): array
{
    $hand = TrainingHand::fromSeed(Seed::fromString($seed));
    $evaluation = $hand->evaluation;
    $answers = ['yaku' => ['picked' => []], 'han' => [], 'dora' => [], 'fu' => [], 'points' => []];

    foreach ($evaluation->yaku() as $yakuHan) {
        $answers['yaku']['picked'][] = $yakuHan->yaku->value;
        $answers['han'][$yakuHan->yaku->value] = (string) $yakuHan->han;
    }

    $answers['dora'] = [
        'dora' => (string) $evaluation->dora->count,
        'aka' => (string) $evaluation->aka,
        'ura' => (string) $evaluation->ura->count,
        'han' => (string) $evaluation->han,
    ];

    $reading = $evaluation->reading();

    foreach ($reading->fu->parts as $part) {
        $answers['fu'][$part->key] = (string) $part->fu;
    }

    $answers['fu']['waittype'] = $reading->wait->value;
    $answers['fu']['raw'] = (string) $reading->fu->raw;
    $answers['fu']['final'] = (string) $reading->fu->fu;

    $payment = $evaluation->payment();
    $answers['points'] = [
        'limit' => $payment->limit?->value ?? 'none',
        'ron' => (string) $payment->ron,
        'each' => (string) $payment->each,
        'fromDealer' => (string) $payment->fromDealer,
        'fromOthers' => (string) $payment->fromOthers,
    ];

    return $answers;
}

it('tire une graine au hasard et redirige vers elle', function () {
    $response = $this->get('/entrainement');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toMatch('#/entrainement/[a-z0-9]{8}$#');
});

it('affiche la même main pour la même graine', function () {
    $first = $this->get('/entrainement/k7f2qx9a')->assertOk()->getContent();
    $second = $this->get('/entrainement/k7f2qx9a')->assertOk()->getContent();

    $hand = fn (string $html) => preg_match('/aria-label="Tuiles en main">(.*?)<\/span>\s*<span class="relative/s', $html, $m) ? $m[1] : null;

    expect($hand($first))->not->toBeNull()->toBe($hand($second));
});

it('refuse une graine invalide', function () {
    $this->get('/entrainement/'.str_repeat('a', 41))->assertNotFound();
    $this->get('/entrainement/pas_de_soulignés')->assertNotFound();
});

it('valide une étape juste', function () {
    $answers = correctAnswers('tenpai');

    Livewire::test(Training::class, ['seed' => 'tenpai'])
        ->set('answers.yaku.picked', $answers['yaku']['picked'])
        ->call('check')
        ->assertSet('results.0.ok', true)
        ->assertSet('step', 0)
        ->call('next')
        ->assertSet('step', 1)
        ->assertSet('view', 1)
        ->assertSee('Combien de han vaut chaque yaku ?');
});

it('signale une étape fausse et donne la bonne valeur', function () {
    Livewire::test(Training::class, ['seed' => 'tenpai'])
        ->set('answers.yaku.picked', ['riichi'])
        ->call('check')
        ->assertSet('results.0.ok', false)
        ->assertSet('results.0.fields.riichi.state', 'extra')
        ->assertSee('n’est pas dans cette main');
});

it('montre la réponse sans compter l’étape comme juste', function () {
    $answers = correctAnswers('tenpai');

    Livewire::test(Training::class, ['seed' => 'tenpai'])
        ->call('reveal')
        ->assertSet('results.0.ok', false)
        ->assertSet('results.0.revealed', true)
        ->assertSet('answers.yaku.picked', $answers['yaku']['picked']);
});

it('ne laisse ni sauter une étape ni la corriger deux fois', function () {
    Livewire::test(Training::class, ['seed' => 'tenpai'])
        ->call('next')
        ->assertSet('step', 0)
        ->call('show', 3)
        ->assertSet('view', 0)
        ->set('answers.yaku.picked', ['riichi'])
        ->call('check')
        ->set('answers.yaku.picked', correctAnswers('tenpai')['yaku']['picked'])
        ->call('check')
        ->assertSet('results.0.ok', false);
});

it('empêche le navigateur de modifier la progression', function () {
    Livewire::test(Training::class, ['seed' => 'tenpai'])
        ->set('step', 4);
})->throws(CannotUpdateLockedPropertyException::class);

it('mène une main juste jusqu’au récapitulatif et annonce la fin', function () {
    $answers = correctAnswers('tenpai');
    $component = Livewire::test(Training::class, ['seed' => 'tenpai']);

    foreach (['yaku', 'han', 'dora', 'fu', 'points'] as $index => $key) {
        $component->set("answers.{$key}", $answers[$key])
            ->call('check')
            ->assertSet("results.{$index}.ok", true);

        if ($key === 'points') {
            $component->assertDispatched('hand-finished', seed: 'tenpai', results: [true, true, true, true, true], daily: null);
        }

        $component->call('next');
    }

    $component->assertSet('step', Training::RECAP)
        ->assertSet('view', Training::RECAP)
        ->assertSee('Sans faute');
});

it('garde le calculateur de points caché tant que l’étape des points n’est pas validée', function () {
    Livewire::test(Training::class, ['seed' => 'tenpai'])
        ->assertSee('id="points-data-training"', false)
        ->assertSee('x-show="$wire.results[4] !== undefined"', false);
});

it('passe à une nouvelle main', function () {
    Livewire::test(Training::class, ['seed' => 'tenpai'])
        ->call('nextHand')
        ->assertRedirect();
});

it('tire une main du thème demandé', function () {
    $response = $this->get('/entrainement?theme=chinitsu');

    expect($response->headers->get('Location'))->toMatch('#/entrainement/chinitsu-[a-z0-9]{8}$#');
});

it('tire une main libre pour un thème inconnu', function (string $theme) {
    $response = $this->get('/entrainement?theme='.$theme);

    expect($response->headers->get('Location'))->toMatch('#/entrainement/[a-z0-9]{8}$#');
})->with(['inconnu' => ['kokushi'], 'libre' => ['libre']]);

it('annonce le thème de la main et le garde pour la main suivante', function () {
    Livewire::test(Training::class, ['seed' => 'honitsu-test0001'])
        ->assertSee('<option value="honitsu" selected>Honitsu : semi-pur</option>', false)
        ->call('nextHand')
        ->assertRedirectContains('/entrainement/honitsu-');
});

it('repart sur une main libre après une main libre', function () {
    $component = Livewire::test(Training::class, ['seed' => 'tenpai'])->call('nextHand');

    expect($component->effects['redirect'])->toMatch('#/entrainement/[a-z0-9]{8}$#');
});

it('ouvre un exercice court à la bonne étape, les précédentes données', function (string $drill, int $first) {
    $component = Livewire::test(Training::class, ['seed' => 'tenpai', 'drill' => $drill])
        ->assertSet('step', $first)
        ->assertSet('view', $first)
        ->assertSee('<option value="'.$drill.'" selected>', false)
        ->assertSee('0/'.(5 - $first));

    $results = $component->get('results');

    expect(array_keys($results))->toBe(range(0, $first - 1));

    foreach ($results as $result) {
        expect($result['given'])->toBeTrue()->and($result['ok'])->toBeFalse();
    }

    $component->call('show', 0)->assertSee('Donnée')->assertDontSee('À revoir');
})->with([
    'fu et points' => ['fu', 3],
    'points seulement' => ['points', 4],
]);

it('compte un exercice court sans faute sur les seules étapes jouées', function () {
    $answers = correctAnswers('tenpai');

    $component = Livewire::test(Training::class, ['seed' => 'tenpai', 'drill' => 'fu'])
        ->set('answers.fu', $answers['fu'])
        ->call('check')
        ->call('next')
        ->set('answers.points', $answers['points'])
        ->call('check')
        ->assertDispatched('hand-finished', drill: 'fu', first: 3)
        ->call('next');

    $component->assertSee('2 étapes justes sur 2')->assertSee('Sans faute');
});

it('redirige vers un exercice court, et garde l’exercice pour la main suivante', function () {
    $response = $this->get('/entrainement?exercice=points&theme=pinfu');

    expect($response->headers->get('Location'))->toMatch('#/exercice/points/pinfu-[a-z0-9]{8}$#');

    Livewire::test(Training::class, ['seed' => 'pinfu-test0001', 'drill' => 'points'])
        ->call('nextHand')
        ->assertRedirectContains('/exercice/points/pinfu-');
});

it('refuse un exercice inconnu', function () {
    $this->get('/exercice/yaku/tenpai')->assertNotFound();
});

it('signale en fin de main les yaku oubliés ou dont on a demandé la réponse', function (bool $reveal) {
    $expected = array_map(fn ($yakuHan) => $yakuHan->yaku->value, TrainingHand::fromSeed(Seed::fromString('tenpai'))->evaluation->yaku());

    $component = Livewire::test(Training::class, ['seed' => 'tenpai'])
        ->call($reveal ? 'reveal' : 'check')
        ->call('next');

    foreach (range(1, 3) as $step) {
        $component->call('reveal')->call('next');
    }

    $component->call('reveal')->assertDispatched('hand-finished', missed: $expected);
})->with(['oubliés' => [false], 'réponse demandée' => [true]]);

it('ne signale aucun yaku oublié quand la main est juste ou l’étape donnée', function () {
    $answers = correctAnswers('tenpai');

    $component = Livewire::test(Training::class, ['seed' => 'tenpai'])
        ->set('answers.yaku', $answers['yaku'])
        ->call('check')
        ->call('next');

    foreach (range(1, 3) as $step) {
        $component->call('reveal')->call('next');
    }

    $component->call('reveal')->assertDispatched('hand-finished', missed: []);

    Livewire::test(Training::class, ['seed' => 'tenpai', 'drill' => 'points'])
        ->call('reveal')
        ->assertDispatched('hand-finished', missed: []);
});

it('compte à 0 les fu laissés vides', function () {
    $answers = correctAnswers('tenpai')['fu'];
    $reading = TrainingHand::fromSeed(Seed::fromString('tenpai'))->evaluation->reading();
    $zeros = array_values(array_filter($reading->fu->parts, fn ($part) => $part->fu === 0));

    expect($zeros)->not->toBeEmpty();

    foreach ($zeros as $part) {
        unset($answers[$part->key]);
    }

    $answers[$zeros[0]->key] = ' ';

    $component = Livewire::test(Training::class, ['seed' => 'tenpai', 'drill' => 'fu'])
        ->set('answers.fu', $answers)
        ->call('check')
        ->assertSet('results.3.ok', true);

    foreach ($zeros as $part) {
        $component->assertSet("answers.fu.{$part->key}", 0);
    }
});

it('choisit « aucune » limite quand rien n’est coché', function () {
    $answers = correctAnswers('tenpai')['points'];
    $limit = $answers['limit'];
    unset($answers['limit']);

    Livewire::test(Training::class, ['seed' => 'tenpai', 'drill' => 'points'])
        ->set('answers.points', $answers)
        ->call('check')
        ->assertSet('answers.points.limit', 'none')
        ->assertSet('results.4.fields.limit.state', $limit === 'none' ? 'ok' : 'ko');
});

it('bloque la saisie de caractères autres que des chiffres', function () {
    Livewire::test(Training::class, ['seed' => 'tenpai', 'drill' => 'fu'])
        ->assertSeeHtml('x-on:beforeinput="/\D/.test(')
        ->assertSeeHtml('placeholder="0"');
});

it('compte à 0 les dora laissés vides, mais pas le total des han', function () {
    $answers = correctAnswers('tenpai')['dora'];
    $shown = DoraStep::fields(TrainingHand::fromSeed(Seed::fromString('tenpai')));
    $zeros = array_keys(array_filter($answers, fn (string $value, string $key) => isset($shown[$key]) && $key !== 'han' && $value === '0', ARRAY_FILTER_USE_BOTH));

    expect($zeros)->not->toBeEmpty();

    foreach ($zeros as $key) {
        $answers[$key] = '';
    }

    $component = Livewire::test(Training::class, ['seed' => 'tenpai'])
        ->call('reveal')->call('next')
        ->call('reveal')->call('next')
        ->set('answers.dora', $answers)
        ->call('check')
        ->assertSet('results.2.ok', true);

    foreach ($zeros as $key) {
        $component->assertSet("answers.dora.{$key}", 0);
    }

    Livewire::test(Training::class, ['seed' => 'tenpai'])
        ->call('reveal')->call('next')
        ->call('reveal')->call('next')
        ->set('answers.dora', [...$answers, 'han' => ''])
        ->call('check')
        ->assertSet('results.2.fields.han.state', 'ko');
});
