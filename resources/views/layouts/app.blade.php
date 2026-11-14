@props(['title' => null, 'description' => null])
@php($season = \App\View\Season::current(now(), request()->query('saison')))
<!DOCTYPE html>
<html lang="fr" data-season="{{ $season->value }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ? $title.' · Tenpai' : 'Tenpai · Comptez vos mains de riichi' }}</title>
    <meta name="description" content="{{ $description ?? 'Entraînez-vous à compter une main de mahjong riichi en cinq étapes : yaku, han, dora, fu et points.' }}">
    <meta name="theme-color" content="#f4eddc" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#131922" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-dvh flex-col">
    <x-scenery :season="$season" />

    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-paper focus:px-4 focus:py-2 focus:text-ink">Aller au contenu</a>

    <header class="relative z-10 mx-auto flex w-full max-w-6xl items-center gap-x-3 gap-y-2 px-4 pt-4 pb-3 sm:gap-x-6 sm:px-6 sm:pt-6">
        <a href="{{ route('home') }}" class="group flex items-center gap-3 rounded-lg" aria-label="Tenpai, accueil">
            <x-seal class="size-10 transition-transform group-hover:-rotate-3 sm:size-11" />
            <span class="display hidden text-xl tracking-wide text-ink min-[400px]:inline sm:text-2xl">Tenpai</span>
        </a>

        <nav class="ml-auto flex items-center gap-1 text-sm sm:gap-2 sm:text-base" aria-label="Navigation principale">
            @foreach ([
                ['training', 'S’entraîner', 'training*'],
                ['daily', 'Défi du jour', 'daily'],
                ['scoring', 'Barème', 'scoring'],
            ] as [$route, $label, $pattern])
                <a href="{{ route($route) }}"
                   @class([
                       'relative whitespace-nowrap rounded-lg px-2.5 py-2 font-medium transition-colors sm:px-3',
                       'text-ink after:absolute after:inset-x-3 after:bottom-0.5 after:h-[3px] after:rounded-full after:bg-shu' => request()->routeIs($pattern),
                       'text-ink-muted hover:text-ink' => ! request()->routeIs($pattern),
                   ])
                   @if (request()->routeIs($pattern)) aria-current="page" @endif
                   @if ($route === 'training') wire:navigate @endif>{{ $label }}</a>
            @endforeach
        </nav>
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
                Riichi courant, style salle japonaise : kuitan, cinq rouges, sans honba ni bâton de riichi.
                Vos progrès restent dans ce navigateur.
            </p>
            <p class="text-ink-faint">Décor de saison : {{ mb_strtolower($season->kigo()) }}.</p>
        </div>
    </footer>
    @livewireScripts
</body>
</html>
