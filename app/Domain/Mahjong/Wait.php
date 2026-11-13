<?php

namespace App\Domain\Mahjong;

/**
 * Forme de l'attente : la façon dont la tuile gagnante complète la main.
 */
enum Wait: string
{
    case Ryanmen = 'ryanmen';
    case Kanchan = 'kanchan';
    case Penchan = 'penchan';
    case Tanki = 'tanki';
    case Shanpon = 'shanpon';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function gloss(): string
    {
        return match ($this) {
            self::Ryanmen => 'deux côtés',
            self::Kanchan => 'au centre',
            self::Penchan => 'au bord',
            self::Tanki => 'sur la paire',
            self::Shanpon => 'deux paires',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Ryanmen => 'Attente ryanmen (sur deux côtés d’une suite) :',
            self::Kanchan => 'Attente kanchan (tuile du milieu d’une suite) :',
            self::Penchan => 'Attente penchan (3 pour 1-2, ou 7 pour 8-9) :',
            self::Tanki => 'Attente tanki (sur la paire) :',
            self::Shanpon => 'Attente shanpon (l’une de deux paires devient brelan), le brelan porte déjà ses fu :',
        };
    }

    /**
     * Fu d'attente : 2 pour une attente fermée (kanchan, penchan, tanki), 0 sinon.
     */
    public function fu(): int
    {
        return match ($this) {
            self::Kanchan, self::Penchan, self::Tanki => 2,
            self::Ryanmen, self::Shanpon => 0,
        };
    }
}
