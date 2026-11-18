{{-- Exercice des attentes : la main juste avant la tuile gagnante, les défausses, puis la palette des 34 tuiles. --}}
@php
    $waitsHand = $this->hand;
    $g = $waitsHand->generated;
    $h = $g->hand;
    $tilesResult = $result['tiles'] ?? [];
    $furitenField = $result['furiten'] ?? null;
    $families = [
        __('Caractères') => range(0, 8),
        __('Cercles') => range(9, 17),
        __('Bambous') => range(18, 26),
        __('Honneurs') => range(27, 33),
    ];
@endphp
<div
    x-data="{
        copied: false,
        async share() {
            const data = { title: 'Tenpai', text: {{ Js::from(__('Trouvez les attentes de cette main avec moi.')) }}, url: window.location.href };
            if (navigator.share && matchMedia('(pointer: coarse)').matches) {
                try { await navigator.share(data); } catch (e) {}
                return;
            }
            await navigator.clipboard?.writeText(data.url);
            this.copied = true;
            setTimeout(() => this.copied = false, 2200);
        },
    }"
    x-on:drill-finished.window="$store.progress.recordDrill($event.detail)"
    class="mx-auto max-w-6xl px-4 pt-2 sm:px-6 sm:pt-4"
>
    @include('livewire.training.picker', ['route' => 'waits', 'theme' => $this->theme(), 'level' => \App\Domain\Mahjong\Generation\Seed::fromString($seed)->level()])

    {{-- Le tapis : vents, main en attente, appels, puis les défausses du joueur. --}}
    <section class="felt px-3 pt-5 pb-6 sm:px-8 sm:pt-7 sm:pb-8" aria-label="{{ __('Main en attente') }}">
        <div class="flex flex-wrap items-start gap-x-6 gap-y-3">
            <div class="flex items-center gap-4" role="group" aria-label="{{ __('Vents') }}">
                <div class="flex items-center gap-2.5">
                    <span class="step-tile h-10 w-8 text-lg"><x-glyph :char="$h->round->kanji()" class="size-5" /></span>
                    <span class="text-sm leading-tight text-felt-muted">{{ __('Manche') }}<br><b class="text-base text-felt-ink">{{ $h->round->label() }}</b></span>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="step-tile h-10 w-8 text-lg"><x-glyph :char="$h->seat->kanji()" class="size-5" /></span>
                    <span class="text-sm leading-tight text-felt-muted">{{ __('Votre siège') }}<br><b class="text-base text-felt-ink">{{ $h->seat->label() }}</b></span>
                </div>
            </div>
            <div class="flex w-full flex-wrap items-center gap-2 sm:ml-auto sm:w-auto">
                @if ($h->riichi)
                    <span class="rounded-lg bg-felt-deep/60 px-3 py-2 text-sm font-bold text-felt-ink">Riichi</span>
                @endif
                <span class="ml-auto flex flex-wrap items-center gap-1 sm:ml-0">
                    {{-- Une autre main tant que celle-ci n'est pas corrigée (ensuite, « Main suivante » en bas). Une main passée ne compte pas. --}}
                    @unless ($result)
                        <button type="button" class="rounded-lg px-3 py-2 text-sm text-felt-muted hover:bg-felt-raised hover:text-felt-ink disabled:opacity-60" wire:click="nextHand" wire:loading.attr="disabled" data-reroll>
                            {{ __('Changer de main') }}
                        </button>
                    @endunless
                    <button type="button" class="rounded-lg px-3 py-2 text-sm text-felt-muted hover:bg-felt-raised hover:text-felt-ink" x-on:click="share()">
                        <span x-show="!copied">{{ __('Partager cette main') }}</span>
                        <span x-cloak x-show="copied" class="text-gilt">{{ __('Lien copié') }}</span>
                    </button>
                </span>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-end gap-x-[calc(var(--tile)*0.55)] gap-y-5 [--tile:clamp(1.15rem,calc((100vw-5.5rem)/14.4),3.15rem)] sm:mt-10 sm:[--tile:clamp(1.15rem,calc((100vw-8.5rem)/15),3.15rem)]">
            {{-- Le joueur range sa main à sa guise (glisser-déposer, dans le navigateur seulement) ; wire:ignore garde son ordre après la correction. --}}
            <span class="flex flex-col items-center" x-data="arrangeableHand" wire:ignore>
                <span class="tiles arrangeable gap-px" aria-label="{{ __('Tuiles en main') }}" data-waiting-hand
                      x-init="$store.sound.play('deal')" x-sort="moved = true; $store.sound.play('tile')" x-sort:config="{ forceFallback: true, fallbackOnBody: true, fallbackTolerance: 3 }" x-ref="hand">
                    @foreach ($g->handTiles as $t)
                        <x-tile :tile="$t->tile" :red="$t->red" />
                    @endforeach
                </span>
                <span class="mt-3 w-0 min-w-full text-center text-xs text-felt-muted" x-show="!moved">{{ __('Glissez les tuiles pour ranger votre main') }}</span>
                <button type="button" x-cloak x-show="moved" x-on:click="restore()" class="mt-1.5 rounded-md px-2 py-1 text-xs whitespace-nowrap text-felt-muted hover:bg-felt-raised hover:text-felt-ink" data-restore-hand>
                    {{ __('Remettre dans l’ordre') }}
                </button>
            </span>
            <span class="flex flex-col items-center" aria-hidden="true">
                <span class="block h-[calc(var(--tile)*46/34)] w-(--tile) rounded-[3px] border-2 border-dashed border-felt-line"></span>
                <span class="mt-3 text-xs font-bold text-gilt">?</span>
            </span>
            @if ($g->melds !== [])
                <span class="flex flex-wrap items-end gap-x-[calc(var(--tile)*0.35)] gap-y-4">
                    @foreach ($g->melds as $meld)
                        <span class="flex flex-col items-center">
                            <span class="tiles meld-tiles">
                                @foreach ($meld->tiles as $i => $t)
                                    <x-tile :tile="$t->tile" :red="$t->red"
                                            :back="$meld->type === \App\Domain\Mahjong\Hand\MeldType::ClosedKan && ($i === 0 || $i === 3)"
                                            :side="$meld->rotated === $i" />
                                @endforeach
                            </span>
                            <span class="mt-2 text-xs text-felt-muted">{{ $meld->type->label() }}</span>
                        </span>
                    @endforeach
                </span>
            @endif
        </div>

        <div class="mt-6 border-t border-felt-line pt-4 sm:mt-8 sm:pt-5" style="--tile: clamp(1.15rem, 3.4vw, 1.85rem)">
            <p class="text-sm text-felt-muted">{{ __('Vos défausses') }}</p>
            <div class="mt-2 grid w-max grid-cols-6 gap-px" aria-label="{{ __('Vos défausses') }}" data-discards>
                @foreach ($waitsHand->discards as $tile)
                    <span class="tiles"><x-tile :tile="$tile" :class="$furitenField && in_array($tile, $waitsHand->furitenTiles(), true) ? 'rounded-[3px] outline-2 outline-offset-1 outline-gilt' : ''" /></span>
                @endforeach
            </div>
        </div>
    </section>

    <section class="paper relative mt-4 sm:mt-8" aria-labelledby="waits-title" wire:key="waits-card">
        <header class="flex gap-4 px-4 pt-5 sm:gap-5 sm:px-8 sm:pt-8">
            <span class="step-tile hidden h-14 w-11 shrink-0 sm:grid"><x-glyph char="聴" class="size-6" /></span>
            <div @class(['min-w-0 flex-1', 'pr-20 sm:pr-24' => $result])>
                <p class="text-sm text-ink-muted">{{ __('Exercice :drill', ['drill' => mb_strtolower(\App\Training\Drill::Waits->label())]) }}</p>
                <h2 id="waits-title" class="display mt-0.5 text-2xl sm:text-[1.9rem]">{{ __('Quelles tuiles complètent la main ?') }}</h2>
                <p class="mt-2 max-w-[62ch] text-ink-muted">{{ __('Touchez toutes les tuiles qui terminent la main, yaku ou non. Puis regardez vos défausses : pouvez-vous gagner en ron ?') }}</p>
            </div>
            @if ($result)
                <x-stamp :result="$result" class="absolute top-4 right-4 sm:top-7 sm:right-8" />
            @endif
        </header>

        <form wire:submit="check" class="mt-4">
            <div class="space-y-5 px-4 pb-6 sm:px-8 sm:pb-8">
                <fieldset>
                    <legend class="sr-only">{{ __('Les attentes') }}</legend>
                    <div class="grid gap-3" style="--tile: clamp(1.6rem, calc((100vw - 6rem) / 11), 2.4rem)">
                        @foreach ($families as $family => $tiles)
                            <div role="group" aria-label="{{ $family }}" class="flex flex-wrap gap-1">
                                @foreach ($tiles as $tile)
                                    @php($state = $tilesResult[$tile] ?? null)
                                    <label @class(['pick', 'is-ok' => $state === 'ok', 'is-missed' => $state === 'missed', 'is-extra' => $state === 'extra', 'is-revealed' => $state === 'revealed'])
                                           title="{{ \App\Domain\Mahjong\Tile::name($tile) }}">
                                        <input type="checkbox" class="sr-only" value="{{ $tile }}" wire:model="answers.waits" @disabled($result !== null)
                                               aria-label="{{ \App\Domain\Mahjong\Tile::name($tile) }}">
                                        <span class="tiles"><x-tile :tile="$tile" aria-hidden="true" /></span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="float-left mb-2 w-full font-bold">{{ __('Furiten ?') }}
                        @if ($furitenField && $furitenField['state'] === 'ok')<span class="ml-2 text-lg text-ok" aria-hidden="true">◎</span><span class="sr-only">{{ __('Juste') }}</span>@endif
                        @if ($furitenField && $furitenField['state'] === 'ko')<span class="ml-2 text-sm text-ko">→ {{ $furitenField['expected'] }}</span>@endif
                    </legend>
                    <div class="clear-both flex flex-wrap gap-2">
                        @foreach (['non' => __('Non, ron possible'), 'oui' => __('Oui, tsumo seulement')] as $value => $label)
                            <label @class([
                                'choice',
                                'is-right' => $furitenField && $value === ($waitsHand->isFuriten() ? 'oui' : 'non'),
                                'is-wrong' => $furitenField && $furitenField['state'] === 'ko' && ($answers['furiten'] ?? null) === $value,
                            ])>
                                <input type="radio" class="sr-only" name="furiten" value="{{ $value }}" wire:model="answers.furiten" @disabled($result !== null)>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @if ($furitenField)
                        <p class="mt-2 text-sm text-ink-muted">{{ $furitenField['why'] }}</p>
                    @endif
                </fieldset>

                @if ($result)
                    <div class="rounded-lg bg-paper-sunk px-4 py-3" data-waits-answer>
                        <p class="font-bold">{{ __('Attentes : :summary', ['summary' => $result['summary']]) }}</p>
                        <ul class="mt-1.5 space-y-1 text-sm text-ink-muted">
                            @foreach ($result['notes'] as $note)
                                <li>{{ $note }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <footer class="sticky bottom-0 z-10 flex flex-wrap items-center gap-2 rounded-b-[0.875rem] border-t border-paper-line bg-paper/95 px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur sm:gap-3 sm:px-8 sm:py-4">
                @if (! $result)
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">{{ __('Valider') }}</button>
                    <button type="button" class="btn btn-quiet px-3 sm:px-[1.15rem]" wire:click="reveal" wire:loading.attr="disabled">{{ __('Voir la réponse') }}</button>
                @else
                    <button type="button" class="btn btn-primary" wire:click="nextHand" data-shortcut="n" x-init="$el.focus({ preventScroll: true })">{{ __('Main suivante') }}</button>
                    <a href="{{ localized_route('counting.hand', ['seed' => $seed]) }}" class="btn btn-quiet" wire:navigate>{{ __('Compter la main gagnée') }}</a>
                @endif
            </footer>
        </form>
    </section>
</div>
