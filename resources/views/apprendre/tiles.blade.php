<x-guide :guide="$guide" :toc="[
    ['jeu', __('136 tuiles, 34 sortes')],
    ['familles', __('Les trois familles')],
    ['honneurs', __('Les honneurs')],
    ['terminales', __('Simples, terminales et honneurs')],
    ['rouges', __('Les cinq rouges')],
    ['dora', __('Les dora')],
    ['notation', __('La notation 123m')],
]">
    <x-slot:intro>{{ __('Avant de jouer, il faut reconnaître les tuiles. Bonne nouvelle : il n’y en a que 34 sortes, et la plupart se lisent comme des chiffres.') }}</x-slot:intro>

    <h2 id="jeu">{{ __('136 tuiles, 34 sortes') }}</h2>
    <p>{{ __('Un jeu de riichi compte 136 tuiles : 34 sortes différentes, chacune en quatre exemplaires. On les range en deux groupes : les tuiles numérotées, réparties en trois familles de 1 à 9, et les honneurs, qui n’ont pas de chiffre.') }}</p>
    <p>{{ __('Contrairement au mahjong chinois, le riichi n’utilise ni fleurs ni saisons.') }}</p>

    <h2 id="familles">{{ __('Les trois familles') }}</h2>
    <p>{{ __('Chaque famille va de 1 à 9. On forme des suites avec des tuiles qui se suivent dans la même famille, et des brelans avec trois tuiles identiques.') }}</p>

    <h3>{{ __('Les caractères (man)') }}</h3>
    <p>{{ __('Le chiffre est écrit en japonais au-dessus du caractère 萬, « dix mille ». Il suffit de retenir les neuf premiers chiffres : 一 二 三 四 五 六 七 八 九.') }}</p>
    <div class="felt guide-felt"><x-tile-row tiles="123456789m" size="clamp(1.5rem, 7vw, 2.6rem)" /></div>

    <h3>{{ __('Les cercles (pin)') }}</h3>
    <p>{{ __('On compte simplement les cercles, comme des pièces de monnaie.') }}</p>
    <div class="felt guide-felt"><x-tile-row tiles="123456789p" size="clamp(1.5rem, 7vw, 2.6rem)" /></div>

    <h3>{{ __('Les bambous (sou)') }}</h3>
    <p>{{ __('On compte les tiges de bambou. Seul piège : le 1 de bambous est dessiné comme un oiseau.') }}</p>
    <div class="felt guide-felt"><x-tile-row tiles="123456789s" size="clamp(1.5rem, 7vw, 2.6rem)" /></div>

    <h2 id="honneurs">{{ __('Les honneurs') }}</h2>
    <p>{{ __('Les honneurs ne se suivent pas : ils ne forment que des paires, des brelans et des carrés. Il y en a sept sortes.') }}</p>

    <h3>{{ __('Les quatre vents') }}</h3>
    <p>{{ __('Est, Sud, Ouest et Nord, écrits 東 南 西 北. Chaque joueur a son vent de siège, et chaque manche a le sien : un brelan du bon vent est un yaku.') }}</p>
    <div class="felt guide-felt"><x-tile-row tiles="1234z" size="clamp(1.5rem, 7vw, 2.6rem)" /></div>

    <h3>{{ __('Les trois dragons') }}</h3>
    <p>{{ __('Le dragon blanc (Haku, une tuile vierge), le dragon vert (Hatsu, 發) et le dragon rouge (Chun, 中). Un brelan de dragons est toujours un yaku.') }}</p>
    <div class="felt guide-felt"><x-tile-row tiles="567z" size="clamp(1.5rem, 7vw, 2.6rem)" /></div>

    <h2 id="terminales">{{ __('Simples, terminales et honneurs') }}</h2>
    <p>{{ __('Beaucoup de règles distinguent trois sortes de tuiles. Ces mots reviennent sans cesse dans le compte des points.') }}</p>
    <ul>
        <li>{{ __('Les simples : les tuiles de 2 à 8. Une main faite uniquement de simples est le yaku tanyao.') }}</li>
        <li>{{ __('Les terminales : les 1 et les 9 de chaque famille.') }}</li>
        <li>{{ __('Les honneurs : les vents et les dragons. Terminales et honneurs ensemble s’appellent yaochuuhai ; leurs brelans rapportent deux fois plus de fu.') }}</li>
    </ul>
    <div class="felt guide-felt flex flex-wrap items-end gap-x-8 gap-y-4">
        <span><span class="mb-2 block text-sm text-felt-muted">{{ __('Simples') }}</span><x-tile-row tiles="2345678p" size="clamp(1.3rem, 5vw, 2rem)" /></span>
        <span><span class="mb-2 block text-sm text-felt-muted">{{ __('Terminales et honneurs') }}</span><x-tile-row tiles="19m 19p 19s 1234567z" size="clamp(1.3rem, 5vw, 2rem)" /></span>
    </div>

    <h2 id="rouges">{{ __('Les cinq rouges') }}</h2>
    <p>{{ __('Dans la plupart des jeux, un des quatre 5 de chaque famille est rouge : c’est un aka-dora. Il se joue comme un 5 ordinaire, mais chaque cinq rouge dans la main gagnante ajoute un han.') }}</p>
    <div class="felt guide-felt"><x-tile-row tiles="0m 0p 0s" size="clamp(1.5rem, 7vw, 2.6rem)" /></div>
    <p>{{ __('Les règles de tournoi s’en passent souvent : Tenpai vous laisse choisir dans les règles de la table.') }}</p>

    <h2 id="dora">{{ __('Les dora : la tuile suivante') }}</h2>
    <p>{{ __('Au début de chaque donne, on retourne une tuile du mur mort : l’indicateur de dora. Le dora n’est pas cette tuile, mais la suivante. Chaque dora dans la main gagnante vaut un han, mais ne remplace pas un yaku.') }}</p>
    <ul>
        <li>{{ __('Dans une famille, on passe au chiffre suivant ; après le 9 vient le 1.') }}</li>
        <li>{{ __('Pour les vents : Est, Sud, Ouest, Nord, puis Est.') }}</li>
        <li>{{ __('Pour les dragons : blanc, vert, rouge, puis blanc.') }}</li>
    </ul>
    <div class="felt guide-felt flex flex-wrap items-end gap-x-10 gap-y-5">
        @foreach (['9m' => '1m', '4z' => '1z', '7z' => '5z'] as $indicator => $dora)
            <span class="flex items-end gap-3">
                <span><span class="mb-2 block text-xs text-felt-muted">{{ __('Indicateur') }}</span><x-tile-row :tiles="$indicator" size="2rem" /></span>
                <span class="pb-3 text-felt-muted" aria-hidden="true">→</span>
                <span><span class="mb-2 block text-xs text-felt-muted">{{ __('Dora') }}</span><x-tile-row :tiles="$dora" size="2rem" /></span>
            </span>
        @endforeach
    </div>
    <p class="guide-note">{{ __('Une main en riichi qui gagne retourne aussi les tuiles cachées sous les indicateurs : ce sont les ura-dora, qui se lisent de la même façon.') }}</p>

    <h2 id="notation">{{ __('La notation 123m') }}</h2>
    <p>{{ __('Sur internet et dans les livres, on écrit les mains en abrégé : les chiffres, puis une lettre pour la famille. m pour les caractères (man), p pour les cercles (pin), s pour les bambous (sou), z pour les honneurs, numérotés de 1 à 7 (Est, Sud, Ouest, Nord, blanc, vert, rouge). Un 0 désigne le cinq rouge.') }}</p>
    <p>{{ __('Ainsi, 123m 406p 789s 555z 11z se lit : 1-2-3 de caractères, 4-5 rouge-6 de cercles, 7-8-9 de bambous, trois dragons blancs et une paire d’Est.') }}</p>
    <div class="felt guide-felt"><x-tile-row tiles="123m 406p 789s 555z 11z" size="clamp(1.15rem, 5vw, 2.2rem)" /></div>
    <p>{{ __('Vous savez lire les tuiles : place au jeu.') }} <a href="{{ localized_route('learn.game') }}">{{ __('Comment se déroule une partie') }}</a>.</p>
</x-guide>
