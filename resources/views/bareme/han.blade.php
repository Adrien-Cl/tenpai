{{-- Han : les yaku, à chercher par leur nom, en main fermée ou ouverte ; puis les dora. --}}
@php
    $entries = array_values(array_filter(\App\View\ScoringSheet::yakuEntries(), fn ($e) => ! $e['yakuman']));
@endphp
<div x-data="yakuSheet({ entries: @js($entries) })">
    <div class="flex flex-wrap items-center gap-3">
        <label class="relative min-w-0 flex-1 basis-60">
            <span class="sr-only">{{ __('Chercher un yaku') }}</span>
            <input type="search" x-model="query" placeholder="{{ __('Chercher un yaku : pinfu, tout simples, double suite…') }}"
                   autocomplete="off" autocapitalize="off" spellcheck="false"
                   class="w-full rounded-xl bg-paper-sunk px-4 py-2.5 shadow-[inset_0_0_0_1px_var(--paper-line)] placeholder:text-ink-faint focus:shadow-[inset_0_0_0_2px_var(--ink)] focus:outline-none">
        </label>
        <div class="inline-flex rounded-lg bg-paper-sunk p-1" role="radiogroup" aria-label="{{ __('Main') }}">
            <button type="button" role="radio" class="rounded-md px-3.5 py-1.5 font-bold transition-colors" :class="!open ? 'bg-ink text-paper' : 'text-ink-muted hover:text-ink'" :aria-checked="(!open).toString()" x-on:click="open = false">{{ __('Main fermée') }}</button>
            <button type="button" role="radio" class="rounded-md px-3.5 py-1.5 font-bold transition-colors" :class="open ? 'bg-ink text-paper' : 'text-ink-muted hover:text-ink'" :aria-checked="open.toString()" x-on:click="open = true">{{ __('Main ouverte') }}</button>
        </div>
    </div>
    <p class="mt-2 min-h-5 text-sm text-ink-muted" aria-live="polite" x-text="hint"></p>

    @foreach ([false, true] as $open)
        <div x-show="open === {{ $open ? 'true' : 'false' }}" @if ($open) x-cloak @endif class="mt-2 space-y-6">
            @foreach ($sheet->yakuByHan($open) as $han => $yakuList)
                @php($ids = array_map(fn ($y) => $y->value, $yakuList))
                <section x-show="anyVisible(@js($ids))">
                    <h3 class="display text-lg">
                        @if ($han === 0)
                            {{ __('Main fermée seulement') }}
                        @else
                            {{ $han }} han
                        @endif
                    </h3>
                    <ul class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($yakuList as $yaku)
                            <li x-show="visible('{{ $yaku->value }}')"
                                @class(['yaku-card', 'opacity-60' => $han === 0])>
                                <span class="flex items-baseline justify-between gap-3">
                                    <b>{{ $yaku->shortLabel() }}</b>
                                    @if ($open && $yaku->losesHanWhenOpen())
                                        <span class="shrink-0 text-xs text-ko">{{ __(':han fermée', ['han' => $yaku->closedHan()]) }}</span>
                                    @endif
                                </span>
                                <span class="block text-sm text-ink-muted">{{ $yaku->gloss() }}</span>
                                <span class="mt-1.5 block text-sm">{{ $yaku->description() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    @endforeach
</div>

<div class="mt-10 grid gap-8 border-t border-paper-line pt-8 md:grid-cols-2">
    <div>
        <h3 class="display text-lg">{{ __('Dora : la tuile suivante') }}</h3>
        <ul class="mt-4 space-y-3">
            @foreach ([__('Chiffres') => [7, 8, 0], __('Vents') => [27, 28, 29, 30, 27], __('Dragons') => [31, 32, 33, 31]] as $label => $chain)
                <li class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="w-20 text-sm text-ink-muted">{{ $label }}</span>
                    <span class="flex items-center gap-1.5">
                        @foreach ($chain as $tile)
                            <span class="mini tiles"><x-tile :tile="$tile" /></span>
                            @if (! $loop->last)<span class="text-ink-faint" aria-hidden="true">›</span>@endif
                        @endforeach
                    </span>
                </li>
            @endforeach
        </ul>
        <ul class="mt-5 space-y-1.5 text-sm">
            <li>{!! __('<b>Dora</b> : 1 han par tuile, mais ce n’est pas un yaku. Il faut au moins un yaku pour gagner.') !!}</li>
            <li>{!! __('<b>Kan</b> : chaque carré révèle un indicateur de plus.') !!}</li>
            <li>{!! __('<b>Ura-dora</b> : seulement après un riichi, sous chaque indicateur.') !!}</li>
            @if ($sheet->rules->aka)
                <li>{!! __('<b>Aka-dora</b> : chaque cinq rouge vaut 1 dora.') !!}</li>
            @endif
        </ul>
    </div>
    <div>
        <h3 class="display text-lg">Yakuman</h3>
        <p class="mt-1 text-sm text-ink-muted">{{ __('Les mains limites. Tenpai n’en distribue jamais, mais il faut les reconnaître.') }}</p>
        <ul class="mt-3 grid grid-cols-1 gap-x-6 gap-y-1.5 text-sm sm:grid-cols-2">
            @foreach ($sheet->yakuman() as $yakuman)
                <li><b>{{ $yakuman->label() }}</b> <span class="text-ink-muted">{{ $yakuman->gloss() }}</span></li>
            @endforeach
        </ul>
    </div>
</div>
