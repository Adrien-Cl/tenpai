<x-layouts::app :title="__('Apprendre le mahjong riichi : règles et guides pour débutants')"
                :description="__('Débuter au mahjong riichi : les tuiles, le déroulement d’une partie, le calcul des points et le lexique de la table, expliqués simplement et en français.')"
                :schema="\App\View\StructuredData::learn()">
    <div class="mx-auto max-w-6xl px-4 pt-6 sm:px-6 sm:pt-10">
        <nav aria-label="{{ __('Fil d’Ariane') }}" class="text-sm text-ink-muted">
            <ol class="flex flex-wrap items-center gap-x-2">
                <li><a href="{{ localized_route('home') }}" class="hover:text-ink">Tenpai</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="text-ink">{{ __('Apprendre') }}</li>
            </ol>
        </nav>

        <div class="mt-5 grid items-end gap-8 lg:grid-cols-[minmax(0,1fr)_auto]">
            <div>
                <h1 class="display max-w-[16ch] text-[2.4rem] sm:text-6xl">{{ __('Apprendre le mahjong riichi') }}</h1>
                <p class="mt-5 max-w-[62ch] text-lg text-ink-muted">
                    {{ __('Le riichi est le mahjong japonais : quatre joueurs, 136 tuiles, et une main de quatorze tuiles à former avant les autres. Ses règles tiennent en quelques pages ; son compte des points fait peur, mais il s’apprend en suivant une méthode.') }}
                </p>
            </div>
            <x-tile-row tiles="123m 406p 77z" size="clamp(1.6rem, 6vw, 2.6rem)" class="max-lg:hidden" aria-hidden="true" />
        </div>

        {{-- Le parcours : quatre guides, dans l'ordre où un débutant les lit. --}}
        <ol class="mt-10 grid gap-4 sm:grid-cols-2">
            @foreach (\App\View\Learn\Guide::cases() as $guide)
                <li>
                    <a href="{{ localized_route($guide->routeName()) }}" class="paper group flex h-full gap-5 p-6 transition-shadow hover:shadow-[inset_0_0_0_2px_var(--ink)] sm:p-7">
                        <span class="step-tile h-14! w-11! text-2xl" aria-hidden="true"><x-glyph :char="$guide->glyph()" class="size-7" /></span>
                        <span>
                            <span class="text-sm text-ink-muted">{{ __('Guide :number', ['number' => $loop->iteration]) }}</span>
                            <span class="display mt-1 block text-2xl group-hover:underline group-hover:decoration-shu group-hover:decoration-2 group-hover:underline-offset-4">{{ $guide->label() }}</span>
                            <span class="mt-2 block text-ink-muted">{{ $guide->summary() }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ol>

        <div class="kasumi mx-auto mt-14" aria-hidden="true">
            <span style="left: 6%; right: 44%; top: 0.3rem"></span>
            <span style="left: 28%; right: 10%; top: 1.45rem"></span>
        </div>

        {{-- L'essentiel en trois phrases, pour qui n'a que deux minutes. --}}
        <section class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]" aria-labelledby="essentials-title">
            <div>
                <h2 id="essentials-title" class="display text-2xl sm:text-3xl">{{ __('Le riichi en trois phrases') }}</h2>
                <ol class="mt-5 space-y-4">
                    @foreach ([
                        __('On gagne avec quatorze tuiles : quatre groupes (suites ou brelans) et une paire.'),
                        __('Il faut au moins un yaku, une combinaison reconnue : le plus simple est de déclarer riichi avec une main fermée, à une tuile de gagner.'),
                        __('La main gagnée vaut des han (ses yaku et ses dora) et des fu (sa composition) ; ensemble, ils donnent les points que paient les autres joueurs.'),
                    ] as $sentence)
                        <li class="flex gap-4">
                            <span class="step-tile" aria-hidden="true"><x-glyph :char="['一', '二', '三'][$loop->index]" class="size-[1.05rem]" /></span>
                            <span class="max-w-[62ch] pt-1">{{ $sentence }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
            <div class="paper p-6">
                <p class="display text-xl">{{ __('Apprendre en jouant') }}</p>
                <p class="mt-2 text-sm text-ink-muted">{{ __('Le niveau débutant tire des mains sans carré ni situation rare. Chaque étape est corrigée et expliquée.') }}</p>
                <a href="{{ localized_route('counting', ['niveau' => \App\Domain\Mahjong\Generation\Level::Beginner->value]) }}" class="btn btn-primary mt-4 w-full" wire:navigate>{{ __('Compter une main facile') }}</a>
                <a href="{{ localized_route('waits') }}" class="mt-3 block text-center text-sm font-bold underline decoration-ink-line decoration-2 underline-offset-4 hover:decoration-shu" wire:navigate>{{ __('S’exercer aux attentes') }}</a>
            </div>
        </section>
    </div>
</x-layouts::app>
