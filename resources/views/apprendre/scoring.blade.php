@use('App\Domain\Mahjong\Yaku')
@use('App\Domain\Mahjong\Limit')
@use('App\Domain\Mahjong\Wait')
@use('App\Domain\Mahjong\Rules')
@use('App\Domain\Mahjong\Scoring\PointsCalculator')
@use('App\Support\Format')
@php
    $evaluation = $example['evaluation'];
    $payment = $evaluation->payment();
    $rules = Rules::default();
    $firstYaku = [Yaku::Riichi, Yaku::MenzenTsumo, Yaku::Tanyao, Yaku::Pinfu, Yaku::Chun, Yaku::Iipeikou, Yaku::Toitoi, Yaku::Honitsu];
    $ron = fn (int $han, int $fu, bool $dealer): string => Format::points(PointsCalculator::points($han, $fu, $dealer, false, $rules)->ron);
@endphp
<x-guide :guide="$guide" :toc="[
    ['etapes', __('La méthode en cinq étapes')],
    ['yaku', __('1. Les yaku')],
    ['han', __('2. Les han')],
    ['dora', __('3. Les dora')],
    ['fu', __('4. Les fu')],
    ['points', __('5. Les points')],
    ['exemple', __('Une main comptée de bout en bout')],
    ['memo', __('Les valeurs à connaître')],
]">
    <x-slot:intro>{{ __('Au riichi, c’est le gagnant qui annonce ce que vaut sa main. Le calcul paraît obscur, mais il suit toujours le même chemin : on y arrive sans calculette avec un peu d’entraînement.') }}</x-slot:intro>

    <h2 id="etapes">{{ __('La méthode en cinq étapes') }}</h2>
    <p>{{ __('La valeur d’une main dépend de deux nombres : les han, qui mesurent ce qu’elle a de remarquable, et les fu, qui mesurent sa composition. On les trouve dans cet ordre :') }}</p>
    <ol>
        <li>{{ __('Repérer les yaku de la main.') }}</li>
        <li>{{ __('Additionner leurs han, main ouverte ou fermée.') }}</li>
        <li>{{ __('Ajouter un han par dora, cinq rouge et ura-dora.') }}</li>
        <li>{{ __('Compter les fu, puis arrondir à la dizaine.') }}</li>
        <li>{{ __('En déduire les points, ou la limite atteinte, et ce que paie chaque joueur.') }}</li>
    </ol>
    <p>{{ __('Ce sont exactement les cinq étapes de l’entraînement de Tenpai.') }}</p>

    <h2 id="yaku">{{ __('1. Les yaku') }}</h2>
    <p>{{ __('Un yaku est une combinaison reconnue : sans yaku, pas de victoire, même avec quatre groupes et une paire. Il en existe une quarantaine, mais ceux-ci couvrent l’essentiel des mains d’un débutant :') }}</p>
    <ul class="grid gap-2 sm:grid-cols-2">
        @foreach ($firstYaku as $yaku)
            <li class="yaku-card">
                <span class="flex items-baseline justify-between gap-3">
                    <b>{{ $yaku->label() }}</b>
                    <span class="num shrink-0 text-sm font-bold text-shu">
                        @if ($yaku->openHan() === null)
                            {{ __(':han han, fermée', ['han' => $yaku->closedHan()]) }}
                        @elseif ($yaku->openHan() === $yaku->closedHan())
                            {{ $yaku->closedHan() }} han
                        @else
                            {{ __(':closed han (:open ouverte)', ['closed' => $yaku->closedHan(), 'open' => $yaku->openHan()]) }}
                        @endif
                    </span>
                </span>
                <span class="block text-sm text-ink-muted">{{ $yaku->gloss() }}</span>
                <span class="mt-1.5 block text-sm">{{ $yaku->description() }}</span>
            </li>
        @endforeach
    </ul>
    <p>{{ __('Tous les autres, avec leur valeur ouverte et fermée, sont dans le barème.') }} <a href="{{ localized_route('scoring') }}">{{ __('Voir tous les yaku') }}</a>.</p>

    <h2 id="han">{{ __('2. Les han') }}</h2>
    <p>{{ __('Chaque yaku vaut un nombre de han, et ils s’additionnent : une main en riichi, tout simples et pinfu vaut 3 han. Certains yaku valent un han de moins quand la main est ouverte (honitsu, chanta, ittsu…), d’autres ne sont permis qu’en main fermée.') }}</p>
    <p>{{ __('Les yakuman, comme les treize orphelins, sont à part : ils valent directement la limite maximale.') }}</p>

    <h2 id="dora">{{ __('3. Les dora') }}</h2>
    <p>{{ __('Une fois qu’on a un yaku, chaque dora ajoute un han : la tuile qui suit l’indicateur, chaque cinq rouge, et en riichi, les ura-dora. Une main peut donc passer de 1 à 4 han grâce à ses dora.') }} <a href="{{ localized_route('learn.tiles') }}#dora">{{ __('Comment lire l’indicateur de dora') }}</a>.</p>

    <h2 id="fu">{{ __('4. Les fu') }}</h2>
    <p>{{ __('Les fu affinent la valeur des mains de moins de 5 han. On part de 20 fu, puis on ajoute :') }}</p>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[30rem] text-left text-sm">
            <thead class="text-ink-muted">
                <tr class="border-b border-paper-line"><th class="py-2 pr-4 font-medium">{{ __('Ce qui rapporte') }}</th><th class="py-2 font-medium">{{ __('Fu') }}</th></tr>
            </thead>
            <tbody class="[&>tr]:border-b [&>tr]:border-paper-line">
                @foreach ([
                    [__('Ron avec une main fermée'), '+10'],
                    [__('Tsumo (sauf pinfu)'), '+2'],
                    [__('Brelan de 2 à 8, ouvert / fermé'), '2 / 4'],
                    [__('Brelan de 1, de 9 ou d’honneurs, ouvert / fermé'), '4 / 8'],
                    [__('Carré : quatre fois le brelan'), '8 – 32'],
                    [__('Paire de dragons, de son vent ou du vent de la manche'), '+2'],
                    [__('Attente sur une seule tuile : kanchan, penchan, tanki'), '+2'],
                ] as [$what, $fu])
                    <tr><td class="py-2 pr-4">{{ $what }}</td><td class="num py-2 font-bold whitespace-nowrap">{{ $fu }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p>{{ __('Les suites ne rapportent rien. On arrondit ensuite à la dizaine supérieure : 32 fu deviennent 40. Deux cas fixes : les sept paires valent toujours 25 fu, et un pinfu vaut 20 fu en tsumo, 30 en ron.') }}</p>
    <h3>{{ __('Les attentes') }}</h3>
    <ul>
        @foreach (Wait::cases() as $wait)
            <li><b>{{ $wait->label() }}</b> ({{ $wait->gloss() }}) : {{ $wait->description() }}</li>
        @endforeach
    </ul>

    <h2 id="points">{{ __('5. Les points') }}</h2>
    <p>{{ __('Les points de base valent fu × 2 puissance (han + 2). Un non-donneur qui gagne en ron reçoit 4 fois cette base ; le donneur, 6 fois. En tsumo, chaque non-donneur paie la base une fois et le donneur deux fois. On arrondit chaque paiement à la centaine supérieure.') }}</p>
    <p>{{ __('Dès que la base atteint 2 000, la main est plafonnée : c’est le mangan. Au-delà, on ne regarde plus que les han :') }}</p>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[30rem] text-left text-sm">
            <thead class="text-ink-muted">
                <tr class="border-b border-paper-line">
                    <th class="py-2 pr-4 font-medium">{{ __('Limite') }}</th>
                    <th class="py-2 pr-4 font-medium">{{ __('Han') }}</th>
                    <th class="py-2 pr-4 text-right font-medium">{{ __('Non-donneur') }}</th>
                    <th class="py-2 text-right font-medium">{{ __('Donneur') }}</th>
                </tr>
            </thead>
            <tbody class="[&>tr]:border-b [&>tr]:border-paper-line">
                @foreach (Limit::cases() as $limit)
                    <tr>
                        <td class="py-2 pr-4 font-bold">{{ $limit->label() }}</td>
                        <td class="py-2 pr-4">{{ $limit->condition() }}</td>
                        <td class="num py-2 pr-4 text-right whitespace-nowrap">{{ Format::points($limit->base() * 4) }}</td>
                        <td class="num py-2 text-right whitespace-nowrap">{{ Format::points($limit->base() * 6) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td class="py-2 pr-4 font-bold">Yakuman</td>
                    <td class="py-2 pr-4">{{ __('Une main yakuman') }}</td>
                    <td class="num py-2 pr-4 text-right whitespace-nowrap">{{ Format::points(32000) }}</td>
                    <td class="num py-2 text-right whitespace-nowrap">{{ Format::points(48000) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2 id="exemple">{{ __('Une main comptée de bout en bout') }}</h2>
    <p>{{ __('Vous êtes au Sud (non-donneur) pendant la manche Est. Vous avez déclaré riichi avec cette main, qui attend le 6 de bambous ; l’indicateur de dora est le 1 de caractères. Un adversaire défausse le 6 : ron !') }}</p>
    <div class="felt guide-felt flex flex-wrap items-end gap-x-5 gap-y-4">
        <span class="tiles" style="--tile: clamp(1.2rem, 5vw, 2.2rem)">
            @foreach ($example['waiting'] as $tile)<x-tile :tile="$tile" />@endforeach
        </span>
        <span class="tiles" style="--tile: clamp(1.2rem, 5vw, 2.2rem)"><x-tile :tile="$example['hand']->win" win /></span>
        <span class="ml-auto flex items-end gap-2 text-sm text-felt-muted">{{ __('Indicateur') }} <x-tile-row tiles="1m" size="clamp(1.2rem, 5vw, 2.2rem)" /></span>
    </div>
    <ol>
        <li>
            <b>{{ __('Yaku') }}</b> :
            {{ collect($evaluation->yaku())->map(fn ($yaku) => $yaku->yaku->label().' ('.$yaku->han.' han)')->join(', ', ' '.__('et').' ') }}.
        </li>
        <li><b>{{ __('Han') }}</b> : {{ __(':han han de yaku.', ['han' => $evaluation->yakuHan()]) }}</li>
        <li><b>{{ __('Dora') }}</b> : {{ __('l’indicateur 1 de caractères désigne le 2 : la main en a :count. Total, :han han.', ['count' => $evaluation->doraHan(), 'han' => $evaluation->han]) }}</li>
        <li>
            <b>{{ __('Fu') }}</b> :
            <ul class="mt-2 space-y-1 text-sm">
                @foreach ($evaluation->reading()->fu->parts as $part)
                    @continue($part->fu === 0)
                    <li class="border-b border-paper-line pb-1">{{ $part->why }}</li>
                @endforeach
            </ul>
            <span class="mt-2 block">{{ __('Total : :fu fu.', ['fu' => $evaluation->fu()]) }}</span>
        </li>
        <li>
            <b>{{ __('Points') }}</b> :
            {{ __(':fu × 2 puissance :exponent = :base points de base ; × 4 en ron d’un non-donneur = :raw, arrondi à :points.', [
                'fu' => $evaluation->fu(),
                'exponent' => $evaluation->han + 2,
                'base' => Format::points($payment->base),
                'raw' => Format::points($payment->base * 4),
                'points' => Format::points($payment->ron),
            ]) }}
        </li>
    </ol>
    <p class="guide-note">{{ __('Résultat : :han han :fu fu, le joueur qui a défaussé le 6 paie :points points.', ['han' => $evaluation->han, 'fu' => $evaluation->fu(), 'points' => Format::points($payment->ron)]) }}</p>

    <h2 id="memo">{{ __('Les valeurs à connaître') }}</h2>
    <p>{{ __('À la table, personne ne refait la multiplication : on retient les montants les plus fréquents. Voici ce que rapporte un ron, sans kiriage mangan :') }}</p>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[30rem] text-left text-sm">
            <thead class="text-ink-muted">
                <tr class="border-b border-paper-line">
                    <th class="py-2 pr-4 font-medium">{{ __('Main') }}</th>
                    <th class="py-2 pr-4 text-right font-medium">{{ __('Non-donneur') }}</th>
                    <th class="py-2 text-right font-medium">{{ __('Donneur') }}</th>
                </tr>
            </thead>
            <tbody class="[&>tr]:border-b [&>tr]:border-paper-line">
                @foreach ([[1, 30], [2, 30], [3, 30], [4, 30], [1, 40], [2, 40], [3, 40]] as [$han, $fu])
                    <tr>
                        <td class="py-2 pr-4">{{ $han }} han {{ $fu }} fu</td>
                        <td class="num py-2 pr-4 text-right">{{ $ron($han, $fu, false) }}</td>
                        <td class="num py-2 text-right">{{ $ron($han, $fu, true) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p>{{ __('La table complète, pour toutes les combinaisons de han et de fu, est dans le barème. Le plus efficace reste de compter des mains : quelques dizaines suffisent pour que le calcul devienne un réflexe.') }} <a href="{{ localized_route('counting', ['niveau' => \App\Domain\Mahjong\Generation\Level::Beginner->value]) }}" wire:navigate>{{ __('Compter une main facile') }}</a>.</p>
</x-guide>
