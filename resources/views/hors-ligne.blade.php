<x-layouts::app :title="__('Hors ligne')" :description="__('Pas de réseau : les exercices attendront, le barème reste consultable.')">
    <div class="mx-auto max-w-3xl px-4 pt-10 sm:px-6 sm:pt-16">
        <h1 class="display text-4xl sm:text-5xl">{{ __('Pas de réseau') }}</h1>
        <p class="mt-4 max-w-[58ch] text-lg text-ink-muted">
            {{ __('Les mains sont tirées et corrigées sur le serveur : les exercices et le défi du jour reprendront avec la connexion. En attendant, le barème reste consultable, et vos progrès sont toujours là.') }}
        </p>
        <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-4">
            <a href="{{ localized_route('scoring') }}" class="btn btn-primary h-12 px-6">{{ __('Ouvrir le barème') }}</a>
            <button type="button" class="font-bold text-ink underline decoration-ink-line decoration-2 underline-offset-[6px] hover:decoration-shu" x-data x-on:click="location.reload()">{{ __('Réessayer') }}</button>
        </div>
    </div>
</x-layouts::app>
