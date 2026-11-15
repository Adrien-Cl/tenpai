{{--
    Un guide de la partie « Apprendre » : fil d'Ariane, titre, sommaire, puis le texte sur une feuille de papier.
    guide : le guide (App\View\Learn\Guide) · toc : les sections [ancre, titre] · intro : le chapeau.
--}}
@props(['guide', 'toc' => []])
<x-layouts::app :title="$guide->title()" :description="$guide->description()" :schema="\App\View\StructuredData::guide($guide)">
    <div class="mx-auto max-w-6xl px-4 pt-6 sm:px-6 sm:pt-10">
        <nav aria-label="{{ __('Fil d’Ariane') }}" class="text-sm text-ink-muted">
            <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <li><a href="{{ localized_route('home') }}" class="hover:text-ink">Tenpai</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ localized_route('learn') }}" class="hover:text-ink">{{ __('Apprendre') }}</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="text-ink">{{ $guide->label() }}</li>
            </ol>
        </nav>

        <header class="mt-5 flex items-start gap-4 sm:gap-6">
            <span class="step-tile mt-1 h-14! w-11! text-2xl sm:mt-2" aria-hidden="true"><x-glyph :char="$guide->glyph()" class="size-7" /></span>
            <div>
                <h1 class="display max-w-[22ch] text-[2rem] sm:text-5xl">{{ $guide->title() }}</h1>
                @isset($intro)
                    <p class="mt-4 max-w-[62ch] text-lg text-ink-muted">{{ $intro }}</p>
                @endisset
            </div>
        </header>

        <div class="mt-8 grid items-start gap-8 sm:mt-10 lg:grid-cols-[minmax(0,1fr)_16rem] lg:gap-10">
            <article class="paper guide px-5 py-7 sm:px-10 sm:py-10">
                {{ $slot }}
            </article>

            <aside class="flex flex-col gap-6 lg:sticky lg:top-6">
                @if ($toc)
                    <nav aria-labelledby="toc-title" class="paper p-5 max-lg:hidden">
                        <h2 id="toc-title" class="text-sm font-bold text-ink-muted">{{ __('Sur cette page') }}</h2>
                        <ol class="mt-3 space-y-1.5 border-l-2 border-paper-line text-sm">
                            @foreach ($toc as [$anchor, $label])
                                <li><a href="#{{ $anchor }}" class="-ml-0.5 block border-l-2 border-transparent py-0.5 pl-3 text-ink-muted hover:border-shu hover:text-ink">{{ $label }}</a></li>
                            @endforeach
                        </ol>
                    </nav>
                @endif

                <div class="paper p-5">
                    <p class="display text-xl">{{ __('Passez à la pratique') }}</p>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('Une main déjà gagnée, à compter en cinq étapes corrigées et expliquées.') }}</p>
                    <a href="{{ localized_route('counting', ['niveau' => \App\Domain\Mahjong\Generation\Level::Beginner->value]) }}" class="btn btn-primary mt-4 w-full" wire:navigate>{{ __('Compter une main facile') }}</a>
                    <a href="{{ localized_route('scoring') }}" class="mt-3 block text-center text-sm font-bold underline decoration-ink-line decoration-2 underline-offset-4 hover:decoration-shu">{{ __('Ouvrir le barème') }}</a>
                </div>
            </aside>
        </div>

        {{-- Le guide d'avant, celui d'après : on lit la partie comme un petit livre. --}}
        <nav class="mt-10 grid gap-3 sm:grid-cols-2" aria-label="{{ __('Les autres guides') }}">
            @if ($previous = $guide->previous())
                <a href="{{ localized_route($previous->routeName()) }}" class="paper group block px-5 py-4 hover:shadow-[inset_0_0_0_2px_var(--ink)]">
                    <span class="text-sm text-ink-muted">← {{ __('Guide précédent') }}</span>
                    <span class="mt-0.5 block font-bold group-hover:underline">{{ $previous->label() }}</span>
                </a>
            @else
                <a href="{{ localized_route('learn') }}" class="paper group block px-5 py-4 hover:shadow-[inset_0_0_0_2px_var(--ink)]">
                    <span class="text-sm text-ink-muted">← {{ __('Tous les guides') }}</span>
                    <span class="mt-0.5 block font-bold group-hover:underline">{{ __('Apprendre le mahjong riichi') }}</span>
                </a>
            @endif
            @if ($next = $guide->next())
                <a href="{{ localized_route($next->routeName()) }}" class="paper group block px-5 py-4 text-right hover:shadow-[inset_0_0_0_2px_var(--ink)] sm:col-start-2">
                    <span class="text-sm text-ink-muted">{{ __('Guide suivant') }} →</span>
                    <span class="mt-0.5 block font-bold group-hover:underline">{{ $next->label() }}</span>
                </a>
            @else
                <a href="{{ localized_route('counting') }}" class="paper group block px-5 py-4 text-right hover:shadow-[inset_0_0_0_2px_var(--ink)] sm:col-start-2" wire:navigate>
                    <span class="text-sm text-ink-muted">{{ __('Et maintenant') }} →</span>
                    <span class="mt-0.5 block font-bold group-hover:underline">{{ __('Commencer une main') }}</span>
                </a>
            @endif
        </nav>
    </div>
</x-layouts::app>
