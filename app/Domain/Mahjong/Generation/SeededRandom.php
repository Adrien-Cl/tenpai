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

    /**
     * @param  string  $stream  un flux à part, tiré de la même graine : ce qu'on y tire ne change pas la main
     */
    public function __construct(Seed $seed, string $stream = '')
    {
        $key = $stream === '' ? $seed->value : $seed->value."\0".$stream;

        $this->randomizer = new Randomizer(new Xoshiro256StarStar(hash('sha256', $key, true)));
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
