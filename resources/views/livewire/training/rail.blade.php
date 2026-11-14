{{--
    La main en petit, collée en haut de l'écran quand le tapis n'est plus visible.
    Sous lg, les étapes y sont aussi : on en change sans remonter jusqu'à la fiche de score.
--}}
@php
    $g = $hand->generated;
    $kanji = ['一', '二', '三', '四', '五'];
    $stepButtonClass = 'flex min-h-10 min-w-0 flex-1 items-center justify-center gap-1.5 rounded-md px-1 transition-colors';
@endphp
<div x-cloak x-show="!tableVisible" x-transition.opacity.duration.150ms data-rail
     class="fixed inset-x-0 top-0 z-30 border-b-4 border-(--wood) bg-felt/95 pt-[env(safe-area-inset-top)] pr-[env(safe-area-inset-right)] pl-[env(safe-area-inset-left)] text-felt-ink shadow-[0_8px_24px_-12px_rgb(0_0_0/0.6)] backdrop-blur">
    <div class="mx-auto flex max-w-6xl flex-wrap items-end gap-x-3 gap-y-1 px-3 py-2 sm:px-6" style="--tile: clamp(1.05rem, calc((100vw - 2.5rem) / 17), 1.7rem)" aria-hidden="true">
        <span class="tiles gap-px">
            @foreach ($g->handTiles as $i => $t)
                <x-tile :tile="$t->tile" :red="$t->red" :group="$groups['hand'][$i] ?? null" />
            @endforeach
        </span>
        <span class="tiles"><x-tile :tile="$g->winTile->tile" :red="$g->winTile->red" :group="$groups['win']" class="outline-2 outline-offset-1 outline-gilt rounded-[3px]" /></span>
        @foreach ($g->melds as $m => $meld)
            <span class="tiles">
                @foreach ($meld->tiles as $i => $t)
                    <x-tile :tile="$t->tile" :red="$t->red"
                            :back="$meld->type === \App\Domain\Mahjong\Hand\MeldType::ClosedKan && ($i === 0 || $i === 3)"
                            :side="$meld->rotated === $i" :group="$groups['melds'][$m] ?? null" />
                @endforeach
            </span>
        @endforeach
        <span class="ml-auto flex items-end gap-2 text-xs text-felt-muted">
            {{ $g->hand->tsumo ? 'Tsumo' : 'Ron' }} · {{ $g->hand->seat->label() }}@if ($g->hand->isDealer()) ({{ __('donneur') }})@endif
            <span class="tiles gap-px">
                @foreach ($g->hand->dora as $t)
                    <x-tile :tile="$t" />
                @endforeach
            </span>
        </span>
    </div>

    <nav class="mx-auto flex max-w-6xl gap-1 border-t border-felt-line px-2 py-1.5 sm:px-5 lg:hidden" aria-label="{{ __('Étapes') }}">
        @foreach ($steps as $i => $s)
            @php
                $result = $results[$i] ?? null;
                $reachable = $i <= $step;
            @endphp
            <button type="button" wire:click="show({{ $i }})" @disabled(! $reachable)
                    @class([
                        $stepButtonClass,
                        'bg-felt-raised shadow-[inset_0_0_0_1px_var(--gilt)]' => $i === $view,
                        'hover:bg-felt-raised' => $reachable && $i !== $view,
                        'opacity-40' => ! $reachable,
                    ])
                    @if ($i === $view) aria-current="step" @endif>
                <span class="step-tile relative h-[1.6rem] w-[1.25rem] text-sm">
                    <x-glyph :char="$kanji[$i]" class="size-[0.8rem]" />
                    <span class="absolute -top-1.5 -right-2 text-xs leading-none font-bold">
                        @include('livewire.training.step-mark', ['result' => $result, 'okClass' => 'text-(--shu-400)', 'koClass' => 'text-(--ai-400)', 'givenClass' => 'text-felt-muted'])
                    </span>
                </span>
                <span class="truncate text-xs font-bold max-[24rem]:sr-only">{{ $s->label() }}</span>
            </button>
        @endforeach
        @if ($step >= \App\Livewire\Training::RECAP)
            <button type="button" wire:click="show({{ \App\Livewire\Training::RECAP }})"
                    @class([
                        $stepButtonClass,
                        'flex-none px-2',
                        'bg-felt-raised shadow-[inset_0_0_0_1px_var(--gilt)]' => $view === \App\Livewire\Training::RECAP,
                        'hover:bg-felt-raised' => $view !== \App\Livewire\Training::RECAP,
                    ])
                    @if ($view === \App\Livewire\Training::RECAP) aria-current="step" @endif
                    aria-label="{{ __('Récapitulatif') }}">
                <span class="step-tile h-[1.6rem] w-[1.25rem] text-sm"><x-glyph char="計" class="size-[0.8rem]" /></span>
            </button>
        @endif
    </nav>
</div>
