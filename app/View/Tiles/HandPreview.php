<?php

namespace App\View\Tiles;

use App\Domain\Mahjong\Generation\GeneratedHand;
use App\Domain\Mahjong\Generation\TileInstance;
use App\Domain\Mahjong\Hand\MeldType;
use GdImage;

/**
 * L'aperçu d'une main partagée (Open Graph) : les tuiles de la main posées sur le tapis d'un fond 1200 × 630.
 *
 * Les tuiles et les fonds sont des PNG pré-rendus avec les styles du site (resources/og/generate.mjs) :
 * le serveur n'a qu'à les assembler avec GD, sans police ni dessin vectoriel.
 */
final class HandPreview
{
    /** Marge transparente autour de chaque tuile pré-rendue (ombre comprise). */
    public const int PAD = 12;

    public const int WIDTH = 1200;

    public const int HEIGHT = 630;

    private const int MARGIN = 56;

    private const int FELT_TOP = 418;

    private const int FELT_HEIGHT = 212;

    /** Espace entre la main, la tuile gagnante et chaque appel. */
    private const int GROUP_GAP = 22;

    /** @var array<string, GdImage> */
    private array $images = [];

    public static function isAvailable(): bool
    {
        return function_exists('imagecreatefrompng');
    }

    public function render(GeneratedHand $generated): string
    {
        $base = $generated->seed->dailyDate() !== null ? 'base-defi' : 'base';
        $canvas = $this->load(app()->getLocale() === 'fr' ? $base : $base.'-'.app()->getLocale());
        $groups = $this->groups($generated);

        $strides = array_map(fn (array $group): int => array_sum(array_map($this->stride(...), array_column($group, 'image'))), $groups);
        $natural = array_sum($strides) + self::GROUP_GAP * (count($groups) - 1);
        $scale = min(1.0, (self::WIDTH - 2 * self::MARGIN) / $natural);
        $tileHeight = imagesy($this->load('tiles/0'));

        $x = (self::WIDTH - $natural * $scale) / 2;
        $y = self::FELT_TOP + (self::FELT_HEIGHT - $tileHeight * $scale) / 2;

        foreach ($groups as $group) {
            foreach ($group as ['image' => $name, 'win' => $win]) {
                $image = $this->load($name);
                $width = imagesx($image);
                $height = imagesy($image);

                imagecopyresampled(
                    $canvas, $image,
                    (int) round($x - self::PAD * $scale), (int) round($y),
                    0, 0,
                    (int) round($width * $scale), (int) round($height * $scale),
                    $width, $height,
                );

                if ($win) {
                    $this->underline($canvas, $x, $y + ($height - self::PAD) * $scale, $this->stride($name) * $scale);
                }

                $x += $this->stride($name) * $scale;
            }

            $x += self::GROUP_GAP * $scale;
        }

        ob_start();
        imagepng($canvas, null, 7);

        return (string) ob_get_clean();
    }

    /**
     * La main cachée, la tuile gagnante, puis les appels, comme sur la table.
     *
     * @return list<list<array{image: string, win: bool}>>
     */
    private function groups(GeneratedHand $generated): array
    {
        $groups = [
            array_map(fn (TileInstance $tile): array => ['image' => $this->face($tile), 'win' => false], $generated->handTiles),
            [['image' => $this->face($generated->winTile), 'win' => true]],
        ];

        foreach ($generated->melds as $meld) {
            $last = count($meld->tiles) - 1;
            $groups[] = array_map(fn (TileInstance $tile, int $index): array => [
                'image' => match (true) {
                    $meld->type === MeldType::ClosedKan && ($index === 0 || $index === $last) => 'tiles/back',
                    $meld->rotated === $index => $this->face($tile, side: true),
                    default => $this->face($tile),
                },
                'win' => false,
            ], $meld->tiles, array_keys($meld->tiles));
        }

        return $groups;
    }

    private function face(TileInstance $tile, bool $side = false): string
    {
        return 'tiles/'.($side ? 'side-' : '').$tile->tile.($tile->red ? 'r' : '');
    }

    /**
     * Largeur visible d'une tuile : les tuiles d'une rangée se touchent.
     */
    private function stride(string $name): int
    {
        return imagesx($this->load($name)) - 2 * self::PAD;
    }

    /**
     * Le trait doré sous la tuile gagnante, comme à l'écran.
     */
    private function underline(GdImage $canvas, float $x, float $bottom, float $width): void
    {
        $gilt = imagecolorallocate($canvas, 0xE3, 0xA7, 0x2F);

        imagefilledrectangle(
            $canvas,
            (int) round($x + $width * 0.18), (int) round($bottom + 10),
            (int) round($x + $width * 0.82), (int) round($bottom + 14),
            $gilt,
        );
    }

    private function load(string $name): GdImage
    {
        if (! isset($this->images[$name])) {
            $image = imagecreatefrompng(resource_path("og/{$name}.png"));
            imagealphablending($image, true);
            $this->images[$name] = $image;
        }

        return $this->images[$name];
    }
}
