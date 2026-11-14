<?php

namespace App\View;

use Illuminate\Http\Request;

/**
 * Le fond choisi avec le bouton de l'en-tête : clair (le jour) ou foncé (la nuit).
 * Sans choix, la page suit le réglage du système. Le cookie est écrit par le JavaScript, en clair.
 */
enum Backdrop: string
{
    case Light = 'light';
    case Dark = 'dark';

    public const string COOKIE = 'fond';

    public static function fromRequest(Request $request): ?self
    {
        return self::tryFrom((string) $request->cookie(self::COOKIE));
    }

    /**
     * Les couleurs de la barre du navigateur, pour le bouton qui change de fond.
     *
     * @return array{light: string, dark: string}
     */
    public static function themeColors(): array
    {
        return [self::Light->value => self::Light->themeColor(), self::Dark->value => self::Dark->themeColor()];
    }

    /**
     * La couleur de la barre du navigateur : celle de la pièce.
     */
    public function themeColor(): string
    {
        return match ($this) {
            self::Light => '#f4eddc',
            self::Dark => '#131922',
        };
    }
}
