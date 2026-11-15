<x-layouts::app :title="__('Barème du riichi : yaku, fu et table des points')" :description="__('Le barème du mahjong riichi : la valeur de chaque yaku en han, le calcul des fu et la table des points, du mangan au yakuman.')" :schema="\App\View\StructuredData::scoring()">
    <div class="mx-auto max-w-6xl px-4 pt-6 sm:px-6 sm:pt-10">
        <h1 class="display text-4xl sm:text-6xl">{{ __('Barème') }}</h1>
        <p class="mt-3 max-w-[62ch] text-lg text-ink-muted">
            {{ __('Le pense-bête de la table, dans l’ordre du comptage : les han des yaku, puis les fu, puis ce que paie la main.') }}
        </p>
        <p class="mt-2 text-ink-muted">
            {{ __('Calculé avec : :rules.', ['rules' => \App\Support\TableRules::describe($sheet->rules)]) }}
            <button type="button" class="font-medium text-ink underline decoration-shu decoration-2 underline-offset-4 hover:decoration-ink" x-data x-on:click="$dispatch('open-rules')">{{ __('Changer') }}</button>
        </p>
        <p class="mt-2 text-ink-muted">
            {{ __('Vous débutez ?') }}
            <a href="{{ localized_route('learn.scoring') }}" class="font-medium text-ink underline decoration-shu decoration-2 underline-offset-4 hover:decoration-ink">{{ __('Lisez d’abord le guide du calcul des points') }}</a>.
        </p>

        <div class="paper mt-8 p-4 sm:mt-10 sm:p-8">
            <x-scoring-sheet :sheet="$sheet" tab="points" />
        </div>
    </div>
</x-layouts::app>
