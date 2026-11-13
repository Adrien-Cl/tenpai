<?php

namespace App\Domain\Mahjong\Generation;

use App\Domain\Mahjong\Hand\Group;
use App\Domain\Mahjong\Tile;

/**
 * Tire les groupes et la paire d'une main selon son thème.
 */
final class ThemeBuilder
{
    /** @var list<int> */
    private array $numbered;

    /** @var list<int> */
    private array $simples;

    /** @var list<int> */
    private array $honors;

    /** @var list<int> */
    private array $terminals;

    /** @var list<int> */
    private array $terminalsAndHonors;

    public function __construct(private readonly SeededRandom $random)
    {
        $this->numbered = range(0, 26);
        $this->simples = array_values(array_filter($this->numbered, Tile::isSimple(...)));
        $this->honors = range(27, 33);
        $this->terminals = array_values(array_filter($this->numbered, Tile::isTerminal(...)));
        $this->terminalsAndHonors = [...$this->terminals, ...$this->honors];
    }

    public function build(Theme $theme, int $seatWind, int $roundWind): ThemeDraft
    {
        $groups = [];

        switch ($theme) {
            case Theme::Tanyao:
                for ($i = 0; $i < 4; $i++) {
                    $groups[] = $this->random->chance(0.7) ? $this->randomSequence([0, 1, 2], 1, 5) : Group::triplet($this->random->pick($this->simples));
                }

                return new ThemeDraft($groups, $this->random->pick($this->simples));

            case Theme::Pinfu:
                for ($i = 0; $i < 4; $i++) {
                    $groups[] = $this->randomSequence();
                }

                $pair = $this->random->chance(0.85)
                    ? $this->random->pick($this->numbered)
                    : $this->random->pick(array_filter([27, 28, 29, 30], fn (int $wind): bool => $wind !== $seatWind && $wind !== $roundWind));

                return new ThemeDraft($groups, $pair, closedOnly: true);

            case Theme::Yakuhai:
                $groups[] = Group::triplet($this->random->pick([31, 32, 33, 31, 32, 33, $seatWind, $roundWind]));

                for ($i = 0; $i < 3; $i++) {
                    $groups[] = $this->randomGroup();
                }

                $pair = $this->random->chance(0.7) ? $this->random->pick($this->numbered) : $this->random->pick($this->honors);

                return new ThemeDraft($groups, $pair);

            case Theme::Honitsu:
                $suit = $this->random->int(3);

                for ($i = 0; $i < 4; $i++) {
                    $groups[] = $this->random->chance(0.6)
                        ? $this->randomSequence([$suit])
                        : Group::triplet($this->random->chance(0.5) ? $this->random->pick($this->honors) : $suit * 9 + $this->random->int(9));
                }

                $pair = $this->random->chance(0.5) ? $this->random->pick($this->honors) : $suit * 9 + $this->random->int(9);
                $hasHonorSet = array_filter($groups, fn (Group $group): bool => $group->isSet() && Tile::isHonor($group->tile)) !== [];

                if (! Tile::isHonor($pair) && ! $hasHonorSet) {
                    $groups[3] = Group::triplet($this->random->pick($this->honors));
                }

                return new ThemeDraft($groups, $pair);

            case Theme::Chinitsu:
                $suit = $this->random->int(3);

                for ($i = 0; $i < 4; $i++) {
                    $groups[] = $this->random->chance(0.75) ? $this->randomSequence([$suit]) : Group::triplet($suit * 9 + $this->random->int(9));
                }

                return new ThemeDraft($groups, $suit * 9 + $this->random->int(9));

            case Theme::Toitoi:
                for ($i = 0; $i < 4; $i++) {
                    $groups[] = Group::triplet($this->random->chance(0.75) ? $this->random->pick($this->numbered) : $this->random->pick($this->honors));
                }

                return new ThemeDraft($groups, $this->random->pick([...$this->numbered, ...$this->honors]));

            case Theme::Sanankou:
                for ($i = 0; $i < 3; $i++) {
                    $groups[] = Group::triplet($this->random->chance(0.8) ? $this->random->pick($this->numbered) : $this->random->pick($this->honors));
                }

                $groups[] = $this->randomSequence();

                return new ThemeDraft($groups, $this->random->pick($this->numbered), keepClosed: [0, 1, 2]);

            case Theme::Sanshoku:
                $number = $this->random->int(7);

                foreach ([0, 1, 2] as $suit) {
                    $groups[] = Group::sequence($suit * 9 + $number);
                }

                $groups[] = $this->randomGroup();

                return new ThemeDraft($groups, $this->random->pick([...$this->numbered, ...$this->honors]));

            case Theme::Ittsu:
                $suit = $this->random->int(3);

                foreach ([0, 3, 6] as $number) {
                    $groups[] = Group::sequence($suit * 9 + $number);
                }

                $groups[] = $this->randomGroup();

                return new ThemeDraft($groups, $this->random->pick([...$this->numbered, ...$this->honors]));

            case Theme::Chanta:
            case Theme::Junchan:
                $pool = $theme === Theme::Chanta ? $this->terminalsAndHonors : $this->terminals;

                for ($i = 0; $i < 4; $i++) {
                    $groups[] = $this->random->chance(0.6)
                        ? Group::sequence($this->random->int(3) * 9 + $this->random->pick([0, 6]))
                        : Group::triplet($this->random->pick($pool));
                }

                if (array_filter($groups, fn (Group $group): bool => $group->isSequence()) === []) {
                    $groups[0] = Group::sequence($this->random->int(3) * 9 + $this->random->pick([0, 6]));
                }

                return new ThemeDraft($groups, $this->random->pick($pool));

            case Theme::Iipeikou:
                $double = $this->randomSequence();
                array_push($groups, $double, $double, $this->randomSequence(), $this->randomGroup());

                return new ThemeDraft($groups, $this->random->pick($this->numbered), closedOnly: true);

            case Theme::Ryanpeikou:
                $first = $this->randomSequence();
                $second = $this->randomSequence();
                array_push($groups, $first, $first, $second, $second);

                return new ThemeDraft($groups, $this->random->pick([...$this->numbered, ...$this->honors]), closedOnly: true);

            case Theme::Shousangen:
                $dragons = $this->random->shuffle([31, 32, 33]);
                array_push($groups, Group::triplet($dragons[0]), Group::triplet($dragons[1]), $this->randomGroup(), $this->randomGroup());

                return new ThemeDraft($groups, $dragons[2]);

            case Theme::Honroutou:
                for ($i = 0; $i < 4; $i++) {
                    $groups[] = Group::triplet($this->random->pick($this->terminalsAndHonors));
                }

                return new ThemeDraft($groups, $this->random->pick($this->terminalsAndHonors));

            case Theme::Doukou:
                $number = $this->random->int(9);

                foreach ([0, 1, 2] as $suit) {
                    $groups[] = Group::triplet($suit * 9 + $number);
                }

                $groups[] = $this->randomGroup();

                return new ThemeDraft($groups, $this->random->pick([...$this->numbered, ...$this->honors]));

            case Theme::Free:
            case Theme::Chiitoitsu:
            default:
                for ($i = 0; $i < 4; $i++) {
                    $groups[] = $this->randomGroup();
                }

                return new ThemeDraft($groups, $this->random->pick([...$this->numbered, ...$this->honors]));
        }
    }

    /**
     * Suite au hasard, dans les familles et entre les points de départ donnés (0 = suite 1-2-3).
     *
     * @param  list<int>  $suits
     */
    private function randomSequence(array $suits = [0, 1, 2], int $lowest = 0, int $highest = 6): Group
    {
        $suit = $this->random->pick($suits);

        return Group::sequence($suit * 9 + $lowest + $this->random->int($highest - $lowest + 1));
    }

    private function randomGroup(): Group
    {
        if ($this->random->chance(0.62)) {
            return $this->randomSequence();
        }

        return Group::triplet($this->random->chance(0.25) ? $this->random->pick($this->honors) : $this->random->pick($this->numbered));
    }
}
