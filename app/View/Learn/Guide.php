<?php

namespace App\View\Learn;

use App\Support\Locale;

/**
 * Les guides de la partie « Apprendre », dans l'ordre de lecture conseillé à un débutant.
 * Chaque guide a sa route par langue (« learn.tiles », « en.learn.tiles »…), son titre et sa description.
 */
enum Guide: string
{
    case Tiles = 'tiles';
    case Game = 'game';
    case Scoring = 'scoring';
    case Glossary = 'glossary';

    /**
     * Le nom de la route, sans la langue.
     */
    public function routeName(): string
    {
        return "learn.{$this->value}";
    }

    /**
     * Le guide d'une route (« learn.tiles » ou « en.learn.tiles »), ou null pour l'accueil de la partie.
     */
    public static function fromRouteName(string $name): ?self
    {
        $base = Locale::baseRouteName($name);

        return str_starts_with($base, 'learn.') ? self::tryFrom(substr($base, strlen('learn.'))) : null;
    }

    /**
     * Le mot de l'adresse dans une langue : « les-tuiles », « tiles »…
     */
    public function slug(string $locale): string
    {
        return Locale::LOCALES[$locale]['guides'][$this->value];
    }

    public function view(): string
    {
        return "apprendre.{$this->value}";
    }

    /**
     * Le titre court : menus, cartes et fil d'Ariane.
     */
    public function label(): string
    {
        return match ($this) {
            self::Tiles => __('Les tuiles'),
            self::Game => __('Déroulement d’une partie'),
            self::Scoring => __('Calcul des points'),
            self::Glossary => __('Lexique'),
        };
    }

    /**
     * Le titre de la page, tel que les moteurs de recherche l'affichent.
     */
    public function title(): string
    {
        return match ($this) {
            self::Tiles => __('Les 136 tuiles du mahjong riichi'),
            self::Game => __('Comment se déroule une partie de mahjong riichi'),
            self::Scoring => __('Calculer les points au mahjong riichi : le guide débutant'),
            self::Glossary => __('Lexique du mahjong riichi : les mots de la table'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Tiles => __('Caractères, cercles, bambous, vents et dragons : les tuiles du mahjong japonais, les cinq rouges, les dora et la notation 123m.'),
            self::Game => __('Les règles du riichi pour débutant : le but du jeu, le mur, piocher et défausser, chii, pon et kan, riichi, tsumo et ron, les manches et la fin de partie.'),
            self::Scoring => __('Compter une main de riichi pas à pas : yaku, han, dora, fu, puis les points et les limites (mangan, haneman…), avec un exemple complet.'),
            self::Glossary => __('Tenpai, furiten, dora, oya, kanchan, hanchan… Le vocabulaire japonais du mahjong riichi expliqué simplement, de A à Z.'),
        };
    }

    /**
     * Ce qu'on y apprend, en une phrase (cartes de l'accueil et de la partie « Apprendre »).
     */
    public function summary(): string
    {
        return match ($this) {
            self::Tiles => __('Les trois familles, les honneurs, les cinq rouges et les dora.'),
            self::Game => __('Le but du jeu, un tour de table, les appels, le riichi et la fin d’une donne.'),
            self::Scoring => __('Yaku, han, dora, fu et points : la méthode, avec une main comptée de bout en bout.'),
            self::Glossary => __('Les mots japonais qu’on entend à table, expliqués en une phrase.'),
        };
    }

    /**
     * Le caractère qui illustre le guide (il doit exister dans le sprite des tuiles).
     */
    public function glyph(): string
    {
        return match ($this) {
            self::Tiles => '牌',
            self::Game => '雀',
            self::Scoring => '点',
            self::Glossary => '解',
        };
    }

    public function previous(): ?self
    {
        $cases = self::cases();
        $index = array_search($this, $cases, true);

        return $cases[$index - 1] ?? null;
    }

    public function next(): ?self
    {
        $cases = self::cases();
        $index = array_search($this, $cases, true);

        return $cases[$index + 1] ?? null;
    }
}
