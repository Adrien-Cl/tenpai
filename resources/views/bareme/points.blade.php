{{--
    Points : le calculateur, puis le tableau allégé (un chiffre par case, la zone des limites en une bande).
    Gagnant et mode de victoire sont partagés entre le calculateur et le tableau.
--}}
@php
    $training ??= false;
    $variants = [[false, false], [false, true], [true, false], [true, true]];
    $calculatorId = 'points-data-'.($training ? 'training' : 'page');
@endphp
<div x-data="pointsCalculator({ source: '{{ $calculatorId }}' })">
    <script type="application/json" id="{{ $calculatorId }}">@json($sheet->calculator())</script>

    {{-- Qui gagne, et comment : ces deux choix pilotent le calculateur et le tableau. --}}
    <div class="flex flex-wrap gap-x-6 gap-y-3">
        <div class="flex items-center gap-3">
            <span class="text-sm text-ink-muted">{{ __('Gagnant') }}</span>
            <div class="inline-flex rounded-lg bg-paper-sunk p-1" role="radiogroup" aria-label="{{ __('Gagnant') }}">
                <button type="button" role="radio" class="rounded-md px-3.5 py-1.5 font-bold transition-colors" :class="!dealer ? 'bg-ink text-paper' : 'text-ink-muted hover:text-ink'" :aria-checked="(!dealer).toString()" x-on:click="dealer = false">{{ __('Non-donneur') }}</button>
                <button type="button" role="radio" class="rounded-md px-3.5 py-1.5 font-bold transition-colors" :class="dealer ? 'bg-ink text-paper' : 'text-ink-muted hover:text-ink'" :aria-checked="dealer.toString()" x-on:click="dealer = true">{{ __('Donneur') }}</button>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm text-ink-muted">{{ __('Victoire') }}</span>
            <div class="inline-flex rounded-lg bg-paper-sunk p-1" role="radiogroup" aria-label="{{ __('Victoire') }}">
                <button type="button" role="radio" class="rounded-md px-3.5 py-1.5 font-bold transition-colors" :class="!tsumo ? 'bg-ink text-paper' : 'text-ink-muted hover:text-ink'" :aria-checked="(!tsumo).toString()" x-on:click="tsumo = false">Ron</button>
                <button type="button" role="radio" class="rounded-md px-3.5 py-1.5 font-bold transition-colors" :class="tsumo ? 'bg-ink text-paper' : 'text-ink-muted hover:text-ink'" :aria-checked="tsumo.toString()" x-on:click="tsumo = true">Tsumo</button>
            </div>
        </div>
    </div>

    {{-- Le calculateur. En entraînement, il attend que l'étape des points soit validée. --}}
    <div class="mt-6 rounded-xl bg-paper-sunk p-4 sm:p-6"
         @if ($training) x-show="$wire.results[4] !== undefined" x-cloak @endif>
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_17rem] lg:gap-8">
            <div class="space-y-4">
                <div>
                    <p class="mb-2 text-sm text-ink-muted">{{ __('Han, dora compris') }}</p>
                    <div class="flex flex-wrap gap-1.5" role="radiogroup" aria-label="Han">
                        @foreach (\App\View\ScoringSheet::HAN_CHOICES as [$value, $label])
                            <button type="button" role="radio" class="calc-chip" :aria-checked="(han === {{ $value }}).toString()" x-on:click="han = {{ $value }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="mb-2 text-sm text-ink-muted">Fu <span x-show="han >= 5" class="ml-1">{{ __(': ils ne comptent plus au-delà de 4 han') }}</span></p>
                    <div class="flex flex-wrap gap-1.5" role="radiogroup" aria-label="Fu">
                        @foreach (\App\View\ScoringSheet::FU_ROWS as $value)
                            <button type="button" role="radio" class="calc-chip" :disabled="han >= 5" :aria-checked="(han < 5 && fu === {{ $value }}).toString()" x-on:click="fu = {{ $value }}">{{ $value }}</button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex flex-col justify-center border-t border-paper-line pt-4 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-8" aria-live="polite">
                <template x-if="result.possible">
                    <div>
                        <p class="text-sm text-ink-muted" x-text="caption"></p>
                        <p class="display num mt-1 text-[2.6rem] leading-none" x-text="result.value"></p>
                        <p class="mt-2 text-sm font-bold text-gilt-ink" x-show="result.limit" x-text="result.limit"></p>
                    </div>
                </template>
                <template x-if="!result.possible">
                    <p class="text-ink-muted" x-text="impossible"></p>
                </template>
            </div>
        </div>
        <div class="mt-4 space-y-1 border-t border-paper-line pt-3 text-sm text-ink-muted" x-show="result.possible">
            <template x-for="line in result.formula ?? []"><p x-html="line"></p></template>
        </div>
    </div>

    {{-- Le tableau : un chiffre par case, selon le gagnant et le mode de victoire. --}}
    @foreach ($variants as [$dealer, $tsumo])
        <div x-show="dealer === {{ $dealer ? 'true' : 'false' }} && tsumo === {{ $tsumo ? 'true' : 'false' }}" @if ($dealer || $tsumo) x-cloak @endif>
            <p class="mt-8 text-sm text-ink-muted">
                @if (! $tsumo)
                    {{ $dealer ? __('Ron du donneur : ce que paie celui qui a jeté la tuile.') : __('Ron d’un non-donneur : ce que paie celui qui a jeté la tuile.') }}
                @elseif ($dealer)
                    {{ __('Tsumo du donneur : ce que paie chaque adversaire.') }}
                @else
                    {{ __('Tsumo d’un non-donneur : en haut ce que paie chaque non-donneur, en dessous ce que paie le donneur.') }}
                @endif
            </p>
            <div class="-mx-1 mt-3 overflow-x-auto px-1">
                <table class="points-grid w-full table-fixed border-separate border-spacing-0 text-right">
                    <caption class="sr-only">{{ match (true) {
                        $tsumo && $dealer => __('Points en tsumo du donneur, selon les han et les fu'),
                        $tsumo => __('Points en tsumo d’un non-donneur, selon les han et les fu'),
                        $dealer => __('Points en ron du donneur, selon les han et les fu'),
                        default => __('Points en ron d’un non-donneur, selon les han et les fu'),
                    } }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="w-[13%] pb-2 text-left text-sm font-medium text-ink-muted sm:w-[18%]">Fu</th>
                            @foreach ([1, 2, 3, 4] as $han)
                                <th scope="col" class="pb-2 text-sm font-medium text-ink-muted">{{ $han }} han</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sheet->pointGrid($dealer, $tsumo) as $row)
                            <tr>
                                <th scope="row" class="border-t border-paper-line py-2 text-left font-normal">
                                    <span class="display num text-[0.8rem] sm:text-lg">{{ $row['fu'] }}</span> <span class="hidden text-sm text-ink-muted sm:inline">fu</span>
                                </th>
                                @foreach ($row['cells'] as $cell)
                                    @if ($cell['value'] === null)
                                        <td class="border-t border-paper-line px-2 py-2 text-ink-faint"><span aria-label="{{ __('impossible') }}">·</span></td>
                                    @else
                                        <td class="border-t border-paper-line px-1 py-2 sm:px-2"
                                            :class="han === {{ $cell['han'] }} && fu === {{ $row['fu'] }} && 'is-picked'"
                                            x-on:click="han = {{ $cell['han'] }}; fu = {{ $row['fu'] }}">
                                            <span class="display num block text-[0.8rem] sm:text-lg">{{ $cell['value'] }}</span>
                                            @if ($cell['detail'])
                                                <span class="num block text-[0.7rem] opacity-70 sm:text-xs">{{ $cell['detail'] }}</span>
                                            @endif
                                        </td>
                                    @endif
                                @endforeach
                                @if ($row['limit'])
                                    <td colspan="{{ $row['limit']['span'] }}" class="border-t border-paper-line py-1.5 pl-2">
                                        <span class="limit-band" x-on:click="han = {{ $row['limit']['from'] }}; fu = {{ $row['fu'] }}"
                                              :class="han >= {{ $row['limit']['from'] }} && han < 5 && fu === {{ $row['fu'] }} && 'is-picked'">
                                            <span class="hidden text-sm sm:inline">{{ $row['limit']['label'] }}</span>
                                            <span class="ml-auto text-right leading-tight">
                                                <span class="display num block text-[0.8rem] sm:text-lg">{{ $row['limit']['value'] }}</span>
                                                @if ($row['limit']['detail'])
                                                    <span class="num block text-[0.7rem] opacity-70 sm:text-xs">{{ $row['limit']['detail'] }}</span>
                                                @endif
                                            </span>
                                        </span>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <h3 class="display mt-8 text-lg">{{ __('Au-delà de 4 han, les fu ne comptent plus') }}</h3>
            @php($limits = $sheet->limitLine($dealer, $tsumo))
            <ul @class(['mt-3 grid grid-cols-2 gap-2', 'sm:grid-cols-5' => count($limits) === 5, 'sm:grid-cols-4' => count($limits) === 4])>
                @foreach ($limits as $limit)
                    <li class="rounded-lg bg-paper-sunk px-3 py-2.5">
                        <span class="block font-bold">{{ $limit['label'] }}</span>
                        <span class="display num block text-lg">{{ $limit['value'] }}</span>
                        @if ($limit['detail'])
                            <span class="num block text-xs text-ink-muted">{{ __('donneur : :points', ['points' => $limit['detail']]) }}</span>
                        @endif
                        <span class="block text-xs text-ink-muted">{{ $limit['condition'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach

    <p class="mt-6 text-sm text-ink-muted">
        {!! __('Le calcul : fu × 2<sup>han + 2</sup> donne les points de base, plafonnés à :cap (mangan).', ['cap' => \App\Support\Format::points(2000)]) !!}
        {{ __('Ron : base × 4, ou × 6 pour le donneur. Tsumo : chaque non-donneur paie la base, le donneur le double.') }}
        {{ __('Chaque paiement est arrondi à la centaine supérieure.') }}
        @if ($sheet->rules->kiriage) {{ __('Kiriage : 4 han 30 fu et 3 han 60 fu comptent comme mangan.') }} @endif
        @if ($sheet->rules->honba) {{ __('Honba : 300 de plus en ron, 100 de plus par payeur en tsumo. Chaque bâton de riichi sur la table : 1 000 pour le gagnant.') }} @endif
    </p>
</div>
