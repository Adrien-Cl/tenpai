<?php

namespace App\Training;

/**
 * Une des cinq étapes du comptage.
 */
interface Step
{
    /** Clé de l'étape : yaku, han, dora, fu, points. */
    public function key(): string;

    /** Nom court, pour la fiche de score. */
    public function label(): string;

    public function title(): string;

    public function help(): string;

    /** Onglet du barème utile à cette étape. */
    public function scoringTab(): string;

    /**
     * Corrige les réponses. En mode « Voir la réponse », renvoie aussi les réponses attendues.
     *
     * @param  array<string, mixed>  $answers
     * @return array{result: array{ok: bool, revealed: bool, summary: string, fields: array<string, array{state: string, expected: string, why: string|null}>}, answers: array<string, mixed>}
     */
    public function check(TrainingHand $hand, array $answers, bool $reveal): array;
}
