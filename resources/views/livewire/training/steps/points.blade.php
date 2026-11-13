{{-- Étape 5 : la limite et les paiements. --}}
@php
    $evaluation = $hand->evaluation;
    $fields = $result['fields'] ?? [];
    $limitField = $fields['limit'] ?? null;
    $expectedLimit = $evaluation->payment()->limit?->value ?? 'none';
    $chosen = $answers['points']['limit'] ?? null;
@endphp

<fieldset class="pb-3">
    <legend class="float-left mb-2 w-full font-bold">Limite atteinte <span class="font-normal text-ink-muted">{{ $evaluation->han }} han, {{ $evaluation->fu() }} fu</span>
        @if ($limitField && $limitField['state'] === 'ok')<span class="ml-2 text-lg text-ok" aria-hidden="true">◎</span><span class="sr-only">Juste</span>@endif
        @if ($limitField && $limitField['state'] === 'ko')<span class="ml-2 text-sm text-ko">→ {{ $limitField['expected'] }}</span>@endif
    </legend>
    <div class="clear-both flex flex-wrap gap-2">
        @foreach (\App\Training\Steps\PointsStep::limits() as $value => $label)
            <label @class([
                'choice',
                'is-right' => $limitField && $value === $expectedLimit,
                'is-wrong' => $limitField && $limitField['state'] === 'ko' && $chosen === $value,
            ])>
                <input type="radio" class="sr-only" name="limit" value="{{ $value }}" wire:model="answers.points.limit" @disabled($limitField !== null)>
                {{ $label }}
            </label>
        @endforeach
    </div>
</fieldset>

<div>
    @foreach (\App\Training\Steps\PointsStep::fields($hand) as $key => $f)
        <x-answer-row wire:key="points-{{ $key }}" :id="'f-points-'.$key" :model="'answers.points.'.$key" :title="$f['label']" :sub="$f['sub']" :field="$fields[$key] ?? null" />
    @endforeach
</div>

@if ($result)
    <div class="mt-5 space-y-1.5 rounded-lg bg-paper-sunk px-4 py-3">
        @foreach (\App\Training\Steps\PointsStep::formula($hand) as $line)
            <p>{!! $line !!}</p>
        @endforeach
    </div>
@endif
