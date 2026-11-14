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
     * Un champ laissé vide vaut 0 : on n'a pas à taper 0 pour chaque ligne qui ne rapporte rien.
     *
     * @param  array<string, mixed>  $answers
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    public static function emptyAsZero(array $answers, array $keys): array
    {
        foreach ($keys as $key) {
            $value = $answers[$key] ?? null;

            if ($value === null || (is_string($value) && trim($value) === '')) {
                $answers[$key] = 0;
            }
        }

        return $answers;
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
