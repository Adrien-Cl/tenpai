@use('App\Support\Format')
<x-layouts::app>
    @php
        $payment = $evaluation->payment();
        $yaku = collect($evaluation->yaku())->map(fn ($y) => $y->yaku->shortLabel());
        if ($evaluation->aka) {
            $yaku->push('aka-dora');
        }
        $redShown = false;
        $steps = [
            ['一', 'Yaku', 'Repérez les yaku de la main, par leur nom.'],
            ['二', 'Han', 'Donnez la valeur de chacun, main ouverte ou fermée.'],
            ['三', 'Dora', 'Comptez les dora, les cinq rouges et les ura-dora.'],
            ['四', 'Fu', 'Décomptez les fu groupe par groupe, puis arrondissez.'],
            ['五', 'Points', 'Trouvez la limite et ce que paie chaque joueur.'],
        ];
        $sprite = \App\View\Tiles\TileSprite::url();
    @endphp

    {{-- Héros : le titre, et l'inscription verticale dans l'espace libre, comme sur une estampe. --}}
    <section class="mx-auto max-w-6xl px-4 pt-8 sm:px-6 sm:pt-14" aria-labelledby="hero-title">
        <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_auto]">
            <div>
                <h1 id="hero-title" class="display max-w-[15ch] text-[2.5rem] sm:text-[4.1rem]">
                    Comptez une main de riichi en cinq étapes, sans calculette.
                </h1>
                <p class="mt-6 max-w-[54ch] text-lg text-ink-muted">
                    <span lang="ja" class="text-ink">聴牌</span>, tenpai : il ne manque plus qu’une tuile. Ici, la main est déjà gagnée ;
                    à vous de dire combien elle vaut. Chaque étape est corrigée et expliquée.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-4">
                    <a href="{{ route('training') }}" class="btn btn-primary h-14 px-7 text-lg" wire:navigate>Commencer une main</a>
                    <a href="{{ route('scoring') }}" class="font-bold text-ink underline decoration-ink-line decoration-2 underline-offset-[6px] hover:decoration-shu">Ouvrir le barème</a>
                </div>
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
                <div class="tiles hero-rack" style="--tile: clamp(1.2rem, 5.4vw, 3.6rem)" aria-label="Une main en attente d’une seule tuile">
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
                    <span class="display text-2xl text-gilt sm:text-3xl">Tsumo !</span>
                    <span>{{ $yaku->join(', ', ' et ') }}</span>
                    <span class="hidden text-felt-muted sm:inline" aria-hidden="true">·</span>
                    <span><b>{{ $evaluation->han }} han</b>, {{ strtolower($payment->limit?->label() ?? $evaluation->fu().' fu') }}</span>
                    <span class="display num text-2xl sm:text-3xl">{{ Format::points($payment->fromOthers) }} / {{ Format::points($payment->fromDealer) }}</span>
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
            <h2 id="steps-title" class="display text-2xl sm:text-3xl">Une main, cinq étapes</h2>
            <ol class="paper scroll-steps mt-6 grid sm:grid-cols-5">
                @foreach ($steps as [$kanji, $name, $text])
                    <li class="flex gap-4 px-5 py-5 sm:flex-col sm:gap-3 sm:py-6">
                        <span class="step-tile"><x-glyph :char="$kanji" class="size-[1.15rem]" /><span class="sr-only">Étape {{ $loop->iteration }}</span></span>
                        <span>
                            <span class="block font-bold">{{ $name }}</span>
                            <span class="mt-1 block text-sm text-ink-muted">{{ $text }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- Le défi du jour : un tanzaku, la bande de papier qu'on suspend aux bambous. --}}
        <a href="{{ route('daily') }}"
           x-data="{ get result() { return $store.progress.dailyResult(@js($today)) } }"
           class="tanzaku group mx-auto block w-full max-w-[17rem] lg:mt-2"
           aria-describedby="daily-state">
            <span class="tanzaku__string" aria-hidden="true"></span>
            <span class="paper tanzaku__paper flex flex-col items-center px-6 pt-8 pb-7 text-center">
                <span class="text-sm text-ink-muted">{{ ucfirst(\Illuminate\Support\Carbon::parse($today)->translatedFormat('l j F')) }}</span>
                <span class="display mt-2 text-[1.7rem] leading-tight">Défi du jour</span>
                <span class="mt-3 text-sm text-ink-muted">La même main pour tout le monde aujourd’hui. Comparez vos réponses à table.</span>
                <span class="mt-6 grid place-items-center" aria-hidden="true">
                    <span class="hanko" x-cloak x-show="result"><x-glyph char="正" class="size-8" /></span>
                    <span class="grid size-[4.25rem] place-items-center rounded-lg border-2 border-dashed border-ink-line text-ink-faint" x-show="!result"><x-glyph char="計" class="size-7 opacity-60" /></span>
                </span>
                <span id="daily-state" class="mt-4 font-bold">
                    <template x-if="result">
                        <span class="text-ok">Fait : <span x-text="result.score"></span>/5<span x-show="result.perfect">, sans faute</span></span>
                    </template>
                    <template x-if="!result">
                        <span class="underline decoration-shu decoration-2 underline-offset-4 group-hover:decoration-ink">Jouer la main du jour</span>
                    </template>
                </span>
            </span>
        </a>
    </section>

    {{-- Vos progrès : seulement s'il y en a. --}}
    <section x-data x-cloak x-show="$store.progress.hasPlayed" class="mx-auto mt-16 max-w-6xl px-4 sm:px-6" aria-labelledby="progress-title">
        <h2 id="progress-title" class="display text-2xl sm:text-3xl">Vos progrès</h2>
        <div class="mt-6 grid gap-10 lg:grid-cols-[auto_1fr] lg:items-end">
            <dl class="flex gap-10">
                <div><dt class="text-ink-muted">Mains</dt><dd class="display num text-4xl" x-text="$store.progress.data.hands"></dd></div>
                <div><dt class="text-ink-muted">Sans faute</dt><dd class="display num text-4xl" x-text="$store.progress.data.perfect"></dd></div>
                <div><dt class="text-ink-muted">Meilleure série</dt><dd class="display num text-4xl text-shu" x-text="$store.progress.data.best"></dd></div>
            </dl>
            <ul class="grid gap-3 sm:grid-cols-5 sm:gap-6">
                @foreach ($steps as $index => [$kanji, $name])
                    @php($key = ['yaku', 'han', 'dora', 'fu', 'points'][$index])
                    <li x-data="{ get rate() { return $store.progress.rate('{{ $key }}') } }">
                        <span class="flex items-baseline justify-between text-sm"><span>{{ $name }}</span><span class="num text-ink-muted" x-text="rate === null ? '–' : rate + ' %'"></span></span>
                        <span class="mt-2 block h-1.5 overflow-hidden rounded-full bg-ink-wash">
                            <span class="block h-full rounded-full bg-shu transition-[width] duration-700" :style="`width: ${rate ?? 0}%`"></span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
</x-layouts::app>
