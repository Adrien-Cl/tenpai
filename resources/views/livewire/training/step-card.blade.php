{{-- La carte de l'étape affichée. --}}
@php
    $s = $steps[$view];
    $result = $results[$view] ?? null;
    $review = $view < $step;
    $kanji = ['一', '二', '三', '四', '五'][$view];
@endphp
<section class="paper relative" aria-labelledby="step-title" wire:key="step-{{ $view }}">
    <header class="flex gap-4 px-5 pt-6 sm:gap-5 sm:px-8 sm:pt-8">
        <span class="step-tile hidden h-14 w-11 shrink-0 sm:grid"><x-glyph :char="$kanji" class="size-6" /></span>
        <div @class(['min-w-0 flex-1', 'pr-20 sm:pr-24' => $result])>
            <p class="text-sm text-ink-muted">{{ __('Étape :step sur 5', ['step' => $view + 1]) }}{{ $review ? ' · '.__('relecture') : '' }}</p>
            <h2 id="step-title" class="display mt-0.5 text-2xl sm:text-[1.9rem]">{{ $s->title() }}</h2>
            <p class="mt-2 max-w-[62ch] text-ink-muted">{{ $s->help() }}</p>
        </div>
        @if ($result)
            <x-stamp :result="$result" class="absolute top-5 right-5 sm:top-7 sm:right-8" />
        @endif
    </header>

    <form wire:submit="check" class="mt-4">
        <div class="px-5 pb-6 sm:px-8 sm:pb-8">
            @include('livewire.training.steps.'.$s->key(), ['result' => $result])
        </div>

        <footer class="sticky bottom-0 z-10 flex items-center gap-2 rounded-b-[0.875rem] border-t border-paper-line bg-paper/95 px-4 py-3 backdrop-blur sm:gap-3 sm:px-8 sm:py-4">
            @if (! $result)
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">{{ __('Valider') }}</button>
                <button type="button" class="btn btn-quiet px-3 sm:px-[1.15rem]" wire:click="reveal" wire:loading.attr="disabled">{{ __('Voir la réponse') }}</button>
                <span class="hidden text-sm text-ink-muted md:inline">{{ __('Entrée pour valider') }}</span>
            @elseif ($review || $step >= \App\Livewire\Training::RECAP)
                <button type="button" class="btn btn-quiet" wire:click="show({{ $step }})">
                    {{ $step >= \App\Livewire\Training::RECAP ? __('Revenir au récapitulatif') : __('Revenir à l’étape en cours') }}
                </button>
            @else
                <button type="button" class="btn btn-primary" wire:click="next" x-init="$el.focus({ preventScroll: true })">
                    {{ $view === 4 ? __('Voir le récapitulatif') : __('Étape suivante') }}
                </button>
            @endif
            <button type="button" class="btn ml-auto px-2 text-ink-muted hover:text-ink sm:px-3" data-shortcut="b" x-on:click="openScoring('{{ $s->scoringTab() }}')">
                {{ __('Barème') }} <kbd class="hidden rounded border border-paper-line px-1.5 text-xs font-normal sm:inline">B</kbd>
            </button>
        </footer>
    </form>
</section>
