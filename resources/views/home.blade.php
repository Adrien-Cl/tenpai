@use('App\Support\Format')
<x-layouts::app :schema="\App\View\StructuredData::home()">
    @php
        $payment = $evaluation->payment();
        $yaku = collect($evaluation->yaku())->map(fn ($y) => $y->yaku->shortLabel());
        if ($evaluation->dora->count) {
            $yaku->push(__('dora :count', ['count' => $evaluation->dora->count]));
        }
        if ($evaluation->aka) {
            $yaku->push(__('aka-dora'));
        }
        if ($evaluation->ura->count) {
            $yaku->push(__('ura-dora :count', ['count' => $evaluation->ura->count]));
        }
        $redShown = false;
        $steps = [
            ['一', 'Yaku', __('Repérez les yaku de la main, par leur nom.')],
            ['二', 'Han', __('Donnez la valeur de chacun, main ouverte ou fermée.')],
            ['三', 'Dora', __('Comptez les dora, les cinq rouges et les ura-dora.')],
            ['四', 'Fu', __('Décomptez les fu groupe par groupe, puis arrondissez.')],
            ['五', 'Points', __('Trouvez la limite et ce que paie chaque joueur.')],
        ];
        $sprite = \App\View\Tiles\TileSprite::url();
    @endphp

    {{-- Héros : le titre, et l'inscription verticale dans l'espace libre, comme sur une estampe. --}}
    <section class="mx-auto max-w-6xl px-4 pt-8 sm:px-6 sm:pt-14" aria-labelledby="hero-title">
        <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_auto]">
            <div>
                <h1 id="hero-title" class="display max-w-[15ch] text-[2.5rem] sm:text-[4.1rem]">
                    {{ __('Comptez une main de riichi en cinq étapes, sans calculette.') }}
                </h1>
                <p class="mt-6 max-w-[54ch] text-lg text-ink-muted">
                    <span lang="ja" class="text-ink">聴牌</span>, {{ __('tenpai : il ne manque plus qu’une tuile. Ici, la main est déjà gagnée ; à vous de dire combien elle vaut. Chaque étape est corrigée et expliquée.') }}
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-4">
                    <a href="{{ localized_route('counting') }}" class="btn btn-primary h-14 px-7 text-lg" wire:navigate>{{ __('Commencer une main') }}</a>
                    <a href="{{ localized_route('scoring') }}" class="font-bold text-ink underline decoration-ink-line decoration-2 underline-offset-[6px] hover:decoration-shu">{{ __('Ouvrir le barème') }}</a>
                </div>
                <p class="mt-6 flex flex-wrap items-center gap-2 text-sm">
                    <span class="mr-1 text-ink-muted">{{ __('Ou ciblez un yaku :') }}</span>
                    @foreach ([\App\Domain\Mahjong\Generation\Theme::Tanyao, \App\Domain\Mahjong\Generation\Theme::Pinfu, \App\Domain\Mahjong\Generation\Theme::Yakuhai, \App\Domain\Mahjong\Generation\Theme::Chiitoitsu, \App\Domain\Mahjong\Generation\Theme::Toitoi, \App\Domain\Mahjong\Generation\Theme::Honitsu] as $theme)
                        <a href="{{ localized_route('counting', ['theme' => $theme->value]) }}" title="{{ ucfirst($theme->gloss()) }}"
                           class="rounded-full bg-paper-sunk px-3 py-1.5 font-medium text-ink shadow-[inset_0_0_0_1px_var(--paper-line)] transition-colors hover:bg-ink hover:text-paper">{{ $theme->label() }}</a>
                    @endforeach
                    <a href="{{ localized_route('counting') }}" class="px-1 text-ink-muted underline decoration-ink-line underline-offset-4 hover:text-ink">{{ __('et les autres thèmes') }}</a>
                </p>
                <p class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                    <span class="mr-1 text-ink-muted">{{ __('Peu de temps ? Exercice court :') }}</span>
                    @foreach (\App\Training\Drill::cases() as $drill)
                        <a href="{{ $drill === \App\Training\Drill::Waits ? localized_route('waits') : localized_route('counting', ['exercice' => $drill->value]) }}"
                           class="rounded-full bg-paper-sunk px-3 py-1.5 font-medium text-ink shadow-[inset_0_0_0_1px_var(--paper-line)] transition-colors hover:bg-ink hover:text-paper">{{ $drill->label() }}</a>
                    @endforeach
                </p>
            </div>

            {{-- 聴牌, à l'encre, signé du sceau. --}}
            <div class="hidden flex-col items-center gap-3 pt-2 pr-6 lg:flex" aria-hidden="true">
                <svg class="w-[5.5rem] text-ink" viewBox="0 0 100 210">
                    <use href="{{ $sprite }}#{{ \App\View\Tiles\TileArt::glyphId('聴') }}" width="100" height="100" fill="currentColor"/>
                    <use href="{{ $sprite }}#{{ \App\View\Tiles\TileArt::glyphId('牌') }}" y="104" width="100" height="100" fill="currentColor"/>
                </svg>
                <x-seal class="size-12 rotate-3" />
            </div>
        </div>

        {{-- La table : une main tenpai, la tuile gagnante arrive. --}}
        <div class="felt hero mt-12 px-3 pt-10 pb-8 sm:mt-14 sm:px-10 sm:pt-12 sm:pb-10">
            <div class="flex flex-col items-center">
                <div class="tiles hero-rack" style="--tile: clamp(1.2rem, 5.4vw, 3.6rem)" aria-label="{{ __('Une main en attente d’une seule tuile') }}">
                    @foreach ($waiting as $t)
                        @php($red = $t === $redTile && ! $redShown)
                        @php($redShown = $redShown || $red)
                        <x-tile :tile="$t" :red="$red" />
                    @endforeach
                    <span class="hero-slot" aria-hidden="true">
                        <span class="hero-slot__ghost"></span>
                        <x-tile :tile="$win" class="hero-win" />
                    </span>
                </div>

                <div class="hero-slip mt-8 flex flex-wrap items-baseline justify-center gap-x-3 gap-y-1 px-2 text-center" aria-live="polite">
                    <span class="display text-2xl text-gilt sm:text-3xl">{{ $tsumo ? __('Tsumo !') : __('Ron !') }}</span>
                    <span>{{ $yaku->join(', ', ' '.__('et').' ') }}</span>
                    <span class="hidden text-felt-muted sm:inline" aria-hidden="true">·</span>
                    <span><b>{{ $evaluation->han }} han</b>, {{ strtolower($payment->limit?->label() ?? $evaluation->fu().' fu') }}</span>
                    @if (! $tsumo)
                        <span class="display num text-2xl sm:text-3xl">{{ Format::points($payment->ron) }}</span>
                    @elseif ($evaluation->dealer)
                        <span><span class="display num text-2xl sm:text-3xl">{{ Format::points($payment->each) }}</span> {{ __('chacun') }}</span>
                    @else
                        <span class="display num text-2xl sm:text-3xl">{{ Format::points($payment->fromOthers) }} / {{ Format::points($payment->fromDealer) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="kasumi mx-auto mt-14 max-w-6xl" aria-hidden="true">
        <span style="left: 4%; right: 46%; top: 0.3rem"></span>
        <span style="left: 30%; right: 12%; top: 1.45rem"></span>
    </div>

    <section class="mx-auto mt-10 grid max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_17rem] lg:gap-12" aria-labelledby="steps-title">
        {{-- Les cinq étapes sur un rouleau : c'est une vraie séquence, d'où les numéros. --}}
        <div>
            <h2 id="steps-title" class="display text-2xl sm:text-3xl">{{ __('Une main, cinq étapes') }}</h2>
            <ol class="paper scroll-steps mt-6 grid sm:grid-cols-5">
                @foreach ($steps as [$kanji, $name, $text])
                    <li class="flex gap-4 px-5 py-5 sm:flex-col sm:gap-3 sm:py-6">
                        <span class="step-tile"><x-glyph :char="$kanji" class="size-[1.15rem]" /><span class="sr-only">{{ __('Étape :step', ['step' => $loop->iteration]) }}</span></span>
                        <span>
                            <span class="block font-bold">{{ $name }}</span>
                            <span class="mt-1 block text-sm text-ink-muted">{{ $text }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Le défi du jour : un tanzaku, la bande de papier qu'on suspend aux bambous. --}}
        <div class="lg:mt-2" x-data="{ copied: false, get result() { return $store.progress.dailyResult(@js($today)) } }">
        <a href="{{ localized_route('daily') }}"
           class="tanzaku group mx-auto block w-full max-w-[17rem]"
           aria-describedby="daily-state">
            <span class="tanzaku__string" aria-hidden="true"></span>
            <span class="paper tanzaku__paper flex flex-col items-center px-6 pt-8 pb-7 text-center">
                <span class="text-sm text-ink-muted">{{ ucfirst(\Illuminate\Support\Carbon::parse($today)->translatedFormat(__('l j F'))) }}</span>
                <span class="display mt-2 text-[1.7rem] leading-tight">{{ __('Défi du jour') }}</span>
                <span class="mt-3 text-sm text-ink-muted">{{ __('La même main pour tout le monde aujourd’hui. Comparez vos réponses à table.') }}</span>
                <span class="mt-6 grid place-items-center" aria-hidden="true">
                    <span class="hanko" x-cloak x-show="result"><x-glyph char="正" class="size-8" /></span>
                    <span class="grid size-[4.25rem] place-items-center rounded-lg border-2 border-dashed border-ink-line text-ink-faint" x-show="!result"><x-glyph char="計" class="size-7 opacity-60" /></span>
                </span>
                <span id="daily-state" class="mt-4 font-bold">
                    <template x-if="result">
                        <span class="text-ok">{{ __('Fait :') }} <span x-text="result.score"></span>/5<span x-show="result.perfect">{{ __(', sans faute') }}</span></span>
                    </template>
                    <template x-if="!result">
                        <span class="underline decoration-shu decoration-2 underline-offset-4 group-hover:decoration-ink">{{ __('Jouer la main du jour') }}</span>
                    </template>
                </span>
            </span>
        </a>
        <p class="mt-3 text-center" x-cloak x-show="result">
            <button type="button" class="btn btn-quiet"
                    x-on:click="copied = await $store.progress.shareDaily(@js($today), @js(localized_route('daily'))); setTimeout(() => copied = false, 2200)">
                <span x-show="!copied">{{ __('Partager mon résultat') }}</span>
                <span x-cloak x-show="copied">{{ __('Résultat copié') }}</span>
            </button>
        </p>
        </div>
    </section>

    {{-- Débuter : les guides, pour qui découvre le riichi. --}}
    <section class="mx-auto mt-16 max-w-6xl px-4 sm:px-6" aria-labelledby="learn-title">
        <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
            <h2 id="learn-title" class="display text-2xl sm:text-3xl">{{ __('Débuter au riichi') }}</h2>
            <a href="{{ localized_route('learn') }}" class="font-bold underline decoration-ink-line decoration-2 underline-offset-[6px] hover:decoration-shu">{{ __('Apprendre le mahjong riichi') }}</a>
        </div>
        <p class="mt-2 max-w-[62ch] text-ink-muted">{{ __('Nouveau venu ? Les règles, le déroulement d’une partie et le calcul des points, expliqués simplement avant de passer à la pratique.') }}</p>
        <ul class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (\App\View\Learn\Guide::cases() as $guide)
                <li>
                    <a href="{{ localized_route($guide->routeName()) }}" class="paper group flex h-full flex-col gap-3 p-5 transition-shadow hover:shadow-[inset_0_0_0_2px_var(--ink)]">
                        <span class="step-tile" aria-hidden="true"><x-glyph :char="$guide->glyph()" class="size-[1.15rem]" /></span>
                        <span class="font-bold group-hover:underline group-hover:decoration-shu group-hover:decoration-2 group-hover:underline-offset-4">{{ $guide->label() }}</span>
                        <span class="text-sm text-ink-muted">{{ $guide->summary() }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Vos progrès : seulement s'il y en a. --}}
    <section x-data x-cloak x-show="$store.progress.hasPlayed" class="mx-auto mt-16 max-w-6xl px-4 sm:px-6" aria-labelledby="progress-title">
        <h2 id="progress-title" class="display text-2xl sm:text-3xl">{{ __('Vos progrès') }}</h2>
        {{-- Deux colonnes : les chiffres et la réussite par étape, puis le calendrier des défis. --}}
        <div class="mt-6 grid gap-12 lg:grid-cols-[minmax(0,1fr)_19rem] lg:gap-20">
            <div>
                <dl class="flex flex-wrap gap-x-10 gap-y-4">
                    <div><dt class="text-ink-muted">{{ __('Mains') }}</dt><dd class="display num text-4xl" x-text="$store.progress.data.hands"></dd></div>
                    <div><dt class="text-ink-muted">{{ __('Sans faute') }}</dt><dd class="display num text-4xl" x-text="$store.progress.data.perfect"></dd></div>
                    <div><dt class="text-ink-muted">{{ __('Meilleure série') }}</dt><dd class="display num text-4xl text-shu" x-text="$store.progress.data.best"></dd></div>
                </dl>

                <h3 class="display mt-10 text-xl">{{ __('Réussite par étape') }}</h3>
                <ul class="mt-4 max-w-2xl space-y-3.5">
                    @foreach ($steps as $index => [$kanji, $name])
                        @php($key = ['yaku', 'han', 'dora', 'fu', 'points'][$index])
                        <li class="grid grid-cols-[4.5rem_minmax(0,1fr)_3rem] items-center gap-4" x-data="{ get rate() { return $store.progress.rate('{{ $key }}') } }">
                            <span>{{ $name }}</span>
                            <span class="block h-2 overflow-hidden rounded-full bg-ink-wash">
                                <span class="block h-full rounded-full bg-shu transition-[width] duration-700" :style="`width: ${rate ?? 0}%`"></span>
                            </span>
                            <span class="num text-right text-sm text-ink-muted" x-text="rate === null ? '–' : rate + ' %'"></span>
                        </li>
                    @endforeach
                </ul>

                <ul class="mt-8 flex flex-wrap gap-x-8 gap-y-2 text-sm" x-show="Object.keys($store.progress.drills).length">
                    @foreach (\App\Training\Drill::cases() as $drill)
                        <li x-data="{ get result() { return $store.progress.drillResult('{{ $drill->value }}') } }" x-show="result">
                            <span class="text-ink-muted">{{ __('Exercice :drill :', ['drill' => mb_strtolower($drill->label())]) }}</span>
                            <b class="num" x-text="result?.perfect"></b> {{ __('sans faute sur') }} <b class="num" x-text="result?.hands"></b>
                        </li>
                    @endforeach
                </ul>

                {{-- Ce qui reste à travailler : les mains ratées, à refaire, et les yaku oubliés. --}}
                <div class="mt-10 space-y-10"
                     x-data="reviewList({
                         sprite: @js($sprite),
                         url: @js(localized_route('counting.hand', ['seed' => 'SEED'])),
                         tilesUrl: @js(route('hands.tiles', absolute: false)),
                         yaku: @js(collect(\App\Domain\Mahjong\Yaku::cases())->mapWithKeys(fn ($yaku) => [$yaku->value => $yaku->shortLabel()])),
                         captions: @js([
                             'themes' => collect(\App\Domain\Mahjong\Generation\Theme::cases())->mapWithKeys(fn ($theme) => [$theme->value => $theme->label()]),
                             'levels' => collect(\App\Domain\Mahjong\Generation\Level::cases())->mapWithKeys(fn ($level) => [$level->value => $level->label()]),
                             'free' => \App\Domain\Mahjong\Generation\Theme::Free->label(),
                             'daily' => __('Défi du :day'),
                         ]),
                     })"
                     x-show="$store.progress.review.length || $store.progress.mostMissed().length">
                    {{-- Chaque main ratée telle qu'elle était sur la table, avec les étapes à reprendre. --}}
                    <div x-show="$store.progress.review.length">
                        <h3 class="display text-xl">{{ __('À revoir') }}</h3>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('Une main refaite sans faute quitte la liste.') }}</p>
                        <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                            <template x-for="entry in entries" :key="entry.seed">
                                <li>
                                    <a :href="link(entry)"
                                       class="@container flex h-full flex-col rounded-lg bg-paper-sunk px-3.5 pt-2.5 pb-3 shadow-[inset_0_0_0_1px_var(--paper-line)] transition-shadow hover:shadow-[inset_0_0_0_2px_var(--ink)]">
                                        <span class="flex items-baseline justify-between gap-3 text-sm">
                                            <span class="font-bold" x-text="caption(entry)"></span>
                                            <span class="num font-bold text-ko"><span x-text="entry.score"></span>/5</span>
                                        </span>
                                        <span class="mt-3 flex flex-wrap items-end gap-x-1.5 gap-y-2.5 [--tile:clamp(0.8rem,calc((100cqi-1.5rem)/16.5),1.3rem)]" aria-hidden="true"
                                              x-show="entry.tiles" x-html="handHtml(entry)"></span>
                                        <span class="mt-1 block font-mono text-sm text-ink-muted" x-show="!entry.tiles" x-text="entry.seed"></span>
                                        <span class="mt-auto flex flex-wrap gap-1 pt-3 text-xs" x-show="Array.isArray(entry.steps)">
                                            @foreach ($steps as $index => [, $name])
                                                <span class="rounded px-1.5 py-0.5" :class="entry.steps?.[{{ $index }}] ? 'text-ink-faint' : 'bg-ko-soft font-bold text-ko'">{{ $name }}<span class="sr-only" x-show="!entry.steps?.[{{ $index }}]"> ({{ __('raté') }})</span></span>
                                            @endforeach
                                        </span>
                                    </a>
                                </li>
                            </template>
                        </ul>
                    </div>
                    <div class="max-w-md" x-show="$store.progress.mostMissed().length">
                        <h3 class="display text-xl">{{ __('Yaku oubliés') }}</h3>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('Ceux que vous laissez passer le plus souvent.') }}</p>
                        <ol class="mt-3 space-y-1.5">
                            <template x-for="[id, count] in $store.progress.mostMissed()" :key="id">
                                <li class="flex items-baseline justify-between gap-4 border-b border-ink-line pb-1.5 text-sm">
                                    <span x-text="labels[id] ?? id"></span>
                                    <span class="num text-ink-muted" x-text="count === 1 ? @js(__('1 fois')) : count + ' ' + @js(__('fois'))"></span>
                                </li>
                            </template>
                        </ol>
                    </div>
                </div>
            </div>

            {{-- Les défis des cinq dernières semaines : un jour manqué se rattrape. --}}
            <div class="max-w-sm" x-data="dailyCalendar({ today: @js($today), url: @js(localized_route('counting.hand', ['seed' => 'defi-DATE'])) })">
                <h3 class="display text-xl">{{ __('Défis du jour') }}</h3>
                <p class="mt-1 text-sm text-ink-muted">
                    <span x-show="streak">{{ __('Série en cours :') }} <b class="num text-ink" x-text="streak"></b> <span x-text="streak > 1 ? @js(__('jours')) : @js(__('jour'))"></span>.</span>
                    <span x-show="!streak">{{ __('Un jour manqué se rattrape : touchez sa case.') }}</span>
                </p>
                <div class="mt-4 grid grid-cols-7 gap-1.5 text-center text-sm">
                    <template x-for="(name, index) in weekdays" :key="'wd' + index">
                        <span class="text-xs text-ink-faint" aria-hidden="true" x-text="name"></span>
                    </template>
                    <template x-for="day in days" :key="day.date">
                        <span>
                            <template x-if="day.future">
                                <span class="block aspect-square" aria-hidden="true"></span>
                            </template>
                            <template x-if="!day.future">
                                <a :href="day.url" :aria-label="day.label" :title="day.label"
                                   class="num grid aspect-square place-items-center rounded-md border transition-colors"
                                   :class="{
                                       'border-shu bg-shu font-bold text-white': day.result?.perfect,
                                       'border-transparent bg-ok-soft text-ink': day.result && !day.result.perfect,
                                       'border-dashed border-ink-line text-ink-faint hover:text-ink': !day.result,
                                       'ring-2 ring-ink ring-offset-2 ring-offset-paper': day.today,
                                   }"
                                   x-text="day.day"></a>
                            </template>
                        </span>
                    </template>
                </div>
                <p class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-ink-muted" aria-hidden="true">
                    <span class="flex items-center gap-1.5"><span class="size-3 rounded-sm bg-shu"></span>{{ __('Sans faute') }}</span>
                    <span class="flex items-center gap-1.5"><span class="size-3 rounded-sm bg-ok-soft"></span>{{ __('Joué') }}</span>
                    <span class="flex items-center gap-1.5"><span class="size-3 rounded-sm border border-dashed border-ink-line"></span>{{ __('Pas joué') }}</span>
                </p>
            </div>
        </div>
    </section>
</x-layouts::app>
