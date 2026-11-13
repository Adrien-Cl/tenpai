<?php

namespace App\Domain\Mahjong;

use LogicException;

/**
 * Les yaku distribués par le générateur, dans l'ordre d'annonce.
 */
enum Yaku: string
{
    case Riichi = 'riichi';
    case DoubleRiichi = 'driichi';
    case Ippatsu = 'ippatsu';
    case MenzenTsumo = 'tsumo';
    case Haitei = 'haitei';
    case Houtei = 'houtei';
    case Rinshan = 'rinshan';
    case Chankan = 'chankan';
    case Pinfu = 'pinfu';
    case Tanyao = 'tanyao';
    case Iipeikou = 'iipeikou';
    case Ryanpeikou = 'ryanpeikou';
    case Chiitoitsu = 'chiitoi';
    case Toitoi = 'toitoi';
    case Sanankou = 'sanankou';
    case Sankantsu = 'sankantsu';
    case Sanshoku = 'sanshoku';
    case SanshokuDoukou = 'doukou';
    case Ittsu = 'ittsu';
    case Chanta = 'chanta';
    case Junchan = 'junchan';
    case Honroutou = 'honroutou';
    case Haku = 'haku';
    case Hatsu = 'hatsu';
    case Chun = 'chun';
    case SeatWind = 'jikaze';
    case RoundWind = 'bakaze';
    case Shousangen = 'shousangen';
    case Honitsu = 'honitsu';
    case Chinitsu = 'chinitsu';

    public function label(): string
    {
        return match ($this) {
            self::Riichi => 'Riichi',
            self::DoubleRiichi => 'Double riichi',
            self::Ippatsu => 'Ippatsu',
            self::MenzenTsumo => 'Menzen tsumo',
            self::Haitei => 'Haitei',
            self::Houtei => 'Houtei',
            self::Rinshan => 'Rinshan kaihou',
            self::Chankan => 'Chankan',
            self::Pinfu => 'Pinfu',
            self::Tanyao => 'Tanyao',
            self::Iipeikou => 'Iipeikou',
            self::Ryanpeikou => 'Ryanpeikou',
            self::Chiitoitsu => 'Chiitoitsu',
            self::Toitoi => 'Toitoi',
            self::Sanankou => 'Sanankou',
            self::Sankantsu => 'Sankantsu',
            self::Sanshoku => 'Sanshoku doujun',
            self::SanshokuDoukou => 'Sanshoku doukou',
            self::Ittsu => 'Ittsu',
            self::Chanta => 'Chanta',
            self::Junchan => 'Junchan',
            self::Honroutou => 'Honroutou',
            self::Haku => 'Yakuhai : Haku',
            self::Hatsu => 'Yakuhai : Hatsu',
            self::Chun => 'Yakuhai : Chun',
            self::SeatWind => 'Yakuhai : vent du siège',
            self::RoundWind => 'Yakuhai : vent de la manche',
            self::Shousangen => 'Shousangen',
            self::Honitsu => 'Honitsu',
            self::Chinitsu => 'Chinitsu',
        };
    }

    /**
     * Nom sans le préfixe « Yakuhai : », pour les listes compactes.
     */
    public function shortLabel(): string
    {
        return ucfirst(str_replace('Yakuhai : ', '', $this->label()));
    }

    public function gloss(): string
    {
        return match ($this) {
            self::Riichi => 'déclaration',
            self::DoubleRiichi => 'riichi au premier tour',
            self::Ippatsu => 'coup immédiat',
            self::MenzenTsumo => 'pioche main fermée',
            self::Haitei => 'dernière pioche',
            self::Houtei => 'dernière défausse',
            self::Rinshan => 'tuile de remplacement',
            self::Chankan => 'vol du kan',
            self::Pinfu => 'main sans points',
            self::Tanyao => 'tout simples',
            self::Iipeikou => 'double suite',
            self::Ryanpeikou => 'deux doubles suites',
            self::Chiitoitsu => 'sept paires',
            self::Toitoi => 'tout brelans',
            self::Sanankou => 'trois brelans cachés',
            self::Sankantsu => 'trois carrés',
            self::Sanshoku => 'suite tricolore',
            self::SanshokuDoukou => 'brelan tricolore',
            self::Ittsu => 'grande suite',
            self::Chanta => 'terminales et honneurs partout',
            self::Junchan => 'terminales partout',
            self::Honroutou => 'terminales et honneurs',
            self::Haku => 'dragon blanc',
            self::Hatsu => 'dragon vert',
            self::Chun => 'dragon rouge',
            self::SeatWind => 'jikaze',
            self::RoundWind => 'bakaze',
            self::Shousangen => 'petits trois dragons',
            self::Honitsu => 'semi-pur',
            self::Chinitsu => 'pur',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Riichi => 'Main fermée et riichi déclaré.',
            self::DoubleRiichi => 'Riichi déclaré dès votre première défausse, sans aucun appel avant. Remplace riichi.',
            self::Ippatsu => 'Victoire avant votre prochaine défausse après le riichi, sans appel entre-temps.',
            self::MenzenTsumo => 'Victoire sur votre propre pioche avec une main fermée (les kan fermés ne l’ouvrent pas).',
            self::Haitei => 'Tsumo sur la dernière tuile du mur.',
            self::Houtei => 'Ron sur la toute dernière défausse.',
            self::Rinshan => 'Victoire sur la tuile piochée après un kan.',
            self::Chankan => 'Ron sur la tuile qu’un adversaire ajoute à son pon pour en faire un kan.',
            self::Pinfu => 'Main fermée, quatre suites, paire sans valeur et attente ryanmen.',
            self::Tanyao => 'Uniquement des tuiles de 2 à 8, aucun honneur ni terminale.',
            self::Iipeikou => 'Deux suites identiques dans la même famille, main fermée.',
            self::Ryanpeikou => 'Deux fois deux suites identiques, main fermée. Remplace iipeikou.',
            self::Chiitoitsu => 'Sept paires toutes différentes. Toujours 25 fu.',
            self::Toitoi => 'Quatre brelans ou carrés.',
            self::Sanankou => 'Trois brelans ou carrés fermés. Un brelan complété par ron ne compte pas comme caché.',
            self::Sankantsu => 'Trois kan, ouverts ou fermés.',
            self::Sanshoku => 'La même suite dans les trois familles.',
            self::SanshokuDoukou => 'Le même brelan dans les trois familles.',
            self::Ittsu => '1-2-3, 4-5-6 et 7-8-9 dans la même famille.',
            self::Chanta => 'Chaque groupe et la paire contiennent une terminale ou un honneur, avec au moins une suite.',
            self::Junchan => 'Chaque groupe et la paire contiennent un 1 ou un 9, sans honneur, avec au moins une suite.',
            self::Honroutou => 'Uniquement des terminales et des honneurs.',
            self::Haku => 'Brelan ou carré de dragons blancs.',
            self::Hatsu => 'Brelan ou carré de dragons verts.',
            self::Chun => 'Brelan ou carré de dragons rouges.',
            self::SeatWind => 'Brelan ou carré de votre propre vent.',
            self::RoundWind => 'Brelan ou carré du vent de la manche.',
            self::Shousangen => 'Deux brelans de dragons et une paire du troisième. Les deux yakuhai s’ajoutent.',
            self::Honitsu => 'Une seule famille plus des honneurs.',
            self::Chinitsu => 'Une seule famille, aucun honneur.',
        };
    }

    /**
     * Valeur en han avec une main fermée.
     */
    public function closedHan(): int
    {
        return match ($this) {
            self::Riichi, self::Ippatsu, self::MenzenTsumo, self::Haitei, self::Houtei, self::Rinshan,
            self::Chankan, self::Pinfu, self::Tanyao, self::Iipeikou, self::Haku, self::Hatsu, self::Chun,
            self::SeatWind, self::RoundWind => 1,
            self::DoubleRiichi, self::Chiitoitsu, self::Toitoi, self::Sanankou, self::Sankantsu, self::Sanshoku,
            self::SanshokuDoukou, self::Ittsu, self::Chanta, self::Honroutou, self::Shousangen => 2,
            self::Ryanpeikou, self::Junchan, self::Honitsu => 3,
            self::Chinitsu => 6,
        };
    }

    /**
     * Valeur en han avec une main ouverte, ou null si le yaku exige une main fermée.
     */
    public function openHan(): ?int
    {
        return match ($this) {
            self::Riichi, self::DoubleRiichi, self::Ippatsu, self::MenzenTsumo, self::Pinfu,
            self::Iipeikou, self::Ryanpeikou, self::Chiitoitsu => null,
            self::Haitei, self::Houtei, self::Rinshan, self::Chankan, self::Tanyao, self::Sanshoku,
            self::Ittsu, self::Chanta, self::Haku, self::Hatsu, self::Chun, self::SeatWind, self::RoundWind => 1,
            self::Toitoi, self::Sanankou, self::Sankantsu, self::SanshokuDoukou, self::Junchan,
            self::Honroutou, self::Shousangen, self::Honitsu => 2,
            self::Chinitsu => 5,
        };
    }

    public function isClosedOnly(): bool
    {
        return $this->openHan() === null;
    }

    /**
     * Le yaku perd un han quand la main est ouverte (kuisagari).
     */
    public function losesHanWhenOpen(): bool
    {
        return $this->openHan() !== null && $this->openHan() < $this->closedHan();
    }

    public function han(bool $closed): int
    {
        $han = $closed ? $this->closedHan() : $this->openHan();

        if ($han === null) {
            throw new LogicException("{$this->label()} exige une main fermée.");
        }

        return $han;
    }

    /**
     * Autres noms courants : romanisations, noms longs, anglais, français.
     *
     * @return list<string>
     */
    public function aliases(): array
    {
        return match ($this) {
            self::Riichi => ['reach'],
            self::DoubleRiichi => ['daburu riichi', 'double reach', 'w riichi'],
            self::Ippatsu, self::Chankan, self::Pinfu, self::Honroutou, self::Shousangen => [],
            self::MenzenTsumo => ['menzen tsumo', 'mentsumo', 'menzenchin tsumohou'],
            self::Haitei => ['haitei raoyue'],
            self::Houtei => ['houtei raoyui', 'hotei'],
            self::Rinshan => ['rinshan kaihou'],
            self::Tanyao => ['kuitan', 'tanyaochuu', 'all simples'],
            self::Iipeikou => ['ipeiko', 'iipeiko'],
            self::Ryanpeikou => ['ryanpeiko'],
            self::Chiitoitsu => ['chiitoitsu', 'chitoitsu', 'seven pairs', '7 paires'],
            self::Toitoi => ['toitoihou', 'all pons'],
            self::Sanankou => ['san ankou'],
            self::Sankantsu => ['san kantsu'],
            self::Sanshoku => ['sanshoku doujun'],
            self::SanshokuDoukou => ['sanshoku doukou'],
            self::Ittsu => ['ikkitsuukan', 'ikki tsuukan', 'itsu', 'pure straight'],
            self::Chanta => ['chantaiyao'],
            self::Junchan => ['junchantaiyao'],
            self::Haku => ['yakuhai', 'dragon blanc'],
            self::Hatsu => ['yakuhai', 'dragon vert'],
            self::Chun => ['yakuhai', 'dragon rouge'],
            self::SeatWind => ['yakuhai', 'vent', 'seat wind'],
            self::RoundWind => ['yakuhai', 'vent', 'round wind', 'prevalent wind'],
            self::Honitsu => ['honiisou', 'half flush'],
            self::Chinitsu => ['chiniisou', 'full flush'],
        };
    }
}
