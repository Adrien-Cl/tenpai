<?php

use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Tile;
use App\Livewire\Waits;
use App\Training\WaitsHand;
use Livewire\Livewire;

/**
 * Une graine dont la main est (ou n'est pas) furiten : on la cherche parmi des graines fixes.
 */
function waitsSeed(bool $furiten): string
{
    for ($i = 0; $i < 200; $i++) {
        $seed = sprintf('test%04d', $i);

        if (WaitsHand::fromSeed(Seed::fromString($seed))->isFuriten() === $furiten) {
            return $seed;
        }
    }

    throw new RuntimeException('Aucune graine trouvée.');
}

/**
 * @return array{waits: list<string>, furiten: string}
 */
function waitsAnswers(string $seed): array
{
    $hand = WaitsHand::fromSeed(Seed::fromString($seed));

    return ['waits' => array_map(strval(...), $hand->waits), 'furiten' => $hand->isFuriten() ? 'oui' : 'non'];
}

it('affiche la main en attente, sans la tuile gagnante', function () {
    $hand = WaitsHand::fromSeed(Seed::fromString('tenpai'));

    $html = $this->get('/attentes/tenpai')
        ->assertOk()
        ->assertSee('Quelles tuiles complètent la main ?')
        ->assertSee('Vos défausses')
        ->assertDontSee('aria-label="Tuile gagnante"', false)
        ->getContent();

    preg_match('/data-waiting-hand[^>]*>(.*?)<\/span>\s*<span class="mt-3/s', $html, $handTiles);

    expect(substr_count($handTiles[1], 'class="tile'))->toBe(count($hand->closed))
        ->and(count($hand->closed) + 3 * count($hand->hand()->melds))->toBe(13);
});

it('valide les bonnes attentes et le bon furiten', function (bool $furiten) {
    $seed = waitsSeed($furiten);

    Livewire::test(Waits::class, ['seed' => $seed])
        ->set('answers', waitsAnswers($seed))
        ->call('check')
        ->assertSet('result.ok', true)
        ->assertSet('result.furiten.state', 'ok')
        ->assertDispatched('drill-finished', seed: $seed, drill: 'attentes', perfect: true)
        ->assertDispatched('answer-checked', ok: true, revealed: false, perfect: true);
})->with([
    'furiten' => [true],
    'pas furiten' => [false],
]);

it('signale une attente oubliée et une tuile en trop', function () {
    $hand = WaitsHand::fromSeed(Seed::fromString('tenpai'));
    $extra = (int) collect(range(0, Tile::COUNT - 1))->first(fn (int $tile) => ! in_array($tile, $hand->waits, true));
    $picked = [...array_slice($hand->waits, 1), $extra];

    $component = Livewire::test(Waits::class, ['seed' => 'tenpai'])
        ->set('answers', ['waits' => array_map(strval(...), $picked), 'furiten' => $hand->isFuriten() ? 'oui' : 'non'])
        ->call('check')
        ->assertSet('result.ok', false)
        ->assertDispatched('drill-finished', perfect: false)
        ->assertDispatched('answer-checked', ok: false, revealed: false, perfect: false);

    expect($component->get('result.tiles'))
        ->toHaveKey($hand->waits[0], 'missed')
        ->toHaveKey($extra, 'extra');
});

it('compte faux un furiten mal jugé, même avec les bonnes attentes', function () {
    $seed = waitsSeed(furiten: true);

    Livewire::test(Waits::class, ['seed' => $seed])
        ->set('answers', [...waitsAnswers($seed), 'furiten' => 'non'])
        ->call('check')
        ->assertSet('result.ok', false)
        ->assertSet('result.furiten.state', 'ko')
        ->assertSee('une de vos attentes, est dans vos défausses');
});

it('montre la réponse sans compter l’exercice comme réussi', function () {
    Livewire::test(Waits::class, ['seed' => 'tenpai'])
        ->call('reveal')
        ->assertSet('result.ok', false)
        ->assertSet('result.revealed', true)
        ->assertDispatched('answer-checked', ok: false, revealed: true)
        ->assertSet('answers', waitsAnswers('tenpai'))
        ->assertSee('Attentes : ');
});

it('ne corrige pas deux fois et garde la correction hors de portée du navigateur', function () {
    $component = Livewire::test(Waits::class, ['seed' => 'tenpai'])
        ->call('reveal')
        ->set('answers', waitsAnswers('tenpai'))
        ->call('check')
        ->assertSet('result.revealed', true);

    expect(fn () => $component->set('result', null))->toThrow(Exception::class);
});

it('ignore les tuiles invalides envoyées par le navigateur', function () {
    expect(WaitsHand::pickedTiles(['3', '3', '34', '-1', 'x', 5, ['1']]))->toBe([3, 5])
        ->and(WaitsHand::pickedTiles('3'))->toBe([]);
});

it('passe à une main suivante du même niveau et du même thème', function () {
    Livewire::test(Waits::class, ['seed' => 'expert-chinitsu-test0001'])
        ->call('nextHand')
        ->assertRedirectContains('/attentes/expert-chinitsu-');
});

it('propose de changer de main tant que la main n’est pas corrigée', function () {
    Livewire::test(Waits::class, ['seed' => 'tenpai'])
        ->assertSeeHtml('data-reroll')
        ->call('reveal')
        ->assertDontSeeHtml('data-reroll');
});

it('tire une main depuis sa propre page, avec thème et niveau', function () {
    $location = $this->get('/attentes?theme=chinitsu&niveau=expert')->assertRedirect()->headers->get('Location');

    expect($location)->toMatch('#/attentes/expert-chinitsu-[a-z0-9]{8}$#')
        ->and($this->get('/attentes?niveau=inconnu')->headers->get('Location'))->toMatch('#/attentes/[a-z0-9]{8}$#')
        ->and($this->get('/comptage?exercice=attentes')->headers->get('Location'))->toMatch('#/attentes/[a-z0-9]{8}$#');
});

it('a sa propre entrée dans le menu, et ne propose que niveau et thème', function () {
    $response = $this->get('/attentes/tenpai');

    expect(currentLinks($response->getContent()))->toBe(['Attentes', 'Normal', 'Main libre']);

    $response
        ->assertSee('href="'.route('waits').'"', false)
        ->assertSee('href="'.route('waits', ['niveau' => 'expert']).'"', false)
        ->assertSee('href="'.route('waits', ['theme' => 'chinitsu']).'"', false)
        ->assertDontSee('quel découpage compte');
});

it('sert l’exercice en anglais sous /en', function () {
    $this->get('/en/waits/tenpai')
        ->assertOk()
        ->assertSee('Which tiles complete the hand?')
        ->assertSee('Your discards');

    expect($this->get('/en/waits')->headers->get('Location'))->toMatch('#/en/waits/[a-z0-9]{8}$#');
});

it('refuse une graine invalide', function () {
    $this->get('/attentes/pas_valide')->assertNotFound();
});
