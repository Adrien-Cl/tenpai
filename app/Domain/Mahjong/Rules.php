<?php

namespace App\Domain\Mahjong;

/**
 * Options de la table. Elles changent le calcul, jamais la main générée.
 */
final readonly class Rules
{
    /**
     * @param  bool  $kazoe  13 han et plus valent un yakuman (sinon, un sanbaiman)
     * @param  bool  $honba  les honba et les bâtons de riichi posés sur la table s'ajoutent au gain
     */
    public function __construct(
        public bool $aka = true,
        public bool $kiriage = false,
        public bool $doubleWind4 = true,
        public bool $kazoe = true,
        public bool $honba = false,
    ) {}

    /**
     * Riichi courant, style salle japonaise : cinq rouges, pas de kiriage, double vent à 4 fu,
     * yakuman compté, sans honba ni bâton de riichi.
     */
    public static function default(): self
    {
        return new self;
    }
}
