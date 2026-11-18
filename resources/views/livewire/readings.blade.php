{{-- Exercice des lectures : la main sur le tapis, puis chaque découpage à compter, et le choix de celui qui compte. --}}
@php
    $readingsHand = $this->hand;
    $hand = $readingsHand->training;
    $groups = ['hand' => [], 'win' => null, 'melds' => []];
    $fields = $result['fields'] ?? [];
    $choiceField = $fields['choice'] ?? null;
    $best = $readingsHand->bestIndexes();
    $letters = \App\Training\ReadingsHand::LETTERS;
@endphp
<div
    x-data="{
        lit: null,
        tableVisible: true,
        copied: false,
        async share() {
            const data = { title: 'Tenpai', text: {{ Js::from(__('Trouvez la bonne lecture de cette main avec moi.')) }}, url: window.location.href };
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
    @include('livewire.training.picker', ['current' => \App\Training\Drill::Readings, 'theme' => null, 'level' => \App\Domain\Mahjong\Generation\Seed::fromString($seed)->level()])

    @include('livewire.training.table')

    <section class="paper relative mt-4 sm:mt-8" aria-labelledby="readings-title" wire:key="readings-card">
        <header class="flex gap-4 px-4 pt-5 sm:gap-5 sm:px-8 sm:pt-8">
            <span class="step-tile hidden h-14 w-11 shrink-0 sm:grid"><x-glyph char="解" class="size-6" /></span>
            <div @class(['min-w-0 flex-1', 'pr-20 sm:pr-24' => $result])>
                <p class="text-sm text-ink-muted">{{ __('Exercice :drill', ['drill' => mb_strtolower(\App\Training\Drill::Readings->label())]) }}</p>
                <h2 id="readings-title" class="display mt-0.5 text-2xl sm:text-[1.9rem]">{{ __('Quel découpage compte ?') }}</h2>
                <p class="mt-2 max-w-[62ch] text-ink-muted">{{ __('La même main se lit de plusieurs façons. Donnez les han (dora compris) et les fu de chaque lecture, puis choisissez celle qui compte.') }}</p>
            </div>
            @if ($result)
                <x-stamp :result="$result" class="absolute top-4 right-4 sm:top-7 sm:right-8" />
            @endif
        </header>

        <form wire:submit="check" class="mt-4" x-init="$store.sound.play('deal')">
            <div class="grid gap-4 px-4 pb-6 sm:px-8 sm:pb-8 md:grid-cols-2">
                @foreach ($readingsHand->readings as $index => $reading)
                    <article @class([
                        'rounded-xl bg-paper-sunk px-4 py-4 shadow-[inset_0_0_0_1px_var(--paper-line)]',
                        'shadow-[inset_0_0_0_2px_var(--ok)]!' => $result && in_array($index, $best, true),
                    ]) aria-labelledby="reading-{{ $index }}" data-reading="{{ $index }}">
                        <h3 id="reading-{{ $index }}" class="display text-xl">{{ __('Lecture :letter', ['letter' => $letters[$index]]) }}</h3>
                        <p class="mt-0.5 text-sm text-ink-muted">{{ __('Attente :wait (:gloss)', ['wait' => $reading->reading->wait->label(), 'gloss' => $reading->reading->wait->gloss()]) }}</p>

                        <ul class="mt-3 flex flex-wrap gap-x-3 gap-y-3" style="--tile: clamp(1.25rem, 3.6vw, 1.7rem)">
                            @foreach ($readingsHand->groups($reading) as $row)
                                <li class="flex flex-col items-center gap-1.5">
                                    <span class="tiles gap-px">
                                        @foreach ($row['tiles'] as $position => $tile)
                                            <x-tile :tile="$tile" :win="$row['win'] === $position" />
                                        @endforeach
                                    </span>
                                    <span class="text-xs text-ink-muted">{{ $row['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-3">
                            <x-answer-row wire:key="han-{{ $index }}" :id="'f-han-'.$index" :model="'answers.han'.$index" title="Han" :sub="__('dora compris')" :field="$fields['han'.$index] ?? null" />
                            <x-answer-row wire:key="fu-{{ $index }}" :id="'f-fu-'.$index" :model="'answers.fu'.$index" title="Fu" :sub="__('après arrondi')" :field="$fields['fu'.$index] ?? null" />
                        </div>

                        @if ($result)
                            <p class="mt-2 text-sm"><b>{{ __('Paie :points', ['points' => $readingsHand->payLine($reading)]) }}</b>@if ($reading->payment->limit) · {{ $reading->payment->limit->label() }}@endif</p>
                        @endif
                    </article>
                @endforeach

                <fieldset class="md:col-span-2">
                    <legend class="float-left mb-2 w-full font-bold">{{ __('La lecture qui compte') }}
                        @if ($choiceField && $choiceField['state'] === 'ok')<span class="ml-2 text-lg text-ok" aria-hidden="true">◎</span><span class="sr-only">{{ __('Juste') }}</span>@endif
                        @if ($choiceField && $choiceField['state'] === 'ko')<span class="ml-2 text-sm text-ko">→ {{ $choiceField['expected'] }}</span>@endif
                    </legend>
                    <div class="clear-both flex flex-wrap gap-2">
                        @foreach ($readingsHand->readings as $index => $reading)
                            <label @class([
                                'choice',
                                'is-right' => $choiceField && in_array($index, $best, true),
                                'is-wrong' => $choiceField && $choiceField['state'] === 'ko' && ($answers['choice'] ?? null) === (string) $index,
                            ])>
                                <input type="radio" class="sr-only" name="choice" value="{{ $index }}" wire:model="answers.choice" @disabled($result !== null)>
                                {{ __('Lecture :letter', ['letter' => $letters[$index]]) }}
                            </label>
                        @endforeach
                    </div>
                    @if ($choiceField)
                        <p class="mt-2 text-sm text-ink-muted">{{ $choiceField['why'] }}</p>
                    @endif
                </fieldset>
            </div>

            <footer class="sticky bottom-0 z-10 flex flex-wrap items-center gap-2 rounded-b-[0.875rem] border-t border-paper-line bg-paper/95 px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur sm:gap-3 sm:px-8 sm:py-4">
                @if (! $result)
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">{{ __('Valider') }}</button>
                    <button type="button" class="btn btn-quiet px-3 sm:px-[1.15rem]" wire:click="reveal" wire:loading.attr="disabled">{{ __('Voir la réponse') }}</button>
                @else
                    <button type="button" class="btn btn-primary" wire:click="nextHand" data-shortcut="n" x-init="$el.focus({ preventScroll: true })">{{ __('Main suivante') }}</button>
                @endif
                <a href="{{ localized_route('scoring') }}" class="btn ml-auto px-2 text-ink-muted hover:text-ink sm:px-3">{{ __('Barème') }}</a>
            </footer>
        </form>
    </section>
</div>
