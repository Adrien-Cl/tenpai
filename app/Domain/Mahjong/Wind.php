<?php

namespace App\Domain\Mahjong;

enum Wind: int
{
    case East = 0;
    case South = 1;
    case West = 2;
    case North = 3;

    public function tile(): int
    {
        return Tile::EAST + $this->value;
    }

    public function label(): string
    {
        return Text::get(match ($this) {
            self::East => 'Est',
            self::South => 'Sud',
            self::West => 'Ouest',
            self::North => 'Nord',
        });
    }

    public function kanji(): string
    {
        return match ($this) {
            self::East => '東',
            self::South => '南',
            self::West => '西',
            self::North => '北',
        };
    }
}
