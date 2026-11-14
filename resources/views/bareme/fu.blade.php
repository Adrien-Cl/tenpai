{{-- Fu : la recette, dans l'ordre où on les compte, avec de vraies tuiles en exemple. --}}
@php
    $tiles = fn (string $notation) => \App\Domain\Mahjong\Tile::parse($notation);
    $groups = [
        [__('Brelan de 2 à 8'), '555p', 2, 4],
        [__('Brelan de 1, de 9 ou d’honneurs'), '999m', 4, 8],
        [__('Carré de 2 à 8'), '5555s', 8, 16],
        [__('Carré de 1, de 9 ou d’honneurs'), '7777z', 16, 32],
        [__('Suite'), '345m', 0, 0],
    ];
@endphp
<ol class="fu-recipe space-y-1">
    <li>
        <span class="fu-step">1</span>
        <div class="min-w-0 flex-1">
            <p class="font-bold">{{ __('Partir de 20 fu') }}</p>
            <p class="text-sm text-ink-muted">{{ __('Toute main normale a une base de 20 fu (fuutei).') }}</p>
        </div>
        <span class="fu-value">20</span>
    </li>

    <li>
        <span class="fu-step">2</span>
        <div class="min-w-0 flex-1">
            <p class="font-bold">{{ __('Ajouter la façon de gagner') }}</p>
            <ul class="mt-2 grid gap-1.5 text-sm sm:grid-cols-3">
                <li class="rounded-lg bg-paper-sunk px-3 py-2"><b class="display num mr-1">+10</b> {{ __('ron, main fermée') }}</li>
                <li class="rounded-lg bg-paper-sunk px-3 py-2"><b class="display num mr-1">+2</b> {{ __('tsumo, sauf en pinfu') }}</li>
                <li class="rounded-lg bg-paper-sunk px-3 py-2"><b class="display num mr-1">0</b> {{ __('ron, main ouverte') }}</li>
            </ul>
        </div>
    </li>

    <li>
        <span class="fu-step">3</span>
        <div class="min-w-0 flex-1">
            <p class="font-bold">{{ __('Ajouter chaque groupe') }}</p>
            <table class="mt-2 w-full text-sm">
                <thead>
                    <tr class="text-ink-muted">
                        <th class="pb-1 text-left font-normal"><span class="sr-only">{{ __('Groupe') }}</span></th>
                        <th class="w-20 pb-1 text-right font-normal">{{ __('ouvert') }}</th>
                        <th class="w-20 pb-1 text-right font-normal">{{ __('fermé') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groups as [$label, $notation, $open, $closed])
                        <tr class="border-t border-paper-line">
                            <td class="py-2">
                                <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <span class="mini tiles">@foreach ($tiles($notation) as $t)<x-tile :tile="$t" />@endforeach</span>
                                    <span>{{ $label }}</span>
                                </span>
                            </td>
                            <td class="display num py-2 text-right text-lg">{{ $open }}</td>
                            <td class="display num py-2 text-right text-lg">{{ $closed }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="mt-2 text-sm text-ink-muted">{{ __('Fermé vaut le double d’ouvert, et un carré quatre fois un brelan. Un brelan complété par ron compte comme ouvert.') }}</p>
        </div>
    </li>

    <li>
        <span class="fu-step">4</span>
        <div class="min-w-0 flex-1">
            <p class="font-bold">{{ __('Ajouter la paire, si elle a de la valeur') }}</p>
            <ul class="mt-2 space-y-1.5 text-sm">
                <li class="flex flex-wrap items-center gap-3">
                    <span class="mini tiles">@foreach ($tiles('77z') as $t)<x-tile :tile="$t" />@endforeach</span>
                    <span>{{ __('Dragons, votre vent ou le vent de la manche') }}</span>
                    <b class="display num ml-auto text-lg">+2</b>
                </li>
                <li class="flex flex-wrap items-center gap-3">
                    <span class="mini tiles">@foreach ($tiles('11z') as $t)<x-tile :tile="$t" />@endforeach</span>
                    <span>{{ __('Votre vent, s’il est aussi celui de la manche') }}</span>
                    <b class="display num ml-auto text-lg">+{{ $sheet->rules->doubleWind4 ? 4 : 2 }}</b>
                </li>
            </ul>
        </div>
    </li>

    <li>
        <span class="fu-step">5</span>
        <div class="min-w-0 flex-1">
            <p class="font-bold">{{ __('Ajouter l’attente, si elle est fermée') }}</p>
            <ul class="mt-2 grid gap-1.5 text-sm sm:grid-cols-2">
                @foreach ($sheet->waits() as $w)
                    <li class="flex items-center gap-3 rounded-lg bg-paper-sunk px-3 py-2">
                        <span class="mini tiles">@foreach ($tiles($w['waiting']) as $t)<x-tile :tile="$t" />@endforeach</span>
                        <span class="text-ink-muted" aria-hidden="true">←</span>
                        <span class="mini tiles opacity-80">@foreach ($tiles($w['completes']) as $t)<x-tile :tile="$t" />@endforeach</span>
                        <span><b>{{ $w['wait']->label() }}</b> <span class="text-ink-muted">{{ $w['wait']->gloss() }}</span></span>
                        <b class="display num ml-auto text-lg">{{ $w['wait']->fu() ? '+'.$w['wait']->fu() : '0' }}</b>
                    </li>
                @endforeach
            </ul>
        </div>
    </li>

    <li>
        <span class="fu-step">6</span>
        <div class="min-w-0 flex-1">
            <p class="font-bold">{{ __('Arrondir à la dizaine supérieure') }}</p>
            <p class="text-sm text-ink-muted">{{ __('32 devient 40, 40 reste 40.') }}</p>
        </div>
    </li>
</ol>

<div class="mt-6 rounded-xl border-2 border-dashed border-paper-line px-4 py-3">
    <p class="font-bold">{{ __('Les cas qui ne se comptent pas') }}</p>
    <ul class="mt-2 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
        <li>{!! __('<b>Chiitoitsu</b> : toujours 25 fu, jamais arrondi.') !!}</li>
        <li>{!! __('<b>Pinfu en tsumo</b> : 20 fu tout rond.') !!}</li>
        <li>{!! __('<b>Pinfu en ron</b> : 30 fu.') !!}</li>
        <li>{!! __('<b>Main ouverte à 20 fu</b> : portée à 30 fu.') !!}</li>
    </ul>
</div>
