<?php

namespace App\View\Learn;

/**
 * Le lexique : les mots japonais qu'on entend à une table de riichi, par ordre alphabétique.
 * Chaque terme a son ancre (#riichi) pour qu'on puisse y renvoyer depuis les guides.
 */
final class Glossary
{
    /**
     * @return list<array{id: string, term: string, japanese: string, definition: string, guide: ?Guide}>
     */
    public static function entries(): array
    {
        $entries = [
            ['agari', 'Agari', '和了', __('Gagner une main, en tsumo ou en ron. On annonce alors « tsumo » ou « ron », jamais « agari ».'), Guide::Game],
            ['aka-dora', 'Aka-dora', '赤ドラ', __('Un cinq rouge. Chacun vaut un dora : un han de plus si la main gagne. Le jeu en compte en général trois, un par famille.'), Guide::Tiles],
            ['anko', 'Ankou', '暗刻', __('Un brelan fermé, formé sans appel. Il rapporte deux fois plus de fu qu’un brelan ouvert.'), Guide::Scoring],
            ['atama', 'Atama', '雀頭', __('La paire d’une main (on dit aussi jantou). Une main normale compte quatre groupes et une paire.'), Guide::Game],
            ['bakaze', 'Bakaze', '場風', __('Le vent de la manche : Est pendant la première manche, Sud pendant la deuxième. Un brelan de ce vent est un yaku.'), Guide::Game],
            ['baiman', 'Baiman', '倍満', __('La limite de 8 à 10 han : 16 000 points pour un non-donneur, 24 000 pour le donneur.'), Guide::Scoring],
            ['chii', 'Chii', 'チー', __('L’appel qui prend la défausse du joueur de gauche pour compléter une suite. La suite est posée face visible et la main devient ouverte.'), Guide::Game],
            ['chiitoitsu', 'Chiitoitsu', '七対子', __('Sept paires différentes : la seule main gagnante, avec les treize orphelins, qui ne suit pas la forme quatre groupes et une paire. Elle vaut toujours 25 fu.'), Guide::Scoring],
            ['damaten', 'Damaten', '黙聴', __('Être tenpai sans déclarer riichi. La main reste libre et discrète, mais il lui faut un autre yaku pour gagner.'), Guide::Game],
            ['dora', 'Dora', 'ドラ', __('Une tuile bonus, désignée par l’indicateur retourné sur le mur mort. Chaque dora dans la main gagnante ajoute un han, mais n’est pas un yaku.'), Guide::Tiles],
            ['dora-hyouji', 'Dora hyoujihai', 'ドラ表示牌', __('L’indicateur de dora : la tuile retournée sur le mur mort. Le dora est la tuile suivante dans l’ordre de sa famille.'), Guide::Tiles],
            ['fu', 'Fu', '符', __('Les « minipoints » qui affinent la valeur d’une main : 20 au départ, puis ce que rapportent les brelans, la paire, l’attente et la façon de gagner. On arrondit à la dizaine supérieure.'), Guide::Scoring],
            ['furiten', 'Furiten', '振聴', __('Être furiten, c’est avoir l’une de ses tuiles d’attente dans ses propres défausses. On ne peut alors plus gagner en ron, seulement en tsumo.'), Guide::Game],
            ['genbutsu', 'Genbutsu', '現物', __('Une tuile déjà défaussée par un adversaire : elle ne peut pas lui faire gagner la main (furiten). C’est la défausse la plus sûre contre un riichi.'), Guide::Game],
            ['haipai', 'Haipai', '配牌', __('La main de départ : les 13 tuiles distribuées à chacun (14 pour le donneur).'), Guide::Game],
            ['han', 'Han', '翻', __('L’unité de valeur d’une main. Chaque yaku et chaque dora rapportent des han ; avec les fu, ils donnent les points.'), Guide::Scoring],
            ['hanchan', 'Hanchan', '半荘', __('La partie standard : une manche Est puis une manche Sud, soit au moins huit donnes.'), Guide::Game],
            ['haneman', 'Haneman', '跳満', __('La limite de 6 ou 7 han : 12 000 points pour un non-donneur, 18 000 pour le donneur.'), Guide::Scoring],
            ['honba', 'Honba', '本場', __('Le compteur de donnes répétées, posé par le donneur. Chaque honba ajoute 300 points au gain de la main suivante.'), Guide::Game],
            ['ippatsu', 'Ippatsu', '一発', __('Un yaku d’un han : gagner dans le tour qui suit sa déclaration de riichi, avant tout appel.'), Guide::Scoring],
            ['jikaze', 'Jikaze', '自風', __('Le vent du siège : Est pour le donneur, puis Sud, Ouest et Nord dans l’ordre du jeu. Un brelan de son vent est un yaku.'), Guide::Game],
            ['jihai', 'Jihai', '字牌', __('Les honneurs : les quatre vents et les trois dragons. Ils ne forment pas de suites, seulement des paires, des brelans et des carrés.'), Guide::Tiles],
            ['kamicha', 'Kamicha', '上家', __('Le joueur de gauche, qui joue juste avant vous. C’est le seul dont on peut appeler la défausse pour un chii.'), Guide::Game],
            ['kan', 'Kan', '槓', __('Un carré : quatre tuiles identiques. On pioche alors une tuile de remplacement et un nouvel indicateur de dora est retourné.'), Guide::Game],
            ['kanchan', 'Kanchan', '嵌張', __('L’attente sur la tuile du milieu d’une suite, comme 4-6 qui attend le 5. Elle rapporte 2 fu.'), Guide::Scoring],
            ['kiriage', 'Kiriage mangan', '切り上げ満貫', __('Une règle optionnelle qui arrondit au mangan les mains de 4 han 30 fu et 3 han 60 fu, payées presque autant.'), Guide::Scoring],
            ['kokushi', 'Kokushi musou', '国士無双', __('Les treize orphelins : un exemplaire de chaque terminale et de chaque honneur, plus une paire de l’une d’elles. Un yakuman.'), null],
            ['koutsu', 'Koutsu', '刻子', __('Un brelan : trois tuiles identiques. Ouvert s’il a été appelé (pon), fermé sinon.'), Guide::Game],
            ['kuisagari', 'Kuisagari', '食い下がり', __('La perte d’un han par certains yaku quand la main est ouverte : honitsu, chinitsu, sanshoku, ittsu, chanta et junchan.'), Guide::Scoring],
            ['kuitan', 'Kuitan', '喰い断', __('La règle qui autorise tanyao (tout simples) en main ouverte. Elle est appliquée presque partout.'), Guide::Scoring],
            ['machi', 'Machi', '待ち', __('L’attente : la ou les tuiles qui complètent une main tenpai. Ryanmen, kanchan, penchan, shanpon et tanki en sont les formes.'), Guide::Scoring],
            ['mangan', 'Mangan', '満貫', __('La première limite, à partir de 5 han (ou 4 han 40 fu, 3 han 70 fu) : 8 000 points pour un non-donneur, 12 000 pour le donneur.'), Guide::Scoring],
            ['menzen', 'Menzen', '門前', __('Une main fermée, sans aucun appel (un kan fermé est permis). Riichi, pinfu et menzen tsumo l’exigent.'), Guide::Game],
            ['mentsu', 'Mentsu', '面子', __('Un groupe de trois ou quatre tuiles : suite, brelan ou carré. Il en faut quatre pour gagner.'), Guide::Game],
            ['noten', 'Noten', '不聴', __('Ne pas être tenpai. Quand le mur s’épuise, les joueurs noten paient 3 000 points en tout aux joueurs tenpai (noten bappu).'), Guide::Game],
            ['oya', 'Oya', '親', __('Le donneur, assis au vent d’Est. Il gagne et paie une fois et demie plus que les autres, et garde la donne s’il gagne. Les autres joueurs sont les ko.'), Guide::Game],
            ['penchan', 'Penchan', '辺張', __('L’attente au bord d’une famille : 1-2 qui attend le 3, ou 8-9 qui attend le 7. Elle rapporte 2 fu.'), Guide::Scoring],
            ['pinfu', 'Pinfu', '平和', __('Un yaku d’un han : main fermée de quatre suites, paire sans valeur et attente ryanmen. La main ne compte que les fu de base : 20 en tsumo, 30 en ron.'), Guide::Scoring],
            ['pon', 'Pon', 'ポン', __('L’appel qui prend la défausse de n’importe quel joueur pour compléter un brelan. Il passe avant un chii.'), Guide::Game],
            ['renchan', 'Renchan', '連荘', __('Le donneur garde la donne : il a gagné, ou il était tenpai quand le mur s’est épuisé. Un honba est ajouté.'), Guide::Game],
            ['riichi', 'Riichi', '立直', __('La déclaration d’une main fermée tenpai : on mise 1 000 points et on ne change plus sa main. C’est un yaku, qui ouvre droit à l’ippatsu et aux ura-dora.'), Guide::Game],
            ['riichibou', 'Riichi-bou', 'リー棒', __('Le bâton de 1 000 points posé sur la table pour déclarer riichi. Le prochain gagnant le ramasse.'), Guide::Game],
            ['rinshan', 'Rinshan', '嶺上', __('La tuile de remplacement piochée sur le mur mort après un kan. Gagner avec elle est un yaku (rinshan kaihou).'), Guide::Game],
            ['ron', 'Ron', 'ロン', __('Gagner sur la défausse d’un adversaire. Celui qui a défaussé paie toute la main.'), Guide::Game],
            ['ryanmen', 'Ryanmen', '両面', __('L’attente des deux côtés d’une suite, comme 4-5 qui attend le 3 ou le 6. La meilleure attente, qui ne rapporte aucun fu.'), Guide::Scoring],
            ['ryuukyoku', 'Ryuukyoku', '流局', __('Une donne nulle, le plus souvent parce que le mur est épuisé sans gagnant. Les joueurs noten paient les joueurs tenpai.'), Guide::Game],
            ['sanbaiman', 'Sanbaiman', '三倍満', __('La limite de 11 ou 12 han : 24 000 points pour un non-donneur, 36 000 pour le donneur.'), Guide::Scoring],
            ['shanpon', 'Shanpon', '双碰', __('L’attente sur deux paires : l’une deviendra un brelan, l’autre restera la paire.'), Guide::Scoring],
            ['shanten', 'Shanten', '向聴', __('Le nombre de tuiles qui manquent pour être tenpai. Une main à un shanten est à une tuile utile du tenpai.'), Guide::Game],
            ['shimocha', 'Shimocha', '下家', __('Le joueur de droite, qui joue juste après vous. Votre défausse peut lui servir pour un chii.'), Guide::Game],
            ['shuntsu', 'Shuntsu', '順子', __('Une suite : trois tuiles qui se suivent dans la même famille, comme 3-4-5 de cercles. Les honneurs n’en forment pas.'), Guide::Game],
            ['suji', 'Suji', '筋', __('Une technique de défense : si un joueur a défaussé le 4, il ne peut pas gagner sur le 1 ou le 7 avec une attente ryanmen.'), null],
            ['tanki', 'Tanki', '単騎', __('L’attente sur la paire : il ne manque que la deuxième tuile de la paire. Elle rapporte 2 fu.'), Guide::Scoring],
            ['tanyao', 'Tanyao', '断幺九', __('Le yaku « tout simples » : seulement des tuiles de 2 à 8, ni terminale ni honneur. Un han, l’un des yaku les plus courants.'), Guide::Scoring],
            ['tenpai', 'Tenpai', '聴牌', __('Être à une tuile de gagner. La main a 13 tuiles et attend sa quatorzième.'), Guide::Game],
            ['tobi', 'Tobi', '飛び', __('Passer sous zéro point. Selon les règles de la table, la partie s’arrête aussitôt.'), Guide::Game],
            ['toimen', 'Toimen', '対面', __('Le joueur assis en face de vous.'), Guide::Game],
            ['toitsu', 'Toitsu', '対子', __('Une paire : deux tuiles identiques.'), Guide::Game],
            ['tonpuusen', 'Tonpuusen', '東風戦', __('La partie courte : seulement la manche Est, soit au moins quatre donnes.'), Guide::Game],
            ['tsumo', 'Tsumo', '自摸', __('Piocher, et gagner avec la tuile qu’on vient de piocher. Les trois adversaires se partagent alors le paiement.'), Guide::Game],
            ['tsumogiri', 'Tsumogiri', 'ツモ切り', __('Défausser aussitôt la tuile qu’on vient de piocher, sans la garder. C’est ce que fait un joueur en riichi.'), Guide::Game],
            ['uma', 'Uma', 'ウマ', __('Les points de classement ajoutés en fin de partie selon la place (par exemple +15, +5, −5, −15 en milliers). Surtout en tournoi.'), Guide::Game],
            ['ura-dora', 'Ura-dora', '裏ドラ', __('Des dora en plus, désignés par les tuiles cachées sous les indicateurs. On ne les retourne que quand une main en riichi gagne.'), Guide::Scoring],
            ['wanpai', 'Wanpai', '王牌', __('Le mur mort : les 14 dernières tuiles, qu’on ne pioche pas. Il porte les indicateurs de dora et les tuiles de remplacement des kan.'), Guide::Game],
            ['yaku', 'Yaku', '役', __('Une combinaison qui rapporte des han. Il faut au moins un yaku pour gagner : les dora seuls ne suffisent pas.'), Guide::Scoring],
            ['yakuhai', 'Yakuhai', '役牌', __('Un brelan de dragons, du vent de la manche ou de son vent de siège : un yaku d’un han, permis en main ouverte.'), Guide::Scoring],
            ['yakuman', 'Yakuman', '役満', __('La main la plus chère : 32 000 points pour un non-donneur, 48 000 pour le donneur. Treize orphelins, quatre brelans fermés, trois dragons…'), Guide::Scoring],
            ['yaochuuhai', 'Yaochuuhai', '么九牌', __('Les terminales (1 et 9) et les honneurs. Leurs brelans rapportent deux fois plus de fu que ceux des simples.'), Guide::Tiles],
        ];

        $entries = array_map(fn (array $entry): array => array_combine(['id', 'term', 'japanese', 'definition', 'guide'], $entry), $entries);

        usort($entries, fn (array $a, array $b): int => strcasecmp($a['term'], $b['term']));

        return $entries;
    }

    /**
     * Les termes rangés par initiale, pour l'index alphabétique.
     *
     * @return array<string, list<array{id: string, term: string, japanese: string, definition: string, guide: ?Guide}>>
     */
    public static function byLetter(): array
    {
        $letters = [];

        foreach (self::entries() as $entry) {
            $letters[strtoupper($entry['term'][0])][] = $entry;
        }

        return $letters;
    }
}
