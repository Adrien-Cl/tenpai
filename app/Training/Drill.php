<?php

namespace App\Training;

use App\Domain\Mahjong\Generation\Seed;

/**
 * Les exercices courts. Les deux premiers reprennent la main complète à une étape donnée ;
 * les lectures sont un exercice de comptage à part. Les attentes ont leur propre page,
 * hors de l'entraînement au comptage.
 */
enum Drill: string
{
    case Fu = 'fu';
    case Points = 'points';
    case Waits = 'attentes';
    case Readings = 'lectures';

    public function label(): string
    {
        return match ($this) {
            self::Fu => __('Fu et points'),
            self::Points => __('Points seulement'),
            self::Waits => __('Attentes'),
            self::Readings => __('Lectures'),
        };
    }

    /**
     * Ce que demande l'exercice, en quelques mots : « 2 étapes », « quel découpage compte ».
     */
    public function hint(): string
    {
        return match ($this) {
            self::Fu, self::Points => trans_choice(':count étape|:count étapes', 5 - (int) $this->firstStep()),
            self::Waits => __('les tuiles qui complètent la main'),
            self::Readings => __('quel découpage compte'),
        };
    }

    /**
     * L'étape de la main complète où commence l'exercice ; null pour un exercice à part.
     */
    public function firstStep(): ?int
    {
        return match ($this) {
            self::Fu => 3,
            self::Points => 4,
            self::Waits, self::Readings => null,
        };
    }

    /**
     * Le thème oriente-t-il la main ? Les lectures cherchent elles-mêmes une main ambiguë.
     */
    public function usesTheme(): bool
    {
        return $this !== self::Readings;
    }

    /**
     * L'adresse d'une main de l'exercice, dans la langue de la page.
     */
    public function url(Seed|string $seed): string
    {
        $seed = (string) $seed;

        return match ($this) {
            self::Fu, self::Points => localized_route('drill.hand', ['drill' => $this->value, 'seed' => $seed]),
            self::Waits => localized_route('waits.hand', ['seed' => $seed]),
            self::Readings => localized_route('readings.hand', ['seed' => $seed]),
        };
    }

    /**
     * Les exercices qui reprennent la main complète.
     *
     * @return list<self>
     */
    public static function steps(): array
    {
        return [self::Fu, self::Points];
    }

    /**
     * Les exercices de la page de comptage, après la main complète.
     *
     * @return list<self>
     */
    public static function counting(): array
    {
        return [self::Fu, self::Points, self::Readings];
    }
}
