{{-- La fiche de score : les cinq étapes, puis le récapitulatif. Sur mobile, une rangée de tuiles numérotées. --}}
@php
    $kanji = ['一', '二', '三', '四', '五'];
    $okCount = count(array_filter($results, fn ($r) => $r['ok']));
    $played = $this->playedSteps();
@endphp
<aside class="paper self-start p-2 sm:p-3 lg:sticky lg:top-6 lg:p-5" aria-label="{{ __('Fiche de score') }}">
    <div class="hidden items-baseline justify-between lg:flex">
        <h2 class="display text-lg">{{ __('Fiche de score') }}</h2>
        <span class="text-sm text-ink-muted">{{ $okCount }}/{{ $played }}</span>
    </div>

    <ol class="grid grid-cols-5 gap-1 lg:mt-4 lg:grid-cols-1 lg:gap-0.5">
        @foreach ($steps as $i => $s)
            @php
                $result = $results[$i] ?? null;
                $reachable = $i <= $step;
            @endphp
            <li>
                <button type="button" wire:click="show({{ $i }})" @disabled(! $reachable)
                        @class([
                            'flex w-full flex-col items-center gap-1.5 rounded-lg px-1 py-2 text-left transition-colors lg:flex-row lg:gap-3 lg:px-2.5',
                            'bg-gilt-soft' => $i === $view,
                            'hover:bg-paper-sunk' => $reachable && $i !== $view,
                            'opacity-45' => ! $reachable,
                        ])
                        @if ($i === $view) aria-current="step" @endif>
                    <span class="step-tile"><x-glyph :char="$kanji[$i]" class="size-[1.05rem]" /></span>
                    <span class="min-w-0 flex-1 text-center lg:text-left">
                        <span class="block text-xs font-bold lg:text-base">{{ $s->label() }}</span>
                        <span class="hidden truncate text-sm text-ink-muted lg:block">
                            @if ($result) {{ $result['summary'] }} @elseif ($i === $step) {{ __('en cours') }} @else &nbsp; @endif
                        </span>
                    </span>
                    <span class="h-5 text-lg leading-5">
                        @include('livewire.training.step-mark', ['result' => $result, 'okClass' => 'text-ok', 'koClass' => 'text-ko', 'givenClass' => 'text-ink-faint'])
                    </span>
                </button>
            </li>
        @endforeach
    </ol>

    @if ($step >= \App\Livewire\Training::RECAP)
        <button type="button" wire:click="show({{ \App\Livewire\Training::RECAP }})"
                @class(['mt-2 flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-left font-bold', 'bg-gilt-soft' => $view === \App\Livewire\Training::RECAP, 'hover:bg-paper-sunk' => $view !== \App\Livewire\Training::RECAP])>
            <span class="step-tile"><x-glyph char="計" class="size-[1.05rem]" /></span>
            {{ __('Récapitulatif') }}
            <span class="ml-auto text-sm font-normal text-ink-muted">{{ __(':ok/:played justes', ['ok' => $okCount, 'played' => $played]) }}</span>
        </button>
    @endif

    <dl class="mt-4 hidden grid-cols-3 gap-2 border-t border-paper-line pt-4 lg:grid">
        @foreach ([['Han', $hand->evaluation->han, 2], ['Fu', $hand->evaluation->fu(), 3], ['Points', \App\Support\Format::points($hand->evaluation->payment()->total), 4]] as [$label, $value, $after])
            <div>
                <dt class="text-xs text-ink-muted">{{ $label }}</dt>
                <dd class="display num text-xl">{{ isset($results[$after]) ? $value : '–' }}</dd>
            </div>
        @endforeach
    </dl>
</aside>
