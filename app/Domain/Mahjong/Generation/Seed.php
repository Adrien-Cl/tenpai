<?php

namespace App\Domain\Mahjong\Generation;

use DateTimeInterface;
use InvalidArgumentException;

/**
 * La graine d'une main : la même graine donne toujours la même main.
 * Elle apparaît dans l'URL, d'où un alphabet court et lisible.
 */
final readonly class Seed
{
    /** Sans 0/o, 1/l/i : une graine se recopie sans ambiguïté. */
    private const string ALPHABET = '23456789abcdefghjkmnpqrstuvwxyz';

    private const string PATTERN = '/^[a-z0-9][a-z0-9-]{0,39}$/';

    private function __construct(public string $value) {}

    /**
     * @throws InvalidArgumentException si la graine contient autre chose que [a-z0-9-] ou dépasse 40 caractères
     */
    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        if (! self::isValid($normalized)) {
            throw new InvalidArgumentException("Graine invalide : « {$value} ».");
        }

        return new self($normalized);
    }

    public static function isValid(string $value): bool
    {
        return preg_match(self::PATTERN, strtolower(trim($value))) === 1;
    }

    public static function random(): self
    {
        $value = '';

        for ($i = 0; $i < 8; $i++) {
            $value .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return new self($value);
    }

    /**
     * La graine du défi du jour : la même main pour tout le monde à cette date.
     */
    public static function daily(DateTimeInterface $date): self
    {
        return new self('defi-'.$date->format('Y-m-d'));
    }

    /**
     * La date du défi, si la graine en est un.
     */
    public function dailyDate(): ?string
    {
        return preg_match('/^defi-(\d{4}-\d{2}-\d{2})$/', $this->value, $matches) === 1 ? $matches[1] : null;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
