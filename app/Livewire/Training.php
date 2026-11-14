<?php

namespace App\Livewire;

use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Generation\Theme;
use App\Support\Locale;
use App\Support\TableRules;
use App\Training\FieldState;
use App\Training\Step;
use App\Training\Steps\DoraStep;
use App\Training\Steps\FuStep;
use App\Training\Steps\HanStep;
use App\Training\Steps\PointsStep;
use App\Training\Steps\YakuStep;
use App\Training\TrainingHand;
use Illuminate\Support\Carbon;
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

    /**
     * Exercices courts : la main commence à cette étape, les précédentes sont données.
     *
     * @var array<string, array{first: int, label: string}>
     */
    public const array DRILLS = [
        'fu' => ['first' => 3, 'label' => 'Fu et points'],
        'points' => ['first' => 4, 'label' => 'Points seulement'],
    ];

    /** Exercice court en cours (clé de DRILLS), ou null pour une main complète. */
    #[Locked]
    public ?string $drill = null;

    #[Locked]
    public string $seed;

    /**
     * Règles de la table, lues dans le cookie à l'ouverture : elles ne changent pas en cours de main.
     *
     * @var array{aka: bool, kiriage: bool, doubleWind4: bool}
     */
    #[Locked]
    public array $rules;

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

    /** @var array<int, array{ok: bool, revealed: bool, given?: bool, summary: string, fields: array<string, array{state: string, expected: string, why: string|null}>}> */
    #[Locked]
    public array $results = [];

    /**
     * Langue de la page, prise dans l'adresse à l'ouverture. Les requêtes Livewire suivantes
     * passent par /livewire/update, sans langue : on la remet en place à chaque fois.
     */
    #[Locked]
    public string $locale = Locale::DEFAULT;

    public function mount(string $seed, ?string $drill = null): void
    {
        abort_unless(Seed::isValid($seed), 404);
        abort_unless($drill === null || isset(self::DRILLS[$drill]), 404);

        $this->locale = app()->getLocale();
        $this->seed = Seed::fromString($seed)->value;
        $this->rules = TableRules::toArray(TableRules::fromRequest(request()));
        $this->drill = $drill;
        $this->giveStepsBefore($this->firstStep());
    }

    public function hydrate(): void
    {
        Locale::apply($this->locale);
    }

    /**
     * Première étape à jouer : 0 pour une main complète, plus loin pour un exercice court.
     */
    public function firstStep(): int
    {
        return $this->drill !== null ? self::DRILLS[$this->drill]['first'] : 0;
    }

    /**
     * Nombre d'étapes jouées, celles qui comptent dans le score.
     */
    public function playedSteps(): int
    {
        return self::RECAP - $this->firstStep();
    }

    public function drillLabel(): ?string
    {
        return $this->drill !== null ? __(self::DRILLS[$this->drill]['label']) : null;
    }

    #[Computed]
    public function hand(): TrainingHand
    {
        return TrainingHand::fromSeed(Seed::fromString($this->seed), TableRules::fromArray($this->rules));
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

    /**
     * Le thème de l'entraînement ciblé, ou null pour une main libre.
     */
    public function theme(): ?Theme
    {
        return Seed::fromString($this->seed)->theme();
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
        $seed = Seed::themed($this->theme() ?? Theme::Free)->value;

        $this->drill !== null
            ? $this->redirect(localized_route('drill.hand', ['drill' => $this->drill, 'seed' => $seed]), navigate: true)
            : $this->redirect(localized_route('training.hand', ['seed' => $seed]), navigate: true);
    }

    /**
     * Les yaku oubliés à l'étape 1 (manqués, ou dont on a demandé la réponse), pour les progrès.
     * Rien quand l'étape a été donnée par un exercice court.
     *
     * @return list<string>
     */
    private function missedYaku(): array
    {
        $result = $this->results[0] ?? null;

        if ($result === null || ($result['given'] ?? false)) {
            return [];
        }

        $missed = array_filter(
            $result['fields'],
            fn (array $field): bool => in_array($field['state'], [FieldState::Missed->value, FieldState::Revealed->value], true),
        );

        return array_keys($missed);
    }

    /**
     * Remplit les étapes avant la première jouée avec leurs réponses, sans les compter.
     */
    private function giveStepsBefore(int $first): void
    {
        for ($index = 0; $index < $first; $index++) {
            $step = $this->steps[$index];
            $checked = $step->check($this->hand, $this->answers[$step->key()], reveal: true);

            $this->answers[$step->key()] = $checked['answers'];
            $this->results[$index] = [...$checked['result'], 'given' => true];
        }

        $this->step = $first;
        $this->view = $first;
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
                drill: $this->drill,
                first: $this->firstStep(),
                missed: $this->missedYaku(),
            );
        }
    }

    public function render()
    {
        $title = match (true) {
            $this->dailyDate() !== null => __('Défi du jour'),
            $this->drill !== null => __('Exercice : :drill', ['drill' => $this->drillLabel()]),
            $this->theme() !== null => __('Entraînement : :theme', ['theme' => $this->theme()->label()]),
            default => __('Entraînement'),
        };

        return view('livewire.training')->title($title)->layoutData([
            'description' => $this->description(),
            'image' => localized_route('preview', ['seed' => $this->seed]),
            'imageAlt' => __('Les tuiles de la main à compter, posées sur le tapis.'),
        ]);
    }

    /**
     * La description de la page, reprise par l'aperçu des liens partagés. Elle ne dévoile rien de la main.
     */
    private function description(): string
    {
        $daily = $this->dailyDate();
        $theme = $this->theme();
        $steps = match ($this->drill) {
            'fu' => __('à partir des fu'),
            'points' => __('à partir des points'),
            default => __('en cinq étapes'),
        };

        return match (true) {
            $daily !== null => __('Le défi du :date : la même main de riichi pour tout le monde. Saurez-vous la compter ?', ['date' => Carbon::parse($daily)->translatedFormat(__('j F'))]),
            $theme !== null => __('Une main avec :theme (:gloss) à compter :steps. Le lien redonne toujours la même main.', ['theme' => $theme->label(), 'gloss' => $theme->gloss(), 'steps' => $steps]),
            default => __('Une main de riichi à compter :steps. Le lien redonne toujours la même main.', ['steps' => $steps]),
        };
    }
}
