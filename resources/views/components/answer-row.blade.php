{{--
    Une ligne de la fiche : intitulé, champ numérique, correction.
    model : chemin wire:model · field : résultat de la correction (state, expected, why) · title : intitulé texte
    placeholder : valeur affichée en grisé tant que le champ est vide
    Le slot par défaut remplace l'intitulé (par exemple des tuiles).
    Le champ n'accepte que des chiffres : le reste est bloqué à la frappe comme au collage.
--}}
@props(['model', 'id', 'title' => null, 'sub' => null, 'field' => null, 'placeholder' => null])
@php
    $state = $field['state'] ?? null;
@endphp
<div {{ $attributes->class('grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1.5 border-t border-paper-line py-3 first:border-t-0') }}>
    <label for="{{ $id }}" class="min-w-0">
        <span class="block font-bold">{{ $title ?? $slot }}</span>
        @if ($sub)
            <span class="block text-sm text-ink-muted">{{ $sub }}</span>
        @endif
    </label>

    <div class="flex items-center gap-3">
        <span class="w-14 text-right text-sm font-bold" aria-live="polite">
            @if ($state === 'ok')
                <span class="text-lg text-ok" aria-hidden="true">◎</span><span class="sr-only">{{ __('Juste') }}</span>
            @elseif ($state === 'ko')
                <span class="num text-ko"><span aria-hidden="true">→ </span><span class="sr-only">{{ __('Réponse :') }} </span>{{ $field['expected'] }}</span>
            @endif
        </span>
        <input id="{{ $id }}" type="number" inputmode="numeric" min="0" step="1" autocomplete="off"
               wire:model="{{ $model }}" @disabled($field !== null)
               @if ($placeholder !== null) placeholder="{{ $placeholder }}" @endif
               x-on:beforeinput="/\D/.test($event.data ?? $event.dataTransfer?.getData('text') ?? '') && $event.preventDefault()"
               @class(['field', 'is-ok' => $state === 'ok', 'is-ko' => $state === 'ko'])
               @if ($state === 'ko') aria-invalid="true" @endif>
    </div>

    @if (! empty($field['why']))
        <p class="col-span-2 text-sm text-ink-muted">{{ $field['why'] }}</p>
    @endif
</div>
