<?php

namespace App\Domain\Mahjong\Hand;

use App\Domain\Mahjong\Tile;

/**
 * Un groupe d'une lecture de la main : suite, brelan ou carré.
 * Pour une suite, la tuile est la plus basse.
 */
final readonly class Group
{
    /**
     * @param  bool  $open  le groupe vient d'un appel, ou d'un brelan complété par ron
     * @param  bool  $concealed  brelan ou carré resté caché (compte double pour les fu)
     * @param  MeldType|null  $meld  type d'appel, si le groupe a été appelé
     * @param  bool  $ronDone  brelan complété par la tuile de ron : il compte comme ouvert
     * @param  bool  $winHere  la tuile gagnante complète ce groupe
     */
    public function __construct(
        public GroupKind $kind,
        public int $tile,
        public bool $open = false,
        public bool $concealed = false,
        public ?MeldType $meld = null,
        public bool $ronDone = false,
        public bool $winHere = false,
    ) {}

    public static function sequence(int $tile): self
    {
        return new self(GroupKind::Sequence, $tile);
    }

    public static function triplet(int $tile): self
    {
        return new self(GroupKind::Triplet, $tile);
    }

    public static function fromMeld(Meld $meld): self
    {
        return new self(
            kind: $meld->type->groupKind(),
            tile: $meld->tile,
            open: $meld->type->opensHand(),
            concealed: ! $meld->type->opensHand(),
            meld: $meld->type,
        );
    }

    public function isSequence(): bool
    {
        return $this->kind === GroupKind::Sequence;
    }

    /**
     * Brelan ou carré.
     */
    public function isSet(): bool
    {
        return $this->kind !== GroupKind::Sequence;
    }

    public function isKan(): bool
    {
        return $this->kind === GroupKind::Kan;
    }

    /**
     * @return list<int>
     */
    public function tiles(): array
    {
        return self::tilesOf($this->kind, $this->tile);
    }

    /**
     * @return list<int>
     */
    public static function tilesOf(GroupKind $kind, int $tile): array
    {
        return match ($kind) {
            GroupKind::Sequence => [$tile, $tile + 1, $tile + 2],
            GroupKind::Triplet => [$tile, $tile, $tile],
            GroupKind::Kan => [$tile, $tile, $tile, $tile],
        };
    }

    /**
     * Le groupe contient une terminale ou un honneur (condition de chanta et junchan).
     */
    public function hasTerminalOrHonor(): bool
    {
        if ($this->isSequence()) {
            return $this->tile % 9 === 0 || $this->tile % 9 === 6;
        }

        return Tile::isTerminalOrHonor($this->tile);
    }
}
