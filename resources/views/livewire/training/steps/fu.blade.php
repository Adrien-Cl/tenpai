{{-- Étape 4 : les fu, ligne par ligne. Survoler une ligne éclaire son groupe sur le tapis. --}}
@php
    $evaluation = $hand->evaluation;
    $reading = $evaluation->reading();
    $h = $hand->hand();
    $fields = $result['fields'] ?? [];
    $tiles = $reading->chiitoitsu ? [] : $hand->fuTiles();
@endphp

@if ($reading->chiitoitsu)
    <x-answer-row placeholder="0" id="f-fu-final" model="answers.fu.final" :title="__('Fu de la main')" :sub="__('sept paires')" :field="$fields['final'] ?? null" />
@else
    <div>
        @foreach ($reading->fu->parts as $part)
            @if ($part->key === 'base')
                <x-answer-row placeholder="0" wire:key="fu-base" id="f-fu-base" model="answers.fu.base" :title="__('Base')" sub="fuutei" :field="$fields['base'] ?? null" />
            @elseif ($part->key === 'win')
                <x-answer-row placeholder="0" wire:key="fu-win" id="f-fu-win" model="answers.fu.win" :title="$h->tsumo ? __('Victoire par tsumo') : __('Victoire par ron')" :sub="$evaluation->menzen ? __('main fermée') : __('main ouverte')" :field="$fields['win'] ?? null" />
            @elseif (str_starts_with($part->key, 'g') || $part->key === 'pair')
                @php
                    $sub = $part->key === 'pair' ? __('paire') : ($part->group?->meld?->label() ?? __('dans la main'));
                @endphp
                <x-answer-row placeholder="0" wire:key="fu-{{ $part->key }}" :id="'f-fu-'.$part->key" :model="'answers.fu.'.$part->key" :sub="$sub" :field="$fields[$part->key] ?? null"
                              x-on:mouseenter="lit = '{{ $part->key }}'" x-on:mouseleave="lit = null" x-on:focusin="lit = '{{ $part->key }}'" x-on:focusout="lit = null"
                              class="-mx-2 rounded-lg px-2 transition-colors hover:bg-paper-sunk">
                    <span class="mini tiles">
                        @foreach ($tiles[$part->key] ?? [] as $t)
                            <x-tile :tile="$t['tile']" :red="$t['red']" :back="$t['back']" :class="$t['win'] ? 'tile--mark' : ''" />
                        @endforeach
                    </span>
                    <span class="sr-only">{{ implode(', ', array_map(fn ($t) => \App\Domain\Mahjong\Tile::name($t['tile'], $t['red']), $tiles[$part->key] ?? [])) }}</span>
                </x-answer-row>
            @elseif ($part->key === 'wait')
                @php
                    $chosen = $answers['fu']['waittype'] ?? null;
                    $waitField = $fields['waittype'] ?? null;
                @endphp
                <fieldset wire:key="fu-waittype" class="border-t border-paper-line py-3">
                    <legend class="float-left mb-2 w-full font-bold">{{ __('Type d’attente') }} <span class="font-normal text-ink-muted">{{ __('sur :tile', ['tile' => \App\Domain\Mahjong\Tile::name($h->win)]) }}</span>
                        @if ($waitField && $waitField['state'] === 'ok')<span class="ml-2 text-lg text-ok" aria-hidden="true">◎</span><span class="sr-only">{{ __('Juste') }}</span>@endif
                        @if ($waitField && $waitField['state'] === 'ko')<span class="ml-2 text-sm text-ko">→ {{ $waitField['expected'] }}</span>@endif
                    </legend>
                    <div class="clear-both flex flex-wrap gap-2">
                        @foreach (\App\Domain\Mahjong\Wait::cases() as $wait)
                            <label @class([
                                'choice',
                                'is-right' => $waitField && $wait === $reading->wait,
                                'is-wrong' => $waitField && $waitField['state'] === 'ko' && $chosen === $wait->value,
                            ])>
                                <input type="radio" class="sr-only" name="waittype" value="{{ $wait->value }}" wire:model="answers.fu.waittype" @disabled($waitField !== null)>
                                {{ $wait->label() }}<small>{{ $wait->gloss() }}</small>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <x-answer-row placeholder="0" wire:key="fu-wait" id="f-fu-wait" model="answers.fu.wait" :title="__('Fu d’attente')" :field="$fields['wait'] ?? null" />
            @endif
        @endforeach

        <x-answer-row placeholder="0" wire:key="fu-raw" id="f-fu-raw" model="answers.fu.raw" :title="__('Total avant arrondi')" :field="$fields['raw'] ?? null" class="mt-2 border-t-2! border-ink/70!" />
        <x-answer-row placeholder="0" wire:key="fu-final" id="f-fu-final" model="answers.fu.final" :title="__('Fu de la main')" :sub="__('après arrondi')" :field="$fields['final'] ?? null" />
    </div>
@endif

@if ($result)
    <p class="mt-4 font-bold">
        {{ $evaluation->fu() }} fu.
        @if ($evaluation->payment()->limit)
            <span class="font-normal text-ink-muted">{{ __('Au-delà du mangan, les fu ne changent plus les points, mais on les compte quand même.') }}</span>
        @endif
    </p>
@endif
