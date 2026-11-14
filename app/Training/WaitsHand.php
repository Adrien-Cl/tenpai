<?php

namespace App\Training;

use App\Domain\Mahjong\Generation\GeneratedHand;
use App\Domain\Mahjong\Generation\HandGenerator;
use App\Domain\Mahjong\Generation\Seed;
use App\Domain\Mahjong\Generation\SeededRandom;
use App\Domain\Mahjong\Hand\Hand;
use App\Domain\Mahjong\Hand\WaitFinder;
use App\Domain\Mahjong\Rules;
use App\Domain\Mahjong\Scoring\EvaluationStatus;
use App\Domain\Mahjong\Scoring\Evaluator;
use App\Domain\Mahjong\Tile;
use App\Domain\Mahjong\Wait;

/**
 * L'exercice des attentes : la main de la graine, juste avant la tuile gagnante.
 * Quelles tuiles la complètent, et le joueur est-il furiten d'après ses défausses ?
 */
final readonly class WaitsHand
{
    /** Chance que les défausses contiennent une des attentes. */
    private const float FURITEN_CHANCE = 0.4;

    /**
     * @param  list<int>  $closed  les 13 tuiles cachées (moins 3 par appel), sans la tuile gagnante
     * @param  list<int>  $waits  les tuiles qui complètent la main
     * @param  list<int>  $exhausted  tuiles que la forme attend, mais dont les quatre exemplaires sont déjà dans la main
     * @param  list<int>  $discards  les défausses du joueur, dans l'ordre
     */
    public function __construct(
        public GeneratedHand $generated,
        public array $closed,
        public array $waits,
        public array $exhausted,
        public array $discards,
    ) {}

    public static function fromSeed(Seed $seed): self
    {
        $generated = (new HandGenerator)->generate($seed);
        $closed = array_map(fn ($instance): int => $instance->tile, $generated->handTiles);
        $melds = $generated->hand->melds;
        $waits = WaitFinder::waits($closed, $melds);

        return new self(
            $generated,
            $closed,
            $waits,
            WaitFinder::exhausted($closed, $melds),
            self::discards($seed, $generated, $closed, $waits),
        );
    }

    public function hand(): Hand
    {
        return $this->generated->hand;
    }

    /**
     * Les attentes déjà défaussées par le joueur : une seule suffit à être furiten.
     *
     * @return list<int>
     */
    public function furitenTiles(): array
    {
        return array_values(array_intersect($this->waits, $this->discards));
    }

    public function isFuriten(): bool
    {
        return $this->furitenTiles() !== [];
    }

    /**
     * Ce que donne une attente : la forme de l'attente, et si la victoire a un yaku en ron et en tsumo.
     *
     * @return array{wait: Wait|null, ron: bool, tsumo: bool}
     */
    public function outcome(int $tile): array
    {
        $ron = Evaluator::evaluate($this->completed($tile, tsumo: false), Rules::default());
        $tsumo = Evaluator::evaluate($this->completed($tile, tsumo: true), Rules::default());

        return [
            'wait' => $ron->isValid() ? $ron->wait() : ($tsumo->isValid() ? $tsumo->wait() : null),
            'ron' => $ron->status === EvaluationStatus::Valid || $ron->status === EvaluationStatus::Yakuman,
            'tsumo' => $tsumo->status === EvaluationStatus::Valid || $tsumo->status === EvaluationStatus::Yakuman,
        ];
    }

    /**
     * Corrige les réponses : les tuiles choisies, puis le furiten.
     *
     * @param  array<string, mixed>  $answers
     * @return array{result: array{ok: bool, revealed: bool, summary: string, tiles: array<int, string>, furiten: array{state: string, expected: string, why: string}, notes: list<string>}, answers: array<string, mixed>}
     */
    public function check(array $answers, bool $reveal): array
    {
        $picked = self::pickedTiles($answers['waits'] ?? []);
        $tiles = [];

        foreach ($this->waits as $tile) {
            $tiles[$tile] = match (true) {
                $reveal => FieldState::Revealed->value,
                in_array($tile, $picked, true) => FieldState::Ok->value,
                default => FieldState::Missed->value,
            };
        }

        foreach ($picked as $tile) {
            if (! $reveal && ! in_array($tile, $this->waits, true)) {
                $tiles[$tile] = FieldState::Extra->value;
            }
        }

        ksort($tiles);

        $furiten = $answers['furiten'] ?? null;
        $furitenRight = ($furiten === 'oui' && $this->isFuriten()) || ($furiten === 'non' && ! $this->isFuriten());
        $furitenState = match (true) {
            $reveal => FieldState::Revealed,
            $furitenRight => FieldState::Ok,
            default => FieldState::Ko,
        };

        $waitsRight = ! $reveal && $picked === $this->waits;
        $ok = $waitsRight && $furitenRight && ! $reveal;

        return [
            'result' => [
                'ok' => $ok,
                'revealed' => $reveal,
                'summary' => $this->summary(),
                'tiles' => $tiles,
                'furiten' => [
                    'state' => $furitenState->value,
                    'expected' => $this->isFuriten() ? __('Oui') : __('Non'),
                    'why' => $this->furitenWhy(),
                ],
                'notes' => $this->notes($picked),
            ],
            'answers' => $reveal
                ? ['waits' => array_map(strval(...), $this->waits), 'furiten' => $this->isFuriten() ? 'oui' : 'non']
                : ['waits' => array_map(strval(...), $picked), 'furiten' => $furiten],
        ];
    }

    /**
     * Les attentes en une ligne : « 3p, 6p (2 attentes) ».
     */
    public function summary(): string
    {
        $labels = implode(', ', array_map(fn (int $tile): string => Tile::label($tile), $this->waits));

        return trans_choice(':tiles (:count attente)|:tiles (:count attentes)', count($this->waits), ['tiles' => $labels]);
    }

    /**
     * Les tuiles choisies, valides et triées. Le navigateur envoie des chaînes.
     *
     * @return list<int>
     */
    public static function pickedTiles(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $tiles = [];

        foreach ($values as $value) {
            if ((is_int($value) || (is_string($value) && ctype_digit($value))) && (int) $value < Tile::COUNT) {
                $tiles[(int) $value] = true;
            }
        }

        $tiles = array_keys($tiles);
        sort($tiles);

        return $tiles;
    }

    private function furitenWhy(): string
    {
        $furiten = $this->furitenTiles();

        if ($furiten === []) {
            return __('Aucune de vos attentes n’est dans vos défausses : vous pouvez gagner en ron.');
        }

        return __(':tile, une de vos attentes, est dans vos défausses : pas de ron, sur aucune de vos attentes. Il reste le tsumo.', [
            'tile' => Tile::label($furiten[0]),
        ]);
    }

    /**
     * Les explications de la correction : la forme de chaque attente, celles sans yaku, les tuiles épuisées.
     *
     * @param  list<int>  $picked
     * @return list<string>
     */
    private function notes(array $picked): array
    {
        $notes = [];

        foreach ($this->waits as $tile) {
            $outcome = $this->outcome($tile);
            $shape = $outcome['wait'] !== null ? $outcome['wait']->label().' ('.$outcome['wait']->gloss().')' : null;

            $notes[] = match (true) {
                $outcome['ron'] => __(':tile : :shape.', ['tile' => Tile::label($tile), 'shape' => $shape]),
                $outcome['tsumo'] => __(':tile : la main est complète, mais sans yaku en ron. Elle ne gagne qu’en tsumo.', ['tile' => Tile::label($tile)]),
                default => __(':tile : la main est complète, mais sans yaku. Elle ne peut pas gagner sur cette tuile.', ['tile' => Tile::label($tile)]),
            };
        }

        foreach ($this->exhausted as $tile) {
            if (in_array($tile, $picked, true)) {
                $notes[] = __(':tile compléterait la forme, mais les quatre exemplaires sont déjà dans votre main : il ne peut plus arriver.', ['tile' => Tile::label($tile)]);
            }
        }

        return $notes;
    }

    /**
     * La main complétée par une tuile, avec sa situation de départ (vents, riichi, dora).
     * Les situations liées à la tuile gagnante (ippatsu, haitei, rinshan, chankan) sont écartées.
     */
    private function completed(int $tile, bool $tsumo): Hand
    {
        $hand = $this->hand();
        $closed = [...$this->closed, $tile];
        sort($closed);

        return new Hand(
            closed: $closed,
            melds: $hand->melds,
            win: $tile,
            tsumo: $tsumo,
            seat: $hand->seat,
            round: $hand->round,
            riichi: $hand->riichi,
            doubleRiichi: $hand->doubleRiichi,
            dora: $hand->dora,
        );
    }

    /**
     * Les défausses du joueur, tirées sur un flux à part : 6 à 12 tuiles encore disponibles.
     * Parfois, l'une d'elles est une attente (furiten) ; jamais plus d'une.
     *
     * @param  list<int>  $closed
     * @param  list<int>  $waits
     * @return list<int>
     */
    private static function discards(Seed $seed, GeneratedHand $generated, array $closed, array $waits): array
    {
        $random = new SeededRandom($seed, 'defausses');
        $seen = $closed;

        foreach ($generated->hand->melds as $meld) {
            array_push($seen, ...$meld->tiles());
        }

        array_push($seen, ...$generated->hand->dora);
        $left = array_map(fn (int $count): int => 4 - $count, Tile::counts($seen));

        $size = 6 + $random->int(7);
        $discards = [];

        for ($i = 0; $i < $size; $i++) {
            $tile = self::pickDiscard($random, $left, $waits);

            if ($tile === null) {
                break;
            }

            $discards[] = $tile;
            $left[$tile]--;
        }

        if ($random->chance(self::FURITEN_CHANCE)) {
            $available = array_values(array_filter($waits, fn (int $tile): bool => $left[$tile] > 0));

            if ($available !== [] && $discards !== []) {
                $discards[$random->int(count($discards))] = $random->pick($available);
            }
        }

        return $discards;
    }

    /**
     * Une défausse au hasard parmi les tuiles qui restent, sans les attentes.
     * Terminales et honneurs partent plus souvent, comme en début de partie.
     *
     * @param  array<int, int>  $left
     * @param  list<int>  $waits
     */
    private static function pickDiscard(SeededRandom $random, array $left, array $waits): ?int
    {
        $weights = [];

        for ($tile = 0; $tile < Tile::COUNT; $tile++) {
            if ($left[$tile] > 0 && ! in_array($tile, $waits, true)) {
                $weights[$tile] = Tile::isTerminalOrHonor($tile) ? 2 : 1;
            }
        }

        if ($weights === []) {
            return null;
        }

        $roll = $random->float() * array_sum($weights);

        foreach ($weights as $tile => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $tile;
            }
        }

        return array_key_last($weights);
    }
}
