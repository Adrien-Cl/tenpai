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
        return new self(self::randomPart());
    }

    /**
     * Une graine d'entraînement ciblé : « chinitsu-ab12cd34 », « expert-chinitsu-ab12cd34 ».
     * Le niveau et le thème font partie de la graine : l'adresse redonne donc la même main.
     */
    public static function themed(Theme $theme, Level $level = Level::Standard): self
    {
        $prefix = ($level === Level::Standard ? '' : $level->value.'-')
            .($theme === Theme::Free ? '' : $theme->value.'-');

        return new self($prefix.self::randomPart());
    }

    /**
     * Le thème imposé par la graine, s'il y en a un.
     */
    public function theme(): ?Theme
    {
        $theme = Theme::tryFrom($this->prefixes()['theme'] ?? '');

        return $theme === Theme::Free ? null : $theme;
    }

    /**
     * Le niveau de la graine : normal sans préfixe de niveau.
     */
    public function level(): Level
    {
        return $this->prefixes()['level'] ?? Level::Standard;
    }

    /**
     * Les préfixes d'une graine tirée par le site : niveau, puis thème, puis huit caractères.
     *
     * @return array{level?: Level, theme?: string}
     */
    private function prefixes(): array
    {
        if (preg_match('/^(?:([a-z]+)-)?(?:([a-z]+)-)?[a-z0-9]{8}$/', $this->value, $matches) !== 1) {
            return [];
        }

        $words = array_values(array_filter([$matches[1] ?? '', $matches[2] ?? ''], fn (string $word): bool => $word !== ''));
        $level = Level::tryFrom($words[0] ?? '');
        $prefixes = [];

        if ($level !== null && $level !== Level::Standard) {
            $prefixes['level'] = $level;
            array_shift($words);
        }

        if (count($words) === 1) {
            $prefixes['theme'] = $words[0];
        }

        return $prefixes;
    }

    private static function randomPart(): string
    {
        $value = '';

        for ($i = 0; $i < 8; $i++) {
            $value .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $value;
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
