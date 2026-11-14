{{-- Étape 3 : dora, cinq rouges, ura-dora et total des han. --}}
@php($evaluation = $hand->evaluation)
<div>
    @foreach (\App\Training\Steps\DoraStep::fields($hand) as $key => $f)
        <x-answer-row wire:key="dora-{{ $key }}"
                      :id="'f-dora-'.$key"
                      :model="'answers.dora.'.$key"
                      :title="$f['label']"
                      :sub="$f['sub']"
                      :placeholder="$key === 'han' ? null : '0'"
                      :field="$result['fields'][$key] ?? null"
                      :class="$key === 'han' ? 'mt-2 border-t-2! border-ink/70!' : ''" />
    @endforeach
</div>

@if ($result)
    <ul class="mt-5 space-y-2.5" aria-label="{{ __('Indicateurs') }}">
        @foreach ([['Dora', $evaluation->dora->detail], ['Ura-dora', $evaluation->ura->detail]] as [$label, $detail])
            @foreach ($detail as $line)
                <li class="flex flex-wrap items-center gap-3">
                    <span class="w-16 text-sm text-ink-muted">{{ $label }}</span>
                    <span class="mini tiles"><x-tile :tile="$line['indicator']" /></span>
                    <span class="text-sm text-ink-muted">{{ __('indique') }}</span>
                    <span class="mini tiles"><x-tile :tile="$line['dora']" /></span>
                    <span @class(['text-sm', 'font-bold' => $line['count'] > 0])>{{ $line['count'] ? __(':count dans la main', ['count' => $line['count']]) : __('absent de la main') }}</span>
                </li>
            @endforeach
        @endforeach
    </ul>
@endif
