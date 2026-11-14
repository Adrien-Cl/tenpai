{{--
    Exercice, niveau et thème, en boutons : un clic tire une nouvelle main. Ce sont des liens,
    ils marchent sans JavaScript.
    route : page d'entrée (« training » pour le comptage, « waits » pour les attentes)
    current : exercice en cours (null pour la main complète) · theme : thème de la main · level : niveau de la main
--}}
@php
    $route ??= 'counting';
    $current ??= null;
    $withDrills = $route === 'counting';
    $withTheme = $current?->usesTheme() ?? true;
    $params = [
        'exercice' => $current?->value,
        'theme' => $theme?->value,
        'niveau' => $level === \App\Domain\Mahjong\Generation\Level::Standard ? null : $level->value,
    ];
    $link = fn (array $changes): string => localized_route($route, array_filter([...$params, ...$changes]));
    $segment = 'flex min-h-11 flex-col justify-center rounded-lg px-3.5 py-1.5 text-left leading-tight transition-colors';
    $segmentIdle = 'bg-paper-sunk text-ink shadow-[inset_0_0_0_1px_var(--paper-line)] hover:shadow-[inset_0_0_0_1px_var(--ink-muted)]';
    $segmentActive = 'bg-ink text-paper';
    $drills = [
        [null, __('Main complète'), __('les cinq étapes')],
        ...array_map(fn (\App\Training\Drill $drill): array => [$drill, $drill->label(), $drill->hint()], \App\Training\Drill::counting()),
    ];
@endphp
<div class="mb-4 space-y-3 sm:mb-6" data-picker>
    @if ($withDrills)
        <nav class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap" aria-label="{{ __('Exercice') }}">
            @foreach ($drills as [$drill, $label, $hint])
                @php($active = $current === $drill)
                <a href="{{ $link(['exercice' => $drill?->value]) }}" wire:navigate
                   @class([$segment, $active ? $segmentActive : $segmentIdle])
                   @if ($active) aria-current="page" @endif>
                    <span class="font-bold">{{ $label }}</span>
                    <span @class(['text-xs', $active ? 'text-paper/75' : 'text-ink-muted'])>{{ $hint }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    <div class="flex flex-wrap items-center gap-x-4 gap-y-3" x-data="{ themesOpen: false }" x-on:keydown.escape="themesOpen = false">
        <div class="flex items-center gap-3">
            <span class="text-sm text-ink-muted" id="picker-level">{{ __('Niveau') }}</span>
            <div class="inline-flex rounded-lg bg-paper-sunk p-1 shadow-[inset_0_0_0_1px_var(--paper-line)]" role="group" aria-labelledby="picker-level">
                @foreach (\App\Domain\Mahjong\Generation\Level::cases() as $option)
                    @php($active = $level === $option)
                    <a href="{{ $link(['niveau' => $option === \App\Domain\Mahjong\Generation\Level::Standard ? null : $option->value]) }}" wire:navigate
                       title="{{ ucfirst($option->gloss()) }}"
                       @class(['rounded-md px-3 py-1.5 text-sm font-bold transition-colors', $active ? 'bg-ink text-paper' : 'text-ink-muted hover:text-ink'])
                       @if ($active) aria-current="page" @endif>{{ $option->label() }}</a>
                @endforeach
            </div>
            <span class="hidden text-sm text-ink-muted lg:inline">{{ ucfirst($level->gloss()) }}</span>
        </div>

        @if ($withTheme)
            <button type="button" x-on:click="themesOpen = !themesOpen" x-bind:aria-expanded="themesOpen" aria-expanded="false" aria-controls="picker-themes"
                    class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-paper-sunk px-3.5 text-sm shadow-[inset_0_0_0_1px_var(--paper-line)] transition-shadow hover:shadow-[inset_0_0_0_1px_var(--ink-muted)] sm:ml-auto">
                <span class="text-ink-muted">{{ __('Thème') }}</span>
                <span class="font-bold">{{ $theme?->label() ?? __('Main libre') }}</span>
                <svg class="size-4 text-ink-muted transition-transform" x-bind:class="themesOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </button>

            {{-- Les thèmes, tous visibles d'un coup : un clic suffit. --}}
            <div id="picker-themes" x-cloak x-show="themesOpen" x-collapse class="w-full">
                <div class="flex flex-wrap gap-2 rounded-xl bg-paper-sunk/60 p-3 shadow-[inset_0_0_0_1px_var(--paper-line)]">
                    @foreach ([null, ...\App\Domain\Mahjong\Generation\Theme::trainable()] as $option)
                        @php($active = $theme === $option)
                        <a href="{{ $link(['theme' => $option?->value]) }}" wire:navigate
                           title="{{ ucfirst($option?->gloss() ?? __('tous les yaku')) }}"
                           @class(['rounded-full px-3 py-1.5 text-sm font-medium transition-colors', $active ? 'bg-ink text-paper' : 'bg-paper text-ink shadow-[inset_0_0_0_1px_var(--paper-line)] hover:bg-ink hover:text-paper'])
                           @if ($active) aria-current="page" @endif>{{ $option?->label() ?? __('Main libre') }}</a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
