<?php

namespace App\Domain\Mahjong\Generation;

use App\Domain\Mahjong\Hand\Hand;
use App\Domain\Mahjong\Hand\Meld;
use App\Domain\Mahjong\Hand\MeldType;

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
     * La même main affichée, avec une autre situation (honba et bâtons de riichi).
     */
    public function withHand(Hand $hand): self
    {
        return new self($this->seed, $this->theme, $hand, $this->handTiles, $this->winTile, $this->melds);
    }

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
     * Les tuiles telles qu'elles sont posées sur la table, en abrégé, pour les redessiner dans le navigateur :
     * « 12 », « 4r » pour un cinq rouge, « s20 » pour une tuile couchée, « x » pour une tuile retournée.
     *
     * @return array{hand: list<string>, win: string, melds: list<list<string>>}
     */
    public function tableCodes(): array
    {
        $code = fn (TileInstance $tile): string => $tile->tile.($tile->red ? 'r' : '');

        return [
            'hand' => array_map($code, $this->handTiles),
            'win' => $code($this->winTile),
            'melds' => array_map(function (MeldView $meld) use ($code): array {
                $last = count($meld->tiles) - 1;

                return array_map(fn (TileInstance $tile, int $index): string => match (true) {
                    $meld->type === MeldType::ClosedKan && ($index === 0 || $index === $last) => 'x',
                    $meld->rotated === $index => 's'.$code($tile),
                    default => $code($tile),
                }, $meld->tiles, array_keys($meld->tiles));
            }, $this->melds),
        ];
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
            'honba' => $hand->honba,
            'riichiSticks' => $hand->riichiSticks,
            'handTiles' => array_map(fn (TileInstance $tile): string => $tile->tile.($tile->red ? 'r' : ''), $this->handTiles),
            'winTile' => $this->winTile->tile.($this->winTile->red ? 'r' : ''),
            'meldViews' => array_map(fn (MeldView $meld): string => $meld->type->value.':'.$meld->tile.'@'.($meld->rotated ?? '-'), $this->melds),
        ];
    }
}
