{{--
    Le barème, en trois onglets qui suivent le comptage : Han, Fu, Points.
    Le même composant sert la page /bareme et la fenêtre de l'entraînement (training).
    En entraînement, le calculateur de points n'apparaît qu'une fois l'étape des points validée.
--}}
@props(['sheet', 'training' => false, 'tab' => 'points'])
@php
    $tabs = [
        'han' => ['Han', __('yaku, dora')],
        'fu' => ['Fu', __('la recette')],
        'points' => ['Points', __('ce que ça paie')],
    ];
@endphp
<div x-data="scoringSheet({ tab: @js($tab), useHash: @js(! $training) })"
     x-on:scoring-tab.window="tab = $event.detail"
     {{ $attributes }}>
    <div class="grid grid-cols-3 gap-1 rounded-xl bg-paper-sunk p-1" role="tablist" aria-label="{{ __('Rubriques du barème') }}">
        @foreach ($tabs as $key => [$label, $caption])
            <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                    class="flex flex-col items-center rounded-lg px-2 py-2 transition-colors sm:py-2.5"
                    :class="tab === '{{ $key }}' ? 'bg-ink text-paper shadow-sm' : 'text-ink-muted hover:bg-paper hover:text-ink'"
                    :aria-selected="(tab === '{{ $key }}').toString()"
                    :tabindex="tab === '{{ $key }}' ? 0 : -1"
                    x-on:click="tab = '{{ $key }}'"
                    x-on:keydown.arrow-right.prevent="next(1)"
                    x-on:keydown.arrow-left.prevent="next(-1)">
                <span class="display text-lg leading-tight sm:text-xl">{{ $label }}</span>
                <span class="hidden text-xs opacity-75 sm:block">{{ $caption }}</span>
            </button>
        @endforeach
    </div>

    <section id="panel-han" role="tabpanel" aria-labelledby="tab-han" class="mt-7" x-cloak x-show="tab === 'han'">
        @include('bareme.han')
    </section>
    <section id="panel-fu" role="tabpanel" aria-labelledby="tab-fu" class="mt-7" x-cloak x-show="tab === 'fu'">
        @include('bareme.fu')
    </section>
    <section id="panel-points" role="tabpanel" aria-labelledby="tab-points" class="mt-7" x-show="tab === 'points'">
        @include('bareme.points', ['training' => $training])
    </section>
</div>
