@props(['title' => null, 'description' => null, 'image' => null, 'imageAlt' => null, 'robots' => null, 'schema' => null])
@php
    $season = \App\View\Season::current(now(), request()->query('saison'));
    $tableRules = \App\Support\TableRules::fromRequest(request());
    $backdrop = \App\View\Backdrop::fromRequest(request());
    $locale = app()->getLocale();
    $alternates = \App\Support\Locale::alternates(request());
    $currentRoute = \App\Support\Locale::baseRouteName(request()->route()?->getName() ?? '');
    $isCurrent = fn (array|string $pattern): bool => \Illuminate\Support\Str::is($pattern, $currentRoute);
    $pageTitle = $title ? $title.' · Tenpai' : __('Tenpai · Apprendre à compter les points au mahjong riichi');
    $pageDescription = $description ?? __('Apprenez à compter les points au mahjong riichi : yaku, han, dora, fu et points, en cinq étapes corrigées. Gratuit, avec les règles et un lexique pour débutants.');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" data-season="{{ $season->value }}" @if ($backdrop) data-theme="{{ $backdrop->value }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    {{-- Les mains tirées au hasard sont innombrables : on les laisse hors des moteurs de recherche, qui suivent quand même leurs liens. --}}
    @if ($robots)
        <meta name="robots" content="{{ $robots }}">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    {{-- La barre du navigateur suit le système, ou le fond choisi. --}}
    @foreach (\App\View\Backdrop::cases() as $scheme)
        <meta name="theme-color" content="{{ ($backdrop ?? $scheme)->themeColor() }}" media="(prefers-color-scheme: {{ $scheme->value }})">
    @endforeach
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    @foreach ($alternates as $alternateLocale => $alternateUrl)
        <link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ $alternateUrl }}">
    @endforeach
    @if ($alternates)
        <link rel="alternate" hreflang="x-default" href="{{ $alternates[\App\Support\Locale::DEFAULT] }}">
    @endif
    {{-- Aperçu des liens partagés : l'image de la main sur une page de main, l'image du site ailleurs. --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Tenpai">
    <meta property="og:locale" content="{{ \App\Support\Locale::LOCALES[$locale]['native'] }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:image" content="{{ $image ?? asset(__('og.png')) }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $imageAlt ?? __('Tenpai : comptez une main de riichi, sans calculette.') }}">
    <meta name="twitter:card" content="summary_large_image">
    @if ($schema)
        <script type="application/ld+json">{!! \App\View\StructuredData::toJson($schema) !!}</script>
    @endif
    @fonts
    {{-- Les textes de l'interface écrits en JavaScript, dans la langue de la page. --}}
    <script>window.tenpaiText = @js(\App\Support\ScriptText::translations());</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-dvh flex-col">
    <x-scenery :season="$season" />

    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-paper focus:px-4 focus:py-2 focus:text-ink">{{ __('Aller au contenu') }}</a>

    {{--
        En-tête : sur grand écran, une rangée (liens, puis réglages, fond, langue).
        Sous md, la rangée ne tient pas : un bouton menu ouvre les mêmes éléments en panneau.
    --}}
    @php($customRules = ! \App\Support\TableRules::isDefault($tableRules))
    @php($utilityClass = 'relative flex min-h-11 w-full shrink-0 items-center gap-3 rounded-lg px-3 text-ink-muted transition-colors hover:bg-paper-sunk hover:text-ink lg:size-10 lg:min-h-0 lg:justify-center lg:px-0')
    <header x-data="{ menuOpen: false }" x-on:keydown.escape.window="menuOpen = false" x-on:click.outside="menuOpen = false"
            class="relative z-20 mx-auto flex w-full max-w-6xl items-center gap-x-6 px-4 pt-[max(1rem,env(safe-area-inset-top))] pb-3 sm:px-6 sm:pt-6">
        <a href="{{ localized_route('home') }}" class="group flex items-center gap-3 rounded-lg" aria-label="{{ __('Tenpai, accueil') }}">
            <x-seal class="size-10 transition-transform group-hover:-rotate-3 sm:size-11" />
            <span class="display text-xl tracking-wide text-ink sm:text-2xl">Tenpai</span>
        </a>

        <button type="button" x-on:click="menuOpen = !menuOpen" x-bind:aria-expanded="menuOpen" aria-controls="site-menu"
                x-bind:aria-label="menuOpen ? @js(__('Fermer le menu')) : @js(__('Ouvrir le menu'))" aria-label="{{ __('Ouvrir le menu') }}"
                class="relative ml-auto grid size-11 shrink-0 place-items-center rounded-lg text-ink transition-colors hover:bg-paper-sunk lg:hidden">
            <svg x-show="!menuOpen" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
            <svg x-cloak x-show="menuOpen" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M6 6l12 12M18 6 6 18"/>
            </svg>
            @if ($customRules)
                <span class="absolute top-2 right-2 size-2 rounded-full bg-shu" aria-hidden="true"></span>
            @endif
        </button>

        <div id="site-menu" x-bind:class="{ 'max-lg:flex!': menuOpen }"
             class="paper absolute inset-x-4 top-full z-40 hidden flex-col gap-1 p-2.5 lg:static lg:ml-auto lg:flex lg:flex-row lg:items-center lg:gap-6 lg:bg-transparent lg:p-0 lg:shadow-none">
            <nav class="flex flex-col gap-0.5 lg:flex-row lg:items-center lg:gap-2" aria-label="{{ __('Navigation principale') }}">
                @foreach ([
                    ['counting', __('Comptage'), ['counting*', 'drill*', 'readings*']],
                    ['waits', __('Attentes'), 'waits*'],
                    ['daily', __('Défi du jour'), 'daily'],
                    ['scoring', __('Barème'), 'scoring'],
                    ['learn', __('Apprendre'), 'learn*'],
                ] as [$route, $label, $pattern])
                    <a href="{{ localized_route($route) }}" x-on:click="menuOpen = false"
                       @class([
                           'relative flex min-h-11 items-center whitespace-nowrap rounded-lg px-3 font-medium transition-colors lg:min-h-0 lg:py-2',
                           'text-ink max-lg:bg-paper-sunk after:absolute after:bottom-0.5 after:h-[3px] after:rounded-full after:bg-shu max-lg:after:inset-y-2.5 max-lg:after:left-0 max-lg:after:h-auto max-lg:after:w-[3px] lg:after:inset-x-3' => $isCurrent($pattern),
                           'text-ink-muted hover:text-ink' => ! $isCurrent($pattern),
                       ])
                       @if ($isCurrent($pattern)) aria-current="page" @endif
                       @if (in_array($route, ['counting', 'waits'], true)) wire:navigate @endif>{{ $label }}</a>
                @endforeach
            </nav>

            <div class="flex flex-col gap-0.5 border-t border-paper-line pt-1.5 lg:flex-row lg:items-center lg:gap-6 lg:border-0 lg:pt-0">
                {{-- Réglages : les règles de la table. Un point rouge quand elles diffèrent des règles par défaut. --}}
                <button type="button" x-on:click="menuOpen = false; $dispatch('open-rules')" class="{{ $utilityClass }}"
                        aria-label="{{ __('Règles de la table') }}" title="{{ __('Règles de la table') }}" aria-haspopup="dialog">
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M4 7h9M17 7h3M4 17h3M11 17h9"/>
                        <circle cx="15" cy="7" r="2"/>
                        <circle cx="9" cy="17" r="2"/>
                    </svg>
                    <span class="lg:hidden" aria-hidden="true">{{ __('Règles de la table') }}</span>
                    @if ($customRules)
                        <span class="size-2 rounded-full bg-shu lg:absolute lg:top-1.5 lg:right-1.5" aria-hidden="true"></span>
                        <span class="sr-only">{{ __('(règles personnalisées)') }}</span>
                    @endif
                </button>

                {{-- Fond : la lune pour passer à la nuit, le soleil pour revenir au jour. --}}
                <button type="button" x-data="backdropToggle(@js(\App\View\Backdrop::themeColors()))"
                        x-on:click="toggle" x-bind:aria-pressed="dark" class="{{ $utilityClass }}"
                        aria-label="{{ __('Fond foncé') }}" title="{{ __('Fond foncé') }}">
                    <svg class="size-5 shrink-0 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z"/>
                    </svg>
                    <svg class="hidden size-5 shrink-0 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="4"/>
                        <path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4"/>
                    </svg>
                    <span class="lg:hidden" aria-hidden="true">{{ __('Fond foncé') }}</span>
                    <span class="ml-auto h-5 w-9 rounded-full p-0.5 transition-colors lg:hidden" x-bind:class="dark ? 'bg-ink' : 'bg-paper-line'" aria-hidden="true">
                        <span class="block size-4 rounded-full bg-paper shadow transition-transform" x-bind:class="dark && 'translate-x-4'"></span>
                    </span>
                </button>

                {{-- Langue : le code de l'autre langue (le nom en entier dans le menu), qui mène à la même page traduite. --}}
                @foreach ($alternates as $alternateLocale => $alternateUrl)
                    @continue($alternateLocale === $locale)
                    @php($localeName = \App\Support\Locale::LOCALES[$alternateLocale]['name'])
                    <a href="{{ $alternateUrl }}" hreflang="{{ $alternateLocale }}" lang="{{ $alternateLocale }}"
                       class="{{ $utilityClass }} lg:min-w-10 lg:px-1.5" aria-label="{{ $localeName }}" title="{{ $localeName }}">
                        <span class="grid w-5 shrink-0 place-items-center text-sm font-bold tracking-wide lg:w-auto">{{ strtoupper($alternateLocale) }}</span>
                        <span class="lg:hidden" aria-hidden="true">{{ $localeName }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </header>

    <main id="contenu" class="relative z-10 w-full flex-1">
        {{ $slot }}
    </main>

    <footer class="relative z-10 mx-auto mt-20 w-full max-w-6xl px-4 pb-[max(2rem,env(safe-area-inset-bottom))] text-sm text-ink-muted sm:px-6">
        <div class="kasumi -mb-2" aria-hidden="true">
            <span style="left: 0; right: 38%; top: 0.4rem"></span>
            <span style="left: 22%; right: 6%; top: 1.4rem"></span>
        </div>
        {{-- Pour débuter : les guides, à portée de chaque page. --}}
        <nav class="mb-5 flex flex-wrap gap-x-5 gap-y-2" aria-label="{{ __('Apprendre le mahjong riichi') }}">
            <a href="{{ localized_route('learn') }}" class="font-bold text-ink underline decoration-ink-line underline-offset-4 hover:decoration-shu">{{ __('Apprendre le mahjong riichi') }}</a>
            @foreach (\App\View\Learn\Guide::cases() as $guide)
                <a href="{{ localized_route($guide->routeName()) }}" class="underline decoration-ink-line underline-offset-4 hover:text-ink hover:decoration-shu">{{ $guide->label() }}</a>
            @endforeach
            <a href="{{ localized_route('scoring') }}" class="underline decoration-ink-line underline-offset-4 hover:text-ink hover:decoration-shu">{{ __('Barème') }}</a>
        </nav>
        <div class="flex flex-wrap items-baseline justify-between gap-x-8 gap-y-2">
            <p>
                {{ __(':label : kuitan, :rules, :honba.', [
                    'label' => \App\Support\TableRules::name($tableRules),
                    'rules' => \App\Support\TableRules::describe($tableRules),
                    'honba' => $tableRules->honba ? __('honba et bâtons de riichi comptés') : __('sans honba ni bâton de riichi'),
                ]) }}
                <br>{{ __('Vos progrès restent dans ce navigateur.') }}
            </p>
            <p class="text-ink-faint">{{ __('Décor de saison : :kigo.', ['kigo' => mb_strtolower($season->kigo())]) }}</p>
        </div>
    </footer>

    {{-- Règles de la table : enregistrées dans un cookie, elles s'appliquent dès la main suivante. --}}
    {{-- Un préréglage coche les options d'une famille de tables ; changer une option à la main le quitte. --}}
    <dialog x-data="{
                rules: @js(\App\Support\TableRules::toArray($tableRules)),
                presets: @js(\App\Support\TableRules::PRESETS),
                apply(key) { Object.assign(this.rules, this.presets[key]) },
                isPreset(key) { return Object.entries(this.presets[key]).every(([option, value]) => this.rules[option] === value) },
            }"
            x-on:open-rules.window="$el.showModal()" x-on:click.self="$el.close()" aria-labelledby="rules-title"
            class="paper m-auto max-h-[92dvh] w-[min(32rem,calc(100vw-2rem))] p-0 backdrop:bg-black/60 backdrop:backdrop-blur-[2px]">
        <form method="POST" action="{{ route('rules') }}" class="px-5 pt-5 pb-6 sm:px-7 sm:pt-6">
            @csrf
            <header class="flex items-center gap-4">
                <h2 id="rules-title" class="display text-2xl">{{ __('Règles de la table') }}</h2>
                <button type="button" class="ml-auto grid size-10 place-items-center rounded-lg text-xl text-ink-muted hover:bg-paper-sunk hover:text-ink" x-on:click="$el.closest('dialog').close()" aria-label="{{ __('Fermer les règles') }}">✕</button>
            </header>
            <p class="mt-1 text-sm text-ink-muted">{{ __('Elles changent le compte, jamais la main tirée. Une main en cours repart de la première étape.') }}</p>

            <div class="mt-5" role="group" aria-labelledby="rules-presets">
                <p id="rules-presets" class="text-sm font-bold">{{ __('Préréglages') }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach (array_keys(\App\Support\TableRules::PRESETS) as $preset)
                        @php(['label' => $presetName, 'tables' => $presetTables] = \App\Support\TableRules::presetLabel($preset))
                        <button type="button" class="choice" x-on:click="apply('{{ $preset }}')"
                                x-bind:class="isPreset('{{ $preset }}') && 'bg-ink! text-paper! shadow-none!'"
                                x-bind:aria-pressed="isPreset('{{ $preset }}')" aria-pressed="{{ \App\Support\TableRules::preset($tableRules) === $preset ? 'true' : 'false' }}">
                            {{ $presetName }} <small x-bind:class="isPreset('{{ $preset }}') && 'text-paper!'">{{ $presetTables }}</small>
                        </button>
                    @endforeach
                </div>
            </div>

            <fieldset class="mt-5 space-y-4">
                <legend class="sr-only">{{ __('Options') }}</legend>
                @foreach (\App\Support\TableRules::OPTIONS as [$key, $label, $help])
                    <label class="flex gap-3">
                        <input type="checkbox" name="{{ $key }}" value="1" class="mt-1 size-4 shrink-0 accent-shu" x-model="rules.{{ $key }}" @checked($tableRules->{$key})>
                        <span>
                            <span class="block font-medium">{{ __($label) }}</span>
                            <span class="block text-sm text-ink-muted">{{ __($help) }}</span>
                        </span>
                    </label>
                @endforeach
            </fieldset>

            <div class="mt-6 flex flex-wrap gap-3">
                <button type="submit" class="btn btn-primary">{{ __('Enregistrer') }}</button>
                <button type="button" class="btn btn-quiet" x-on:click="$el.closest('dialog').close()">{{ __('Annuler') }}</button>
            </div>
        </form>
    </dialog>

    @livewireScripts
</body>
</html>
