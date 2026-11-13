<?php

namespace App\Domain\Mahjong\Generation;

use App\Domain\Mahjong\Hand\Group;

/**
 * Le squelette d'une main avant les appels : quatre groupes et une paire.
 */
final readonly class ThemeDraft
{
    /**
     * @param  list<Group>  $groups
     * @param  bool  $closedOnly  le thème exige une main fermée (pinfu, iipeikou…)
     * @param  list<int>  $keepClosed  index des groupes qui doivent rester cachés
     */
    public function __construct(
        public array $groups,
        public int $pair,
        public bool $closedOnly = false,
        public array $keepClosed = [],
    ) {}
}
