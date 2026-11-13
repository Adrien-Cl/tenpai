<?php

use App\Domain\Mahjong\Generation\Seed;
use App\Livewire\Training;
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
