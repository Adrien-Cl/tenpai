{{-- Étape 2 : la valeur de chaque yaku. --}}
@php($evaluation = $hand->evaluation)
<p class="mb-2 text-sm text-ink-muted">{{ $evaluation->menzen ? __('Main fermée.') : __('Main ouverte.') }}</p>
<div>
    @foreach ($evaluation->yaku() as $yakuHan)
        <x-answer-row wire:key="han-{{ $yakuHan->yaku->value }}"
                      :id="'f-han-'.$yakuHan->yaku->value"
                      :model="'answers.han.'.$yakuHan->yaku->value"
                      :title="$yakuHan->yaku->label()"
                      :sub="$yakuHan->yaku->gloss()"
                      :field="$result['fields'][$yakuHan->yaku->value] ?? null" />
    @endforeach
</div>

@if ($result)
    <p class="mt-4 font-bold">{{ $evaluation->menzen ? __('Han des yaku : :han (main fermée).', ['han' => $evaluation->yakuHan()]) : __('Han des yaku : :han (main ouverte).', ['han' => $evaluation->yakuHan()]) }}</p>
@endif
