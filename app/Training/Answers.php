<?php

namespace App\Training;

/**
 * Lecture prudente des réponses envoyées par le navigateur.
 */
final class Answers
{
    /**
     * @param  array<string, mixed>  $answers
     */
    public static function int(array $answers, string $key): ?int
    {
        $value = $answers[$key] ?? null;

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^\s*\d{1,6}\s*$/', $value) === 1) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    public static function string(array $answers, string $key): ?string
    {
        $value = $answers[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
