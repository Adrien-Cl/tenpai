<?php

namespace App\Domain\Mahjong\Generation;

use App\Domain\Mahjong\Hand\Hand;
use App\Domain\Mahjong\Hand\Meld;

/**
 * Une main tirée d'une graine, avec ce qu'il faut pour l'afficher.
 */
final readonly class GeneratedHand
{
    /**
     * @param  list<TileInstance>  $handTiles  tuiles cachées sans la tuile gagnante, triées
     * @param  list<MeldView>  $melds  appels dans l'ordre d'affichage
     */
    public function __construct(
        public Seed $seed,
        public Theme $theme,
        public Hand $hand,
        public array $handTiles,
        public TileInstance $winTile,
        public array $melds,
    ) {}

    /**
     * Toutes les tuiles visibles, pour compter les exemplaires : main, appels et indicateurs.
     *
     * @return list<int>
     */
    public function tilesOnTable(): array
    {
        $tiles = $this->hand->allTiles();
        array_push($tiles, ...$this->hand->dora, ...$this->hand->ura);

        return $tiles;
    }

    /**
     * Forme sérialisable, utile pour comparer deux mains.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $hand = $this->hand;

        return [
            'seed' => $this->seed->value,
            'theme' => $this->theme->value,
            'closed' => $hand->closed,
            'melds' => array_map(fn (Meld $meld): string => $meld->type->value.':'.$meld->tile, $hand->melds),
            'win' => $hand->win,
            'tsumo' => $hand->tsumo,
            'seat' => $hand->seat->value,
            'round' => $hand->round->value,
            'riichi' => $hand->riichi,
            'doubleRiichi' => $hand->doubleRiichi,
            'ippatsu' => $hand->ippatsu,
            'haitei' => $hand->haitei,
            'rinshan' => $hand->rinshan,
            'chankan' => $hand->chankan,
            'dora' => $hand->dora,
            'ura' => $hand->ura,
            'aka' => $hand->aka,
            'handTiles' => array_map(fn (TileInstance $tile): string => $tile->tile.($tile->red ? 'r' : ''), $this->handTiles),
            'winTile' => $this->winTile->tile.($this->winTile->red ? 'r' : ''),
            'meldViews' => array_map(fn (MeldView $meld): string => $meld->type->value.':'.$meld->tile.'@'.($meld->rotated ?? '-'), $this->melds),
        ];
    }
}
