<?php

namespace App\Domain\Mahjong;

/**
 * Options de la table. Elles changent le calcul, jamais la main générée.
 */
final readonly class Rules
{
    public function __construct(
        public bool $aka = true,
        public bool $kiriage = false,
        public bool $doubleWind4 = true,
    ) {}

    /**
     * Riichi courant, style salle japonaise : cinq rouges, pas de kiriage, double vent à 4 fu.
     */
    public static function default(): self
    {
        return new self;
    }
}
