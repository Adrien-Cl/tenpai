<?php

namespace App\Livewire;

use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Generation\Theme;
use App\Support\Locale;
use App\Training\Drill;
use App\Training\WaitsHand;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Exercice des attentes : la main juste avant la tuile gagnante. Le joueur désigne les tuiles
 * qui la complètent, puis dit s'il est furiten d'après ses défausses.
 *
 * Comme pour l'entraînement, la main est recalculée à partir de sa graine : les attentes
 * ne partent vers le navigateur qu'une fois la réponse corrigée.
 */
class Waits extends Component
{
    #[Locked]
    public string $seed;

    /** @var array{waits: list<string>, furiten: string|null} */
    public array $answers = ['waits' => [], 'furiten' => null];

    /** @var array{ok: bool, revealed: bool, summary: string, tiles: array<int, string>, furiten: array{state: string, expected: string, why: string}, notes: list<string>}|null */
    #[Locked]
    public ?array $result = null;

    #[Locked]
    public string $locale = Locale::DEFAULT;

    public function mount(string $seed): void
    {
        abort_unless(Seed::isValid($seed), 404);

        $this->locale = app()->getLocale();
        $this->seed = Seed::fromString($seed)->value;
    }

    public function hydrate(): void
    {
        Locale::apply($this->locale);
    }

    #[Computed]
    public function hand(): WaitsHand
    {
        return WaitsHand::fromSeed(Seed::fromString($this->seed));
    }

    public function theme(): ?Theme
    {
        return Seed::fromString($this->seed)->theme();
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
        $seed = Seed::themed($this->theme() ?? Theme::Free, Seed::fromString($this->seed)->level());

        $this->redirect(Drill::Waits->url($seed), navigate: true);
    }

    private function correct(bool $reveal): void
    {
        if ($this->result !== null) {
            return;
        }

        $checked = $this->hand->check($this->answers, $reveal);

        $this->answers = $checked['answers'];
        $this->result = $checked['result'];

        $this->dispatch('drill-finished', seed: $this->seed, drill: Drill::Waits->value, perfect: $this->result['ok']);
        $this->dispatch('answer-checked', ok: $this->result['ok'], revealed: $this->result['revealed'], perfect: $this->result['ok']);
    }

    public function render()
    {
        return view('livewire.waits')
            ->title(__('Exercice : :drill', ['drill' => Drill::Waits->label()]))
            ->layoutData([
                'description' => __('Une main de riichi en attente : trouvez toutes les tuiles qui la complètent, et dites si vous êtes furiten.'),
                'robots' => 'noindex, follow',
            ]);
    }
}
