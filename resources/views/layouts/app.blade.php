@props(['title' => null, 'description' => null, 'image' => null, 'imageAlt' => null])
@php
    $season = \App\View\Season::current(now(), request()->query('saison'));
    $tableRules = \App\Support\TableRules::fromRequest(request());
    $locale = app()->getLocale();
    $alternates = \App\Support\Locale::alternates(request());
    $currentRoute = \App\Support\Locale::baseRouteName(request()->route()?->getName() ?? '');
    $isCurrent = fn (array|string $pattern): bool => \Illuminate\Support\Str::is($pattern, $currentRoute);
    $pageTitle = $title ? $title.' · Tenpai' : __('Tenpai · Comptez vos mains de riichi');
    $pageDescription = $description ?? __('Entraînez-vous à compter une main de mahjong riichi en cinq étapes : yaku, han, dora, fu et points.');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" data-season="{{ $season->value }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="theme-color" content="#f4eddc" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#131922" media="(prefers-color-scheme: dark)">
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
    @fonts
    {{-- Les textes de l'interface écrits en JavaScript, dans la langue de la page. --}}
    <script>window.tenpaiText = @js(\App\Support\ScriptText::translations());</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-dvh flex-col">
    <x-scenery :season="$season" />

    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-paper focus:px-4 focus:py-2 focus:text-ink">{{ __('Aller au contenu') }}</a>

    <header class="relative z-10 mx-auto flex w-full max-w-6xl items-center gap-x-2 gap-y-2 px-4 pt-4 pb-3 sm:gap-x-6 sm:px-6 sm:pt-6">
        <a href="{{ localized_route('home') }}" class="group flex items-center gap-3 rounded-lg" aria-label="{{ __('Tenpai, accueil') }}">
            <x-seal class="size-10 transition-transform group-hover:-rotate-3 sm:size-11" />
            <span class="display hidden text-xl tracking-wide text-ink min-[400px]:inline sm:text-2xl">Tenpai</span>
        </a>

        <nav class="ml-auto flex items-center gap-1 text-sm sm:gap-2 sm:text-base" aria-label="{{ __('Navigation principale') }}">
            @foreach ([
                ['training', __('S’entraîner'), null, ['training*', 'drill*']],
                ['daily', __('Défi du jour'), __('Défi'), 'daily'],
                ['scoring', __('Barème'), null, 'scoring'],
            ] as [$route, $label, $shortLabel, $pattern])
                <a href="{{ localized_route($route) }}"
                   @class([
                       'relative whitespace-nowrap rounded-lg px-2 py-2 font-medium transition-colors sm:px-3',
                       'text-ink after:absolute after:inset-x-3 after:bottom-0.5 after:h-[3px] after:rounded-full after:bg-shu' => $isCurrent($pattern),
                       'text-ink-muted hover:text-ink' => ! $isCurrent($pattern),
                   ])
                   @if ($isCurrent($pattern)) aria-current="page" @endif
                   @if ($route === 'training') wire:navigate @endif>
                    @if ($shortLabel)
                        <span class="sm:hidden">{{ $shortLabel }}</span><span class="hidden sm:inline">{{ $label }}</span>
                    @else
                        {{ $label }}
                    @endif
                </a>
            @endforeach
        </nav>

        {{-- Réglages : les règles de la table. Un point rouge quand elles diffèrent des règles par défaut. --}}
        <button type="button" x-data x-on:click="$dispatch('open-rules')"
                class="relative -ml-1 grid size-9 shrink-0 place-items-center rounded-lg text-ink-muted transition-colors hover:bg-paper-sunk hover:text-ink sm:ml-0 sm:size-10"
                aria-label="{{ __('Règles de la table') }}" title="{{ __('Règles de la table') }}" aria-haspopup="dialog">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M4 7h9M17 7h3M4 17h3M11 17h9"/>
                <circle cx="15" cy="7" r="2"/>
                <circle cx="9" cy="17" r="2"/>
            </svg>
            @unless (\App\Support\TableRules::isDefault($tableRules))
                <span class="absolute top-1.5 right-1.5 size-2 rounded-full bg-shu" aria-hidden="true"></span>
                <span class="sr-only">{{ __('(règles personnalisées)') }}</span>
            @endunless
        </button>

        {{-- Langue : le code de l'autre langue, qui mène à la même page traduite. --}}
        @foreach ($alternates as $alternateLocale => $alternateUrl)
            @continue($alternateLocale === $locale)
            <a href="{{ $alternateUrl }}" hreflang="{{ $alternateLocale }}" lang="{{ $alternateLocale }}"
               class="-ml-1 grid h-9 min-w-9 shrink-0 place-items-center rounded-lg px-1.5 text-sm font-bold tracking-wide text-ink-muted transition-colors hover:bg-paper-sunk hover:text-ink sm:ml-0 sm:h-10 sm:min-w-10"
               aria-label="{{ \App\Support\Locale::LOCALES[$alternateLocale]['name'] }}" title="{{ \App\Support\Locale::LOCALES[$alternateLocale]['name'] }}">{{ strtoupper($alternateLocale) }}</a>
        @endforeach
    </header>

    <main id="contenu" class="relative z-10 w-full flex-1">
        {{ $slot }}
    </main>

    <footer class="relative z-10 mx-auto mt-20 w-full max-w-6xl px-4 pb-8 text-sm text-ink-muted sm:px-6">
        <div class="kasumi -mb-2" aria-hidden="true">
            <span style="left: 0; right: 38%; top: 0.4rem"></span>
            <span style="left: 22%; right: 6%; top: 1.4rem"></span>
        </div>
        <div class="flex flex-wrap items-baseline justify-between gap-x-8 gap-y-2">
            <p>
                {{ __(':label : kuitan, :rules, sans honba ni bâton de riichi.', [
                    'label' => \App\Support\TableRules::isDefault($tableRules) ? __('Riichi courant, style salle japonaise') : __('Vos règles'),
                    'rules' => \App\Support\TableRules::describe($tableRules),
                ]) }}
                <br>{{ __('Vos progrès restent dans ce navigateur.') }}
            </p>
            <p class="text-ink-faint">{{ __('Décor de saison : :kigo.', ['kigo' => mb_strtolower($season->kigo())]) }}</p>
        </div>
    </footer>

    {{-- Règles de la table : enregistrées dans un cookie, elles s'appliquent dès la main suivante. --}}
    <dialog x-data x-on:open-rules.window="$el.showModal()" x-on:click.self="$el.close()" aria-labelledby="rules-title"
            class="paper m-auto w-[min(32rem,calc(100vw-2rem))] p-0 backdrop:bg-black/60 backdrop:backdrop-blur-[2px]">
        <form method="POST" action="{{ route('rules') }}" class="px-5 pt-5 pb-6 sm:px-7 sm:pt-6">
            @csrf
            <header class="flex items-center gap-4">
                <h2 id="rules-title" class="display text-2xl">{{ __('Règles de la table') }}</h2>
                <button type="button" class="ml-auto grid size-10 place-items-center rounded-lg text-xl text-ink-muted hover:bg-paper-sunk hover:text-ink" x-on:click="$el.closest('dialog').close()" aria-label="{{ __('Fermer les règles') }}">✕</button>
            </header>
            <p class="mt-1 text-sm text-ink-muted">{{ __('Elles changent le compte, jamais la main tirée. Une main en cours repart de la première étape.') }}</p>

            <fieldset class="mt-5 space-y-4">
                <legend class="sr-only">{{ __('Options') }}</legend>
                @foreach (\App\Support\TableRules::OPTIONS as [$key, $label, $help])
                    <label class="flex gap-3">
                        <input type="checkbox" name="{{ $key }}" value="1" class="mt-1 size-4 shrink-0 accent-shu" @checked($tableRules->{$key})>
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
