<?php

use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Scoring\Evaluator;
use App\Livewire\Readings;
use App\Training\ReadingsHand;
use Livewire\Livewire;

/**
 * Les bonnes réponses de l'exercice, telles qu'un joueur les taperait.
 *
 * @return array<string, string>
 */
function readingsAnswers(string $seed): array
{
    $hand = ReadingsHand::fromSeed(Seed::fromString($seed), Rules::default());
    $answers = ['choice' => (string) $hand->bestIndexes()[0]];

    foreach ($hand->readings as $index => $reading) {
        $answers["han{$index}"] = (string) $reading->han;
        $answers["fu{$index}"] = (string) $reading->fu();
    }

    return $answers;
}

it('tire une main qui se lit de plusieurs façons, la meilleure payant plus', function () {
    for ($i = 0; $i < 15; $i++) {
        $hand = ReadingsHand::fromSeed(Seed::fromString(sprintf('test%04d', $i)), Rules::default());
        $totals = array_map(fn ($reading) => $reading->payment->total, $hand->readings);

        expect(count($hand->readings))->toBeBetween(2, ReadingsHand::MAX)
            ->and($hand->bestIndexes())->toHaveCount(1)
            ->and(max($totals))->toBe(Evaluator::evaluate($hand->training->hand(), Rules::default())->payment()->total);
    }
});

it('affiche chaque lecture avec ses groupes', function () {
    $hand = ReadingsHand::fromSeed(Seed::fromString('tenpai'), Rules::default());

    $this->get('/exercice/lectures/tenpai')
        ->assertOk()
        ->assertSee('Quel découpage compte ?')
        ->assertSee('Lecture A')
        ->assertSee('Lecture B')
        ->assertSee('aria-label="Tuile gagnante"', false)
        ->assertSee('data-reading="'.(count($hand->readings) - 1).'"', false);
});

it('valide les han, les fu et la lecture qui compte', function () {
    Livewire::test(Readings::class, ['seed' => 'tenpai'])
        ->set('answers', readingsAnswers('tenpai'))
        ->call('check')
        ->assertSet('result.ok', true)
        ->assertSet('result.fields.choice.state', 'ok')
        ->assertDispatched('drill-finished', seed: 'tenpai', drill: 'lectures', perfect: true)
        ->assertDispatched('answer-checked', ok: true, revealed: false, perfect: true);
});

it('compte faux le choix d’une lecture moins payante', function () {
    $answers = readingsAnswers('tenpai');
    $answers['choice'] = $answers['choice'] === '0' ? '1' : '0';

    Livewire::test(Readings::class, ['seed' => 'tenpai'])
        ->set('answers', $answers)
        ->call('check')
        ->assertSet('result.ok', false)
        ->assertSet('result.fields.choice.state', 'ko')
        ->assertDispatched('answer-checked', ok: false, revealed: false)
        ->assertSet('result.fields.han0.state', 'ok')
        ->assertSee('On garde toujours la lecture la plus payante');
});

it('signale des han faux et donne le détail des yaku', function () {
    $answers = readingsAnswers('tenpai');
    $answers['han0'] = (string) ((int) $answers['han0'] + 1);

    Livewire::test(Readings::class, ['seed' => 'tenpai'])
        ->set('answers', $answers)
        ->call('check')
        ->assertSet('result.ok', false)
        ->assertSet('result.fields.han0.state', 'ko')
        ->assertSet('result.fields.han0.expected', readingsAnswers('tenpai')['han0']);
});

it('montre la réponse sans compter l’exercice comme réussi', function () {
    Livewire::test(Readings::class, ['seed' => 'tenpai'])
        ->call('reveal')
        ->assertSet('result.ok', false)
        ->assertSet('result.revealed', true)
        ->assertDispatched('answer-checked', ok: false, revealed: true)
        ->assertSet('answers.choice', readingsAnswers('tenpai')['choice'])
        ->assertSee('Paie ');
});

it('passe à une main suivante du même niveau, sans thème', function () {
    Livewire::test(Readings::class, ['seed' => 'expert-test0001'])
        ->call('nextHand')
        ->assertRedirectContains('/exercice/lectures/expert-');
});

it('ouvre l’exercice depuis l’entraînement en laissant le thème de côté', function () {
    $location = $this->get('/comptage?exercice=lectures&theme=chinitsu')->assertRedirect()->headers->get('Location');

    expect($location)->toMatch('#/exercice/lectures/[a-z0-9]{8}$#');
});

it('sert l’exercice en anglais sous /en', function () {
    $this->get('/en/drill/readings/tenpai')
        ->assertOk()
        ->assertSee('Which split counts?')
        ->assertSee('Reading A');
});
