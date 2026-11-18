<?php

namespace App\Livewire;

use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Generation\Theme;
use App\Support\Locale;
use App\Support\TableRules;
use App\Training\Drill;
use App\Training\ReadingsHand;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

/**
 * Exercice des lectures : une main qui se découpe de plusieurs façons. Pour chaque découpage,
 * le joueur donne les han et les fu, puis désigne celui qui compte.
 */
class Readings extends Component
{
    #[Locked]
    public string $seed;

    /**
     * Règles de la table, lues dans le cookie à l'ouverture.
     *
     * @var array{aka: bool, kiriage: bool, doubleWind4: bool, kazoe: bool, honba: bool}
     */
    #[Locked]
    public array $rules;

    /** @var array<string, mixed> han0, fu0, han1, fu1…, choice */
    public array $answers = [];

    /** @var array{ok: bool, revealed: bool, summary: string, fields: array<string, array{state: string, expected: string, why: string|null}>}|null */
    #[Locked]
    public ?array $result = null;

    #[Locked]
    public string $locale = Locale::DEFAULT;

    public function mount(string $seed): void
    {
        abort_unless(Seed::isValid($seed), 404);

        $this->locale = app()->getLocale();
        $this->seed = Seed::fromString($seed)->value;
        $this->rules = TableRules::toArray(TableRules::fromRequest(request()));

        try {
            $this->hand;
        } catch (RuntimeException) {
            abort(404);
        }
    }

    public function hydrate(): void
    {
        Locale::apply($this->locale);
    }

    #[Computed]
    public function hand(): ReadingsHand
    {
        return ReadingsHand::fromSeed(Seed::fromString($this->seed), TableRules::fromArray($this->rules));
    }

    public function check(): void
    {
        $this->correct(reveal: false);
    }

    public function reveal(): void
    {
        $this->correct(reveal: true);
    }

    public function nextHand(): void
    {
        $seed = Seed::themed(Theme::Free, Seed::fromString($this->seed)->level());

        $this->redirect(Drill::Readings->url($seed), navigate: true);
    }

    private function correct(bool $reveal): void
    {
        if ($this->result !== null) {
            return;
        }

        $checked = $this->hand->check($this->answers, $reveal);

        $this->answers = $checked['answers'];
        $this->result = $checked['result'];

        $this->dispatch('drill-finished', seed: $this->seed, drill: Drill::Readings->value, perfect: $this->result['ok']);
        $this->dispatch('answer-checked', ok: $this->result['ok'], revealed: $this->result['revealed'], perfect: $this->result['ok']);
    }

    public function render()
    {
        return view('livewire.readings')
            ->title(__('Exercice : :drill', ['drill' => Drill::Readings->label()]))
            ->layoutData([
                'description' => __('Une main de riichi qui se lit de plusieurs façons : comptez chaque découpage et gardez le bon.'),
                'robots' => 'noindex, follow',
            ]);
    }
}
