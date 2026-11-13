<x-layouts::app title="Barème" description="Le barème du riichi : la valeur des yaku, la recette des fu et ce que paie chaque main.">
    <div class="mx-auto max-w-6xl px-4 pt-6 sm:px-6 sm:pt-10">
        <h1 class="display text-4xl sm:text-6xl">Barème</h1>
        <p class="mt-3 max-w-[62ch] text-lg text-ink-muted">
            Le pense-bête de la table, dans l’ordre du comptage : les han des yaku, puis les fu, puis ce que paie la main.
        </p>

        <div class="paper mt-8 p-4 sm:mt-10 sm:p-8">
            <x-scoring-sheet :sheet="$sheet" tab="points" />
        </div>
    </div>
</x-layouts::app>
