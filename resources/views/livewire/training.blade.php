@php
    $hand = $this->hand;
    $steps = $this->steps;
    $daily = $this->dailyDate();
    $groups = $step >= 3 ? $hand->tableGroups() : ['hand' => [], 'win' => null, 'melds' => []];
@endphp
<div
    x-data="{
        lit: null,
        tableVisible: true,
        copied: false,
        openScoring(tab) {
            this.$dispatch('scoring-tab', tab);
            this.$refs.scoring.showModal();
        },
        async share() {
            const data = { title: 'Tenpai', text: 'Comptez cette main de riichi avec moi.', url: window.location.href };
            if (navigator.share && matchMedia('(pointer: coarse)').matches) {
                try { await navigator.share(data); } catch (e) {}
                return;
            }
            await navigator.clipboard?.writeText(data.url);
            this.copied = true;
            setTimeout(() => this.copied = false, 2200);
        },
    }"
    x-on:hand-finished.window="$store.progress.record($event.detail)"
    class="mx-auto max-w-6xl px-4 pt-2 sm:px-6 sm:pt-4"
>
    @if ($daily)
        <p class="mb-5 flex flex-wrap items-baseline gap-x-3 text-ink-muted">
            <span class="display text-xl text-shu">Défi du jour</span>
            <span>{{ ucfirst(\Illuminate\Support\Carbon::parse($daily)->translatedFormat('l j F')) }} : la même main pour tout le monde.</span>
        </p>
    @endif

    @include('livewire.training.table')
    @include('livewire.training.rail')

    <div class="mt-8 grid gap-6 lg:mt-10 lg:grid-cols-[17.5rem_minmax(0,1fr)] lg:gap-8">
        @include('livewire.training.score-sheet')

        <div class="min-w-0">
            @if ($view === \App\Livewire\Training::RECAP)
                @include('livewire.training.recap')
            @else
                @include('livewire.training.step-card')
            @endif
        </div>
    </div>

    {{--
        Barème, à portée de main (touche B), ouvert sur la rubrique de l'étape.
        Dans un island : il n'est rendu qu'une fois, pas à chaque validation.
    --}}
    @island(name: 'bareme')
        <dialog x-ref="scoring" aria-labelledby="scoring-title"
                class="paper m-auto max-h-[min(92dvh,58rem)] w-[min(66rem,calc(100vw-1.5rem))] overflow-hidden p-0 backdrop:bg-black/60 backdrop:backdrop-blur-[2px]"
                x-on:click.self="$el.close()">
            <div class="flex max-h-[inherit] flex-col">
                <header class="flex items-center gap-4 px-5 pt-5 sm:px-8 sm:pt-6">
                    <h2 id="scoring-title" class="display text-2xl">Barème</h2>
                    <button type="button" class="ml-auto grid size-10 place-items-center rounded-lg text-xl text-ink-muted hover:bg-paper-sunk hover:text-ink" x-on:click="$refs.scoring.close()" aria-label="Fermer le barème">✕</button>
                </header>
                <div class="overflow-y-auto px-5 pt-4 pb-7 sm:px-8">
                    <x-scoring-sheet :sheet="new \App\View\ScoringSheet($this->hand->rules)" training :tab="$this->steps[min($this->view, 4)]->scoringTab()" />
                </div>
            </div>
        </dialog>
    @endisland
</div>
