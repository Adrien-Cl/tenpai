<?php

namespace App\View\Tiles;

/**
 * Le sprite des tuiles, servi comme un fichier statique et versionné par son contenu.
 */
final class TileSprite
{
    private static ?string $content = null;

    public static function content(): string
    {
        return self::$content ??= TileArt::sprite();
    }

    public static function version(): string
    {
        return substr(md5(self::content()), 0, 10);
    }

    /**
     * URL du sprite, à placer devant « #identifiant » dans un <use href>.
     */
    public static function url(): string
    {
        static $url = null;

        return $url ??= route('tiles.sprite', ['v' => self::version()], absolute: false);
    }
}
