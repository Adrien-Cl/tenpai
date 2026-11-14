<?php

namespace App\View\Tiles;

use App\Domain\Mahjong\Tile;

/**
 * Le dessin des tuiles, porté du tiles.js de la référence (mêmes coordonnées, mêmes encres),
 * dans un repère de 30 × 40. Les caractères sont des tracés (voir TileGlyphs), pas du texte.
 *
 * Tout est rassemblé dans un sprite SVG : chaque tuile de la page y fait référence avec <use>.
 */
final class TileArt
{
    public const string FACE = '#F6F1E3';

    public const string INK = '#1A1714';

    public const string RED = '#C0352A';

    public const string BLUE = '#22508F';

    public const string GREEN = '#24764A';

    public const string GOLD = '#C98A1B';

    private const string KANJI_NUMBERS = '一二三四五六七八九';

    private const array PIN = [
        2 => [[15, 10.5, 7], [15, 29.5, 7]],
        3 => [[8, 8, 6], [15, 20, 6], [22, 32, 6]],
        4 => [[8.5, 10.5, 6], [21.5, 10.5, 6], [8.5, 29.5, 6], [21.5, 29.5, 6]],
        5 => [[8, 8.5, 5.3], [22, 8.5, 5.3], [15, 20, 5.3], [8, 31.5, 5.3], [22, 31.5, 5.3]],
        6 => [[9.5, 7.5, 5], [20.5, 7.5, 5], [9.5, 21.5, 5], [20.5, 21.5, 5], [9.5, 32.5, 5], [20.5, 32.5, 5]],
        7 => [[7, 6, 4.2], [15, 10, 4.2], [23, 14, 4.2], [9.5, 24.5, 4.6], [20.5, 24.5, 4.6], [9.5, 34, 4.6], [20.5, 34, 4.6]],
        8 => [[9.5, 6, 4.4], [20.5, 6, 4.4], [9.5, 15.3, 4.4], [20.5, 15.3, 4.4], [9.5, 24.7, 4.4], [20.5, 24.7, 4.4], [9.5, 34, 4.4], [20.5, 34, 4.4]],
        9 => [[6.6, 7.5, 4.1], [15, 7.5, 4.1], [23.4, 7.5, 4.1], [6.6, 20, 4.1], [15, 20, 4.1], [23.4, 20, 4.1], [6.6, 32.5, 4.1], [15, 32.5, 4.1], [23.4, 32.5, 4.1]],
    ];

    private const array PIN_COLORS = [2 => 'gb', 3 => 'brg', 4 => 'bggb', 5 => 'bgrgb', 6 => 'ggrrrr', 7 => 'gggrrrr', 8 => 'bbbbbbbb', 9 => 'bbbrrrggg'];

    private const array SOU = [
        2 => [[15, 11], [15, 29]],
        3 => [[15, 11], [9, 29], [21, 29]],
        4 => [[9, 11], [21, 11], [9, 29], [21, 29]],
        5 => [[8, 11], [22, 11], [15, 20], [8, 29], [22, 29]],
        6 => [[7, 11], [15, 11], [23, 11], [7, 29], [15, 29], [23, 29]],
        7 => [[15, 7.5], [7, 20.5], [15, 20.5], [23, 20.5], [7, 32.5], [15, 32.5], [23, 32.5]],
        8 => [[6, 11], [12, 11], [18, 11], [24, 11], [6, 29], [12, 29], [18, 29], [24, 29]],
        9 => [[7, 8], [15, 8], [23, 8], [7, 20], [15, 20], [23, 20], [7, 32], [15, 32], [23, 32]],
    ];

    /** Bâtons rouges des bambous : index des tiges concernées. */
    private const array SOU_RED = [5 => [2], 7 => [0], 9 => [1, 4, 7]];

    /** Caractères à exposer dans le sprite pour l'interface (étapes, sceaux, logo). */
    public const string UI_GLYPHS = '一二三四五東南西北正解答残念満点計聴牌雀';

    /**
     * Le contenu d'une face (repère 30 × 40).
     */
    public static function face(int $tile, bool $red = false): string
    {
        if ($tile < 9) {
            return self::glyph(mb_substr(self::KANJI_NUMBERS, $tile, 1), 15, 15, 16.6, $red ? self::RED : self::INK, 1.3, 1)
                .self::glyph('萬', 18.5, 15, 37.4, self::RED, 1.08, 1.12);
        }

        if ($tile < 18) {
            $number = $tile - 8;

            if ($number === 1) {
                return self::pinOne($red);
            }

            $dots = '';

            foreach (self::PIN[$number] as $index => [$x, $y, $r]) {
                $dots .= self::pinDot($x, $y, $r, $red ? self::RED : self::colorCode(self::PIN_COLORS[$number][$index]));
            }

            return $dots;
        }

        if ($tile < 27) {
            $number = $tile - 17;

            if ($number === 1) {
                return self::souOne($red);
            }

            $height = $number >= 7 ? 9.6 : 14.4;
            $sticks = '';

            foreach (self::SOU[$number] as $index => [$x, $y]) {
                $isRed = $red || in_array($index, self::SOU_RED[$number] ?? [], true);
                $sticks .= self::stick($x, $y, $height, $isRed ? self::RED : self::GREEN);
            }

            return $sticks;
        }

        if ($tile === Tile::HAKU) {
            return '';
        }

        $char = mb_substr('東南西北白發中', $tile - 27, 1);
        $color = match ($tile) {
            Tile::HATSU => self::GREEN,
            Tile::CHUN => self::RED,
            default => self::INK,
        };

        return self::glyph($char, 23, 15, 29, $color);
    }

    /**
     * Un caractère placé comme du texte centré (text-anchor: middle) sur sa ligne de base.
     */
    public static function glyph(string $char, float $size, float $centerX, float $baseline, string $fill, float $scaleX = 1, float $scaleY = 1): string
    {
        $sx = self::r($scaleX * $size / 100, 4);
        $sy = self::r($scaleY * $size / 100, 4);

        return "<path transform=\"translate({$centerX} {$baseline}) scale({$sx} {$sy}) translate(-50 -88)\" fill=\"{$fill}\" d=\"".TileGlyphs::PATHS[$char].'"/>';
    }

    /**
     * Identifiant ASCII d'un caractère du sprite : « 正 » → « g-6b63 ».
     */
    public static function glyphId(string $char): string
    {
        return 'g-'.dechex(mb_ord($char));
    }

    /**
     * Le sprite complet : formes des tuiles, 34 faces (plus les trois cinq rouges) et caractères d'interface.
     */
    public static function sprite(): string
    {
        $symbols = self::bodies();

        for ($tile = 0; $tile < Tile::COUNT; $tile++) {
            $symbols .= '<symbol id="f-'.$tile.'" viewBox="0 0 30 40">'.self::face($tile).'</symbol>';
        }

        foreach (Tile::FIVES as $five) {
            $symbols .= '<symbol id="f-'.$five.'r" viewBox="0 0 30 40">'.self::face($five, true).'</symbol>';
        }

        foreach (mb_str_split(self::UI_GLYPHS) as $char) {
            $symbols .= '<symbol id="'.self::glyphId($char).'" viewBox="0 0 100 100"><path d="'.Glyphs::PATHS[$char].'"/></symbol>';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg">'
            .'<defs>'
            .'<linearGradient id="tile-face" x1="0" y1="0" x2=".18" y2="1">'
            .'<stop offset="0" stop-color="#FFFDF7"/><stop offset=".5" stop-color="'.self::FACE.'"/><stop offset="1" stop-color="#E9E1CB"/>'
            .'</linearGradient>'
            .'<linearGradient id="tile-back" x1="0" y1="0" x2=".2" y2="1">'
            .'<stop offset="0" stop-color="#F2BE4E"/><stop offset=".55" stop-color="#E3A72F"/><stop offset="1" stop-color="#CF9222"/>'
            .'</linearGradient>'
            .'</defs>'
            .$symbols
            .'</svg>';
    }

    /**
     * Les corps de tuile : debout, couchée (appel), retournée.
     * Sous la face ivoire, on voit la tranche ivoire puis le dos safran.
     */
    private static function bodies(): string
    {
        $edge = '<rect x=".55" y=".55" width="%s" height="%s" rx="4.1" fill="none" stroke="#fff" stroke-opacity=".8" stroke-width=".7"/>';

        $up = '<symbol id="tile-up" viewBox="0 0 34 46">'
            .'<rect y="4.6" width="34" height="41.4" rx="4.6" fill="#A86F12"/>'
            .'<rect y="3.2" width="34" height="41.6" rx="4.6" fill="url(#tile-back)"/>'
            .'<rect y="1.3" width="34" height="40.4" rx="4.6" fill="#D6CBAE"/>'
            .'<rect width="34" height="40" rx="4.6" fill="url(#tile-face)"/>'
            .sprintf($edge, 32.9, 38.9)
            .'</symbol>';

        $side = '<symbol id="tile-side" viewBox="0 0 40 40">'
            .'<rect y="4.6" width="40" height="35.4" rx="4.6" fill="#A86F12"/>'
            .'<rect y="3.2" width="40" height="35.6" rx="4.6" fill="url(#tile-back)"/>'
            .'<rect y="1.3" width="40" height="34.4" rx="4.6" fill="#D6CBAE"/>'
            .'<rect width="40" height="34" rx="4.6" fill="url(#tile-face)"/>'
            .sprintf($edge, 38.9, 32.9)
            .'</symbol>';

        $back = '<symbol id="tile-down" viewBox="0 0 34 46">'
            .'<rect y="4.6" width="34" height="41.4" rx="4.6" fill="#BDB196"/>'
            .'<rect y="3.2" width="34" height="41.6" rx="4.6" fill="#EFE8D6"/>'
            .'<rect y="1.3" width="34" height="40.4" rx="4.6" fill="#B37A16"/>'
            .'<rect width="34" height="40" rx="4.6" fill="url(#tile-back)"/>'
            .'<rect x=".55" y=".55" width="32.9" height="38.9" rx="4.1" fill="none" stroke="#FFE7A8" stroke-opacity=".7" stroke-width=".7"/>'
            .'</symbol>';

        return $up.$side.$back;
    }

    private static function colorCode(string $code): string
    {
        return match ($code) {
            'r' => self::RED,
            'g' => self::GREEN,
            default => self::BLUE,
        };
    }

    /**
     * Cercle de pin : anneau plat autour d'une pastille percée en son centre.
     */
    private static function pinDot(float $x, float $y, float $r, string $color): string
    {
        return '<circle cx="'.$x.'" cy="'.$y.'" r="'.self::r($r * 0.84).'" fill="none" stroke="'.$color.'" stroke-width="'.self::r($r * 0.24).'"/>'
            .'<circle cx="'.$x.'" cy="'.$y.'" r="'.self::r($r * 0.42).'" fill="'.$color.'"/>'
            .'<circle cx="'.$x.'" cy="'.$y.'" r="'.self::r($r * 0.13).'" fill="'.self::FACE.'"/>';
    }

    /**
     * Le 1 de cercles : grand médaillon, couronne de pétales autour d'un anneau rouge.
     */
    private static function pinOne(bool $red): string
    {
        $ring = $red ? self::RED : self::GREEN;
        $edge = $red ? self::RED : self::BLUE;
        $petals = '';

        for ($k = 0; $k < 16; $k++) {
            $petals .= '<path d="M15 9.6c1.4 1 1.4 2.6 0 3.6-1.4-1-1.4-2.6 0-3.6z" fill="'.$ring.'" transform="rotate('.($k * 22.5).' 15 20)"/>';
        }

        return '<circle cx="15" cy="20" r="12.4" fill="none" stroke="'.$edge.'" stroke-width="1.2"/>'
            .'<circle cx="15" cy="20" r="11" fill="none" stroke="'.$edge.'" stroke-width=".45"/>'
            .$petals
            .self::pinDot(15, 20, 6.9, self::RED);
    }

    /**
     * Tige de bambou sculptée : fine, cintrée entre trois nœuds, avec un reflet.
     */
    private static function stick(float $x, float $y, float $height, string $color): string
    {
        $width = 3;
        $pinch = 0.6;
        $top = self::r($y - $height / 2);
        $bottom = self::r($y + $height / 2);
        $left = self::r($x - $width / 2);
        $right = self::r($x + $width / 2);
        $upperMiddle = self::r(($top + $y) / 2);
        $lowerMiddle = self::r(($y + $bottom) / 2);

        $body = "M{$left} {$top}Q".self::r($left + $pinch)." {$upperMiddle} {$left} {$y}Q".self::r($left + $pinch)." {$lowerMiddle} {$left} {$bottom}"
            ."H{$right}Q".self::r($right - $pinch)." {$lowerMiddle} {$right} {$y}Q".self::r($right - $pinch)." {$upperMiddle} {$right} {$top}Z";

        $nodes = '';

        foreach ([$top + 0.55, $y, $bottom - 0.55] as $nodeY) {
            $nodes .= '<rect x="'.self::r($x - ($width + 1.1) / 2).'" y="'.self::r($nodeY - 0.6).'" width="'.self::r($width + 1.1).'" height="1.2" rx=".6" fill="'.$color.'"/>';
        }

        $glint = self::r($x - 0.45);

        return '<path d="'.$body.'" fill="'.$color.'"/>'
            .$nodes
            .'<path d="M'.$glint.' '.self::r($top + 1.7).'V'.self::r($y - 1.1).'M'.$glint.' '.self::r($y + 1.1).'V'.self::r($bottom - 1.7).'" stroke="'.self::FACE.'" stroke-width=".4" opacity=".6" stroke-linecap="round"/>';
    }

    /**
     * Le 1 de bambous : l'oiseau (麻雀, « moineau », a donné son nom au jeu), huppe rouge et queue ocellée.
     */
    private static function souOne(bool $red): string
    {
        $green = $red ? self::RED : self::GREEN;
        $blue = $red ? self::RED : self::BLUE;
        $red = self::RED;
        $gold = self::GOLD;
        $face = self::FACE;
        $ink = self::INK;

        $eye = fn (float $x, float $y, float $r): string => "<circle cx=\"{$x}\" cy=\"{$y}\" r=\"{$r}\" fill=\"{$gold}\"/>"
            ."<circle cx=\"{$x}\" cy=\"{$y}\" r=\"".self::r($r * 0.62)."\" fill=\"{$blue}\"/>"
            ."<circle cx=\"{$x}\" cy=\"{$y}\" r=\"".self::r($r * 0.28)."\" fill=\"{$red}\"/>";

        return '<g transform="translate(1.2 2.2) scale(.92)">'
            ."<path d=\"M14.5 24C17 29 21 33.5 26.6 37.6 26.6 33 24 28.6 18.8 23.4z\" fill=\"{$green}\"/>"
            ."<path d=\"M16.6 22.6C21 25.4 24.6 28.6 27.6 33 28.6 28.8 26.4 24.6 20.4 21.2z\" fill=\"{$blue}\"/>"
            ."<path d=\"M13 25.4C14 30 16.4 34.4 19.8 38.4 21 34.8 19.6 29.6 16.4 24.4z\" fill=\"{$blue}\"/>"
            .$eye(25, 35.2, 1.55).$eye(26.2, 30, 1.4).$eye(18.6, 35.4, 1.4)
            ."<path d=\"M10.6 27.4l-.8 6.6M13.4 27.6l.2 6.4M8 34h3.8M12 34h3.6\" stroke=\"{$red}\" stroke-width=\".9\" stroke-linecap=\"round\"/>"
            ."<path d=\"M9.4 9.8C8 13 7.4 16.4 7.8 20.4 8.4 25.4 11.2 28.6 14.6 28.4 18.6 28 20.6 24.6 19.8 20.6 19 17 15.6 15 13.6 12 12.8 10.6 12.8 9.4 13 8.4z\" fill=\"{$green}\"/>"
            ."<path d=\"M8.6 15.6C8 19.6 8.8 23.8 11.4 26.4 9.2 23 9 19.2 9.6 15.8z\" fill=\"{$red}\"/>"
            ."<path d=\"M12.4 15.4C16.4 15.6 19.6 18.8 19.6 22.8 19.4 25.6 17.6 27 15.4 26.6 13 24.4 12 19.8 12.4 15.4z\" fill=\"{$blue}\"/>"
            ."<path d=\"M13.4 19.6q2.2-1 4.4.6M13.8 22.4q2.2-1 4.6.6M14.6 25q1.8-.8 3.6.4\" stroke=\"{$face}\" stroke-width=\".55\" fill=\"none\" stroke-linecap=\"round\"/>"
            ."<circle cx=\"11.6\" cy=\"7.6\" r=\"3.4\" fill=\"{$green}\"/>"
            ."<path d=\"M11.2 4.4l-1.4-3M12.2 4.3l.2-3.2M13.2 4.7l1.6-2.6\" stroke=\"{$green}\" stroke-width=\".55\" stroke-linecap=\"round\"/>"
            ."<circle cx=\"9.8\" cy=\"1.4\" r=\".85\" fill=\"{$red}\"/><circle cx=\"12.4\" cy=\"1.1\" r=\".85\" fill=\"{$red}\"/><circle cx=\"14.8\" cy=\"1.9\" r=\".85\" fill=\"{$red}\"/>"
            ."<circle cx=\"10.6\" cy=\"7.2\" r=\"1.2\" fill=\"{$face}\"/><circle cx=\"10.4\" cy=\"7.2\" r=\".62\" fill=\"{$ink}\"/>"
            ."<path d=\"M8.4 7.4L5.4 8.8l3.2.6z\" fill=\"{$gold}\"/>"
            .'</g>';
    }

    private static function r(float $value, int $precision = 2): float
    {
        return round($value, $precision);
    }
}
