<x-guide :guide="$guide" :toc="[
    ['but', __('Le but du jeu')],
    ['table', __('Avant la première donne')],
    ['tour', __('Un tour de jeu')],
    ['appels', __('Les appels : chii, pon, kan')],
    ['riichi', __('Tenpai et riichi')],
    ['gagner', __('Gagner : tsumo ou ron')],
    ['fin-donne', __('La fin d’une donne')],
    ['partie', __('Manches et fin de partie')],
]">
    <x-slot:intro>{{ __('Une partie de riichi enchaîne des donnes courtes : chacun pioche et défausse à tour de rôle, jusqu’à ce qu’un joueur complète sa main. Voici tout ce qui se passe à la table, dans l’ordre.') }}</x-slot:intro>

    <h2 id="but">{{ __('Le but du jeu') }}</h2>
    <p>{{ __('Chaque joueur garde 13 tuiles en main. Pour gagner, il faut en réunir 14 qui forment quatre groupes et une paire. Un groupe est une suite (trois tuiles qui se suivent dans la même famille) ou un brelan (trois tuiles identiques).') }}</p>
    <div class="felt guide-felt"><x-tile-row tiles="234m 678p 567s 777z 99m" size="clamp(1.15rem, 5vw, 2.2rem)" /></div>
    <p>{{ __('Ici : trois suites, un brelan de dragons rouges et une paire de 9 de caractères. Deux mains font exception à cette forme : les sept paires (chiitoitsu) et les treize orphelins (kokushi musou).') }}</p>
    <p class="guide-note">{{ __('La règle d’or du riichi : une main gagnante doit contenir au moins un yaku, une combinaison reconnue. Quatre groupes et une paire ne suffisent pas.') }}</p>

    <h2 id="table">{{ __('Avant la première donne') }}</h2>
    <ul>
        <li>{{ __('Quatre joueurs, qui commencent en général avec 25 000 points chacun, sous forme de bâtons.') }}</li>
        <li>{{ __('Chaque siège a un vent. L’Est est le donneur (oya) : il gagne et paie une fois et demie plus que les autres. Sud, Ouest et Nord suivent dans le sens inverse des aiguilles d’une montre, qui est aussi le sens du jeu.') }}</li>
        <li>{{ __('Les 136 tuiles, mélangées face cachée, forment un mur carré de deux étages. On l’ouvre à un endroit tiré aux dés.') }}</li>
        <li>{{ __('Les 14 dernières tuiles forment le mur mort (wanpai). On n’y pioche pas, sauf après un carré ; on y retourne l’indicateur de dora.') }}</li>
        <li>{{ __('Chaque joueur reçoit 13 tuiles. Le donneur commence en piochant la quatorzième.') }}</li>
    </ul>

    <h2 id="tour">{{ __('Un tour de jeu') }}</h2>
    <p>{{ __('À son tour, un joueur fait deux gestes :') }}</p>
    <ol>
        <li>{{ __('Il pioche une tuile dans le mur (tsumo). Il a alors 14 tuiles.') }}</li>
        <li>{{ __('Il en défausse une, face visible, devant lui. Les défausses se rangent par lignes de six, dans l’ordre : tout le monde peut les lire.') }}</li>
    </ol>
    <p>{{ __('Puis c’est au joueur suivant, à sa droite. Les défausses ne sont pas qu’un tas : elles disent ce que chacun attend, et décident du furiten.') }}</p>

    <h2 id="appels">{{ __('Les appels : chii, pon, kan') }}</h2>
    <p>{{ __('Une tuile qui vient d’être défaussée peut être prise par un autre joueur pour compléter un groupe. On l’annonce à voix haute, puis on pose le groupe face visible à droite de sa main.') }}</p>
    <ul>
        <li>{{ __('Chii : compléter une suite. Seulement avec la défausse du joueur de gauche, celui qui joue juste avant vous.') }}</li>
        <li>{{ __('Pon : compléter un brelan, avec la défausse de n’importe quel joueur. Le pon passe avant le chii.') }}</li>
        <li>{{ __('Kan : un carré de quatre tuiles identiques. Il peut être pris sur une défausse, ajouté à un pon déjà posé, ou fermé (les quatre tuiles venant de sa propre pioche). Après un kan, on pioche une tuile de remplacement dans le mur mort et un nouvel indicateur de dora est retourné.') }}</li>
    </ul>
    <div class="felt guide-felt flex flex-wrap items-end gap-x-8 gap-y-4">
        <span><span class="mb-2 block text-sm text-felt-muted">Chii</span><x-tile-row tiles="345p" size="2rem" /></span>
        <span><span class="mb-2 block text-sm text-felt-muted">Pon</span><x-tile-row tiles="666z" size="2rem" /></span>
        <span><span class="mb-2 block text-sm text-felt-muted">Kan</span><x-tile-row tiles="8888s" size="2rem" /></span>
    </div>
    <p>{{ __('Appeler fait gagner du temps, mais la main devient ouverte. Plusieurs yaku exigent une main fermée (riichi, pinfu, menzen tsumo…), et d’autres perdent un han. Un débutant gagne souvent à garder sa main fermée.') }}</p>

    <h2 id="riichi">{{ __('Tenpai et riichi') }}</h2>
    <p>{{ __('Une main est tenpai quand il ne lui manque plus qu’une tuile pour gagner. Les tuiles qui la complètent sont ses attentes.') }}</p>
    <p>{{ __('Avec une main fermée et tenpai, on peut déclarer riichi : on annonce « riichi », on pose un bâton de 1 000 points sur la table et on tourne sa défausse sur le côté. Dès lors, on ne change plus sa main : on défausse chaque tuile piochée, sauf celle qui fait gagner.') }}</p>
    <p>{{ __('En échange, riichi est un yaku. Il offre aussi l’ippatsu (gagner dans le tour qui suit) et les ura-dora. C’est la façon la plus simple d’avoir un yaku, d’où le nom du jeu.') }}</p>
    <p>{{ __('Attention au furiten : si l’une de vos attentes figure dans vos propres défausses, vous ne pouvez plus gagner sur la défausse d’un autre, seulement en piochant vous-même.') }} <a href="{{ localized_route('waits') }}" wire:navigate>{{ __('Exercez-vous à trouver les attentes') }}</a>.</p>

    <h2 id="gagner">{{ __('Gagner : tsumo ou ron') }}</h2>
    <ul>
        <li>{{ __('Tsumo : on gagne avec la tuile qu’on vient de piocher. Les trois autres joueurs paient, le donneur plus que les autres.') }}</li>
        <li>{{ __('Ron : on gagne avec la tuile qu’un adversaire vient de défausser. Ce joueur paie seul la totalité.') }}</li>
    </ul>
    <p>{{ __('On montre sa main, puis on la compte : yaku, han, dora, fu et points. C’est l’objet du guide suivant.') }} <a href="{{ localized_route('learn.scoring') }}">{{ __('Calculer les points') }}</a>.</p>

    <h2 id="fin-donne">{{ __('La fin d’une donne') }}</h2>
    <p>{{ __('Une donne s’arrête quand un joueur gagne, ou quand le mur est épuisé sans gagnant : c’est une donne nulle (ryuukyoku). Les joueurs tenpai montrent alors leur main, et les joueurs noten leur paient 3 000 points en tout.') }}</p>
    <p>{{ __('Si le donneur a gagné, ou s’il était tenpai à la donne nulle, il garde la donne (renchan) et pose un compteur, le honba : chaque honba ajoute 300 points au gain suivant. Sinon, la donne passe au joueur de droite et les vents tournent.') }}</p>

    <h2 id="partie">{{ __('Manches et fin de partie') }}</h2>
    <p>{{ __('Quand chaque joueur a été donneur au moins une fois, la manche est finie. La partie standard (hanchan) compte une manche Est puis une manche Sud ; la partie courte (tonpuusen), seulement la manche Est. Le vent de la manche compte pour les yaku : un brelan d’Est pendant la manche Est en est un.') }}</p>
    <p>{{ __('À la dernière donne, le joueur qui a le plus de points gagne. En club et en tournoi, on ajoute souvent l’uma, un bonus selon la place. Selon les règles, la partie s’arrête aussi dès qu’un joueur passe sous zéro.') }}</p>
    <p class="guide-note">{{ __('À retenir : piocher, défausser ; une main fermée et un riichi pour commencer ; tsumo si on pioche la bonne tuile, ron si un autre la jette.') }}</p>
</x-guide>
