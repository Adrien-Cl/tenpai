<?php

use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Rules;
use App\Livewire\Training;
use App\Support\TableRules;
use App\Training\Steps\PointsStep;
use App\Training\TrainingHand;
use Livewire\Livewire;

/**
 * La valeur du cookie de règles, telle que le contrôleur l'écrit.
 */
function rulesCookie(bool $aka = true, bool $kiriage = false, bool $doubleWind4 = true, bool $kazoe = true, bool $honba = false): string
{
    return json_encode(['aka' => $aka, 'kiriage' => $kiriage, 'doubleWind4' => $doubleWind4, 'kazoe' => $kazoe, 'honba' => $honba]);
}

it('enregistre les règles cochées dans un cookie et revient sur la page', function () {
    $this->from('/bareme')
        ->post('/regles', ['aka' => '1', 'kiriage' => '1'])
        ->assertRedirect('/bareme')
        ->assertCookie(TableRules::COOKIE, rulesCookie(kiriage: true, doubleWind4: false, kazoe: false));
});

it('refuse une option qui n’est pas un booléen', function () {
    $this->from('/bareme')
        ->post('/regles', ['kiriage' => 'peut-être'])
        ->assertRedirect('/bareme')
        ->assertSessionHasErrors('kiriage')
        ->assertCookieMissing(TableRules::COOKIE);
});

it('calcule le barème avec les règles du cookie', function () {
    $this->withCookie(TableRules::COOKIE, rulesCookie(aka: false, kiriage: true))
        ->get('/bareme')
        ->assertOk()
        ->assertSee('Calculé avec : sans rouges, kiriage mangan, double vent à 4 fu, yakuman compté.')
        ->assertSee('Kiriage : 4 han 30 fu et 3 han 60 fu comptent comme mangan.')
        ->assertSee('Vos règles');
});

it('garde les règles par défaut sans cookie, ou avec un cookie illisible', function (?string $cookie) {
    $request = $cookie === null ? $this : $this->withCookie(TableRules::COOKIE, $cookie);

    $request->get('/bareme')
        ->assertOk()
        ->assertSee('Calculé avec : cinq rouges, sans kiriage, double vent à 4 fu, yakuman compté.')
        ->assertSee('Salle japonaise et en ligne : kuitan')
        ->assertDontSee('Kiriage : 4 han 30 fu');
})->with([
    'sans cookie' => [null],
    'cookie illisible' => ['pas du json'],
]);

it('propose le panneau des règles, cases cochées selon les règles en cours', function () {
    $this->withCookie(TableRules::COOKIE, rulesCookie(kiriage: true))
        ->get('/')
        ->assertOk()
        ->assertSee('Règles de la table')
        ->assertSee('action="'.route('rules').'"', false)
        ->assertSee('name="kiriage" value="1" class="mt-1 size-4 shrink-0 accent-shu" x-model="rules.kiriage" checked', false);
});

it('fige les règles du cookie à l’ouverture de la main', function () {
    $component = Livewire::withCookies([TableRules::COOKIE => rulesCookie(kiriage: true)])
        ->test(Training::class, ['seed' => 'tenpai'])
        ->assertSet('rules', ['aka' => true, 'kiriage' => true, 'doubleWind4' => true, 'kazoe' => true, 'honba' => false]);

    expect($component->instance()->hand->rules->kiriage)->toBeTrue();
});

it('ouvre les règles depuis l’en-tête, signalées quand elles sont personnalisées', function () {
    $this->get('/')
        ->assertSee('aria-label="Règles de la table"', false)
        ->assertDontSee('(règles personnalisées)');

    $this->withCookie(TableRules::COOKIE, rulesCookie(kiriage: true))
        ->get('/')
        ->assertSee('(règles personnalisées)');
});

it('reconnaît les préréglages, honba à part', function () {
    expect(TableRules::preset(new Rules))->toBe('japon')
        ->and(TableRules::preset(new Rules(aka: false, kiriage: true, doubleWind4: false, kazoe: false)))->toBe('tournoi')
        ->and(TableRules::preset(new Rules(aka: false, kiriage: true, doubleWind4: false, kazoe: false, honba: true)))->toBe('tournoi')
        ->and(TableRules::preset(new Rules(kiriage: true)))->toBeNull();
});

it('nomme les règles de tournoi et propose les préréglages dans le panneau', function () {
    $this->withCookie(TableRules::COOKIE, rulesCookie(aka: false, kiriage: true, doubleWind4: false, kazoe: false))
        ->get('/')
        ->assertOk()
        ->assertSee('Préréglages')
        ->assertSee('EMA, WRC')
        ->assertSee('Tournoi : kuitan, sans rouges, kiriage mangan, double vent à 2 fu, 13 han plafonnés au sanbaiman, sans honba ni bâton de riichi.');
});

it('garde un ancien cookie sans les nouvelles options', function () {
    $this->withCookie(TableRules::COOKIE, json_encode(['aka' => false, 'kiriage' => true, 'doubleWind4' => true]))
        ->get('/bareme')
        ->assertSee('Calculé avec : sans rouges, kiriage mangan, double vent à 4 fu, yakuman compté.');
});

it('arrête le barème au sanbaiman sans yakuman compté', function () {
    $limitCard = 'font-bold">Yakuman compté</span>';

    $this->get('/bareme')->assertSee($limitCard, false);

    $this->withCookie(TableRules::COOKIE, rulesCookie(kazoe: false))
        ->get('/bareme')
        ->assertSee('11 han et plus')
        ->assertDontSee($limitCard, false);
});

it('pose honba et bâtons de riichi sur le tapis et les ajoute aux points', function () {
    $seed = collect(range(0, 199))->map(fn (int $i) => sprintf('test%04d', $i))
        ->first(fn (string $seed) => TrainingHand::fromSeed(Seed::fromString($seed), new Rules(honba: true))->evaluation->payment()->hasBonus());
    $hand = TrainingHand::fromSeed(Seed::fromString($seed), new Rules(honba: true));
    $payment = $hand->evaluation->payment();

    $component = Livewire::withCookies([TableRules::COOKIE => rulesCookie(honba: true)])
        ->test(Training::class, ['seed' => $seed, 'drill' => 'points'])
        ->assertSee('data-table-bonus', false)
        ->assertSee('Gain total');

    $answers = ['limit' => $payment->limit?->value ?? 'none', 'gain' => (string) $payment->gain()];

    foreach (array_keys(PointsStep::fields($hand)) as $key) {
        $answers[$key] ??= (string) $payment->{$key};
    }

    $component->set('answers.points', $answers)
        ->call('check')
        ->assertSet('results.4.ok', true);

    Livewire::withCookies([TableRules::COOKIE => rulesCookie()])
        ->test(Training::class, ['seed' => $seed])
        ->assertDontSee('data-table-bonus', false);
});
