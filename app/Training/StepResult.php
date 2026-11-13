<?php

namespace App\Training;

/**
 * Correction d'une étape. Stockée en tableau dans l'état du composant Livewire.
 */
final class StepResult
{
    /** @var array<string, array{state: string, expected: string, why: string|null}> */
    private array $fields = [];

    public function __construct(
        private readonly bool $revealed,
    ) {}

    /**
     * Corrige un champ et renvoie s'il est juste.
     */
    public function mark(string $key, bool $correct, string $expected, ?string $why = null): bool
    {
        $state = match (true) {
            $this->revealed => FieldState::Revealed,
            $correct => FieldState::Ok,
            default => FieldState::Ko,
        };

        $this->fields[$key] = ['state' => $state->value, 'expected' => $expected, 'why' => $why];

        return $correct && ! $this->revealed;
    }

    public function set(string $key, FieldState $state, string $expected = '', ?string $why = null): void
    {
        $this->fields[$key] = ['state' => $state->value, 'expected' => $expected, 'why' => $why];
    }

    public function isRevealed(): bool
    {
        return $this->revealed;
    }

    /**
     * @return array{ok: bool, revealed: bool, summary: string, fields: array<string, array{state: string, expected: string, why: string|null}>}
     */
    public function toArray(bool $ok, string $summary): array
    {
        return [
            'ok' => $ok && ! $this->revealed,
            'revealed' => $this->revealed,
            'summary' => $summary,
            'fields' => $this->fields,
        ];
    }
}
