<?php

namespace App\Livewire;

use App\Domain\Mahjong\Generation\Seed;
use App\Training\Step;
use App\Training\Steps\DoraStep;
use App\Training\Steps\FuStep;
use App\Training\Steps\HanStep;
use App\Training\Steps\PointsStep;
use App\Training\Steps\YakuStep;
use App\Training\TrainingHand;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Entraînement au comptage : une main, cinq étapes (yaku, han, dora, fu, points), puis le récapitulatif.
 *
 * La main n'est jamais stockée : elle est recalculée à partir de sa graine, côté serveur.
 * Les bonnes réponses ne partent donc vers le navigateur qu'une fois l'étape corrigée.
 */
class Training extends Component
{
    public const int RECAP = 5;

    #[Locked]
    public string $seed;

    /** Étape en cours (0 à 4), ou 5 quand la main est terminée. */
    #[Locked]
    public int $step = 0;

    /** Étape affichée : on peut relire une étape déjà faite. */
    #[Locked]
    public int $view = 0;

    /** @var array<string, array<string, mixed>> réponses saisies, par étape */
    public array $answers = [
        'yaku' => ['picked' => []],
        'han' => [],
        'dora' => [],
        'fu' => [],
        'points' => [],
    ];

    /** @var array<int, array{ok: bool, revealed: bool, summary: string, fields: array<string, array{state: string, expected: string, why: string|null}>}> */
    #[Locked]
    public array $results = [];

    public function mount(string $seed): void
    {
        abort_unless(Seed::isValid($seed), 404);

        $this->seed = Seed::fromString($seed)->value;
    }

    #[Computed]
    public function hand(): TrainingHand
    {
        return TrainingHand::fromSeed(Seed::fromString($this->seed));
    }

    /**
     * @return list<Step>
     */
    #[Computed]
    public function steps(): array
    {
        return [new YakuStep, new HanStep, new DoraStep, new FuStep, new PointsStep];
    }

    public function dailyDate(): ?string
    {
        return Seed::fromString($this->seed)->dailyDate();
    }

    public function isDone(): bool
    {
        return $this->step >= self::RECAP;
    }

    public function check(): void
    {
        $this->correct(reveal: false);
    }

    public function reveal(): void
    {
        $this->correct(reveal: true);
    }

    public function next(): void
    {
        if ($this->isDone() || ! isset($this->results[$this->step])) {
            return;
        }

        $this->step++;
        $this->view = $this->step;
    }

    public function show(int $index): void
    {
        if ($index >= 0 && $index <= $this->step && ($index < self::RECAP || $this->isDone())) {
            $this->view = $index;
        }
    }

    public function nextHand(): void
    {
        $this->redirectRoute('training.hand', ['seed' => Seed::random()->value], navigate: true);
    }

    private function correct(bool $reveal): void
    {
        if ($this->isDone() || $this->view !== $this->step || isset($this->results[$this->step])) {
            return;
        }

        $step = $this->steps[$this->step];
        $answers = is_array($this->answers[$step->key()] ?? null) ? $this->answers[$step->key()] : [];
        $checked = $step->check($this->hand, $answers, $reveal);

        $this->answers[$step->key()] = $checked['answers'];
        $this->results[$this->step] = $checked['result'];

        if ($this->step === self::RECAP - 1) {
            $this->dispatch(
                'hand-finished',
                seed: $this->seed,
                results: array_map(fn (array $result): bool => $result['ok'], $this->results),
                daily: $this->dailyDate(),
            );
        }
    }

    public function render()
    {
        return view('livewire.training')->title($this->dailyDate() !== null ? 'Défi du jour' : 'Entraînement');
    }
}
