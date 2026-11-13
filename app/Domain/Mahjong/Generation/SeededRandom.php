<?php

namespace App\Domain\Mahjong\Generation;

use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Hasard reproductible : tout le tirage d'une main passe par ici.
 */
final class SeededRandom
{
    private Randomizer $randomizer;

    public function __construct(Seed $seed)
    {
        $this->randomizer = new Randomizer(new Xoshiro256StarStar(hash('sha256', $seed->value, true)));
    }

    /**
     * Entier de 0 à $max - 1.
     */
    public function int(int $max): int
    {
        return $this->randomizer->getInt(0, $max - 1);
    }

    public function float(): float
    {
        return $this->randomizer->nextFloat();
    }

    public function chance(float $probability): bool
    {
        return $this->float() < $probability;
    }

    /**
     * @template T
     *
     * @param  array<T>  $items
     * @return T
     */
    public function pick(array $items): mixed
    {
        $items = array_values($items);

        return $items[$this->int(count($items))];
    }

    /**
     * @template T
     *
     * @param  array<T>  $items
     * @return list<T>
     */
    public function shuffle(array $items): array
    {
        return $this->randomizer->shuffleArray(array_values($items));
    }
}
