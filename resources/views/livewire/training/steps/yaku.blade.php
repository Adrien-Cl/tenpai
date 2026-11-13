{{-- Étape 1 : annoncer les yaku. --}}
@php
    $evaluation = $hand->evaluation;
    $correct = array_map(fn ($y) => $y->yaku, $evaluation->yaku());
@endphp

@if (! $result)
    <div x-data="yakuPicker({ entries: @js(\App\View\ScoringSheet::yakuEntries()), locked: false })"
         x-modelable="picked" wire:model="answers.yaku.picked" class="space-y-3">

        <div class="flex min-h-11 flex-wrap items-center gap-2" aria-label="Yaku annoncés">
            <template x-for="id in picked" :key="id">
                <button type="button" class="chip chip--picked" x-on:click="remove(id)" :aria-label="'Retirer ' + entry(id).label">
                    <span x-text="entry(id).label"></span><span class="opacity-60" aria-hidden="true">✕</span>
                </button>
            </template>
            <span x-show="!picked.length" class="text-ink-faint">Aucun yaku annoncé pour l’instant.</span>
        </div>

        <div class="relative">
            <label for="yaku-search" class="sr-only">Ajouter un yaku</label>
            <input id="yaku-search" x-ref="input" type="text" role="combobox"
                   aria-autocomplete="list" aria-controls="yaku-options" aria-describedby="yaku-hint"
                   :aria-expanded="(options.length > 0).toString()"
                   :aria-activedescendant="active >= 0 && options[active] ? 'yopt-' + options[active].id : null"
                   autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" enterkeyhint="done"
                   placeholder="Tapez un yaku : rii, tan, pinfu…"
                   x-model="query"
                   x-on:keydown.arrow-down.prevent="move(1)"
                   x-on:keydown.arrow-up.prevent="move(-1)"
                   x-on:keydown.enter="enter($event)"
                   x-on:keydown.escape="if (options.length) { $event.stopPropagation(); close() }"
                   x-on:keydown.backspace="backspace()"
                   x-on:blur="close()"
                   class="w-full rounded-xl bg-paper-sunk px-4 py-3 text-lg shadow-[inset_0_0_0_1px_var(--paper-line)] placeholder:text-ink-faint focus:shadow-[inset_0_0_0_2px_var(--ink),0_0_0_4px_rgb(227_167_47/0.35)] focus:outline-none">

            <ul id="yaku-options" role="listbox" aria-label="Suggestions" x-cloak x-show="options.length"
                x-on:pointerdown.prevent
                class="absolute inset-x-0 z-20 mt-2 max-h-80 overflow-y-auto rounded-xl bg-paper py-1.5 shadow-[0_18px_40px_-16px_rgb(0_0_0/0.55)] ring-1 ring-paper-line">
                <template x-for="(option, i) in options" :key="option.id">
                    <li role="option" :id="'yopt-' + option.id" :aria-selected="(i === active).toString()"
                        x-on:click="add(option.id)" x-on:mousemove="active = i"
                        class="cursor-pointer px-4 py-2.5" :class="i === active && 'bg-gilt-soft'">
                        <span class="font-bold" x-text="option.name"></span>
                        <span class="text-ink-muted" x-text="option.gloss"></span>
                        <span x-show="option.yakuman" class="ml-1 rounded bg-ko-soft px-1.5 py-0.5 text-xs font-bold text-ko">yakuman</span>
                        <span class="block text-sm text-ink-muted" x-text="option.description"></span>
                    </li>
                </template>
            </ul>
        </div>

        <p id="yaku-hint" class="min-h-5 text-sm text-ink-muted" aria-live="polite" x-text="hint"></p>

        <div>
            <button type="button" class="inline-flex items-center gap-2 text-sm font-bold text-ink underline decoration-paper-line decoration-2 underline-offset-4 hover:decoration-ink"
                    x-on:click="browsing = !browsing" :aria-expanded="browsing.toString()" aria-controls="yaku-browse">
                <span x-text="browsing ? 'Masquer la liste' : 'Parcourir la liste des yaku'"></span>
            </button>
            <div id="yaku-browse" x-cloak x-show="browsing" x-collapse>
                <div class="mt-4 space-y-4">
                    <template x-for="[han, list] in groups" :key="han">
                        <div>
                            <p class="text-sm text-ink-muted" x-text="han + ' han (main fermée)'"></p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <template x-for="e in list" :key="e.id">
                                    <button type="button" class="chip" :class="picked.includes(e.id) && 'chip--picked'"
                                            :aria-pressed="picked.includes(e.id).toString()" x-on:click="toggle(e.id)" :title="e.description" x-text="e.label"></button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
@else
    @php
        $fields = $result['fields'];
        $wrong = array_keys(array_filter($fields, fn ($f) => $f['state'] === 'extra'));
        $missed = array_keys(array_filter($fields, fn ($f) => $f['state'] === 'missed'));
        $found = count($correct) - count($missed);
        $label = fn (string $id) => (\App\Domain\Mahjong\Yaku::tryFrom($id) ?? \App\Domain\Mahjong\Yakuman::from($id));
    @endphp

    <div class="flex flex-wrap gap-2" aria-label="Correction des yaku">
        @foreach ($correct as $yaku)
            @php($state = $fields[$yaku->value]['state'])
            <span @class(['chip', 'chip--ok' => $state === 'ok', 'chip--missed' => $state === 'missed', 'chip--picked' => $state === 'revealed'])>
                {{ $yaku->shortLabel() }}
                @if ($state === 'missed')<span class="text-xs font-normal">oublié</span>@endif
            </span>
        @endforeach
        @foreach ($wrong as $id)
            <span class="chip chip--extra"><s>{{ $label($id) instanceof \App\Domain\Mahjong\Yaku ? $label($id)->shortLabel() : $label($id)->label() }}</s><span class="text-xs font-normal">en trop</span></span>
        @endforeach
    </div>

    <p @class(['mt-5 font-bold', 'text-ok' => $result['ok'], 'text-ko' => ! $result['ok'] && ! $result['revealed']])>
        @if ($result['revealed'])
            Les yaku de cette main :
        @elseif ($result['ok'])
            Tous les yaku sont trouvés.
        @else
            {{ $found }} sur {{ count($correct) }} trouvé{{ $found > 1 ? 's' : '' }}@if ($wrong !== []), {{ count($wrong) }} en trop @endif.
        @endif
    </p>

    <ul class="mt-3 space-y-2.5">
        @foreach ($correct as $yaku)
            <li class="flex gap-3">
                <span @class(['mt-2 size-2 flex-none rounded-full', 'bg-ok' => ! in_array($yaku->value, $missed, true), 'bg-ko' => in_array($yaku->value, $missed, true)])></span>
                <span><b>{{ $yaku->label() }}</b> : {{ $yaku->description() }}</span>
            </li>
        @endforeach
        @foreach ($wrong as $id)
            @php($y = $label($id))
            <li class="flex gap-3">
                <span class="mt-2 size-2 flex-none rounded-full border-2 border-ko"></span>
                <span><b>{{ $y->label() }}</b> n’est pas dans cette main.@if ($y instanceof \App\Domain\Mahjong\Yakuman) C’est un yakuman, et Tenpai n’en distribue jamais.@endif Condition : {{ $y->description() }}</span>
            </li>
        @endforeach
    </ul>

    @if ($evaluation->multiple)
        <p class="mt-5 rounded-lg bg-paper-sunk px-4 py-3 text-sm text-ink-muted">Cette main se découpe de plusieurs façons. On retient la lecture qui rapporte le plus de points.</p>
    @endif
@endif
