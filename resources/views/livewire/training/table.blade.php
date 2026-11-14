{{-- Le tapis : vents, situation, main, appels et mur mort. Il compte comme caché dès qu'il passe sous la barre de la main (160 px). --}}
@php
    $h = $hand->hand();
    $g = $hand->generated;
    $wall = function (array $indicators): array {
        $count = max(5, count($indicators) + 3);
        $tiles = [];
        for ($i = 0; $i < $count; $i++) {
            $tiles[] = ($i >= 2 && $i < 2 + count($indicators)) ? $indicators[$i - 2] : null;
        }
        return $tiles;
    };
    $tags = [];
    if ($h->doubleRiichi) $tags[] = __('Riichi dès la première défausse');
    if ($h->ippatsu) $tags[] = __('Gagnée au tour suivant le riichi, sans appel');
    if ($h->chankan) $tags[] = __('Ron sur la tuile ajoutée au kan d’un adversaire');
    if ($h->haitei) $tags[] = $h->tsumo ? __('Dernière tuile du mur') : __('Dernière défausse de la partie');
    if ($h->rinshan) $tags[] = __('Tuile piochée après le kan');
@endphp
<section class="felt px-3 pt-5 pb-6 sm:px-8 sm:pt-7 sm:pb-8" aria-label="{{ __('Main gagnante') }}" x-intersect:enter.margin.-160px.0px.0px.0px="tableVisible = true" x-intersect:leave.margin.-160px.0px.0px.0px="tableVisible = false">
    <div class="flex flex-wrap items-start gap-x-6 gap-y-3 sm:gap-y-4">
        {{-- Vents de la manche et du siège. --}}
        <div class="flex items-center gap-4" role="group" aria-label="{{ __('Vents') }}">
            <div class="flex items-center gap-2.5">
                <span class="step-tile h-10 w-8 text-lg"><x-glyph :char="$h->round->kanji()" class="size-5" /></span>
                <span class="text-sm leading-tight text-felt-muted">{{ __('Manche') }}<br><b class="text-base text-felt-ink">{{ $h->round->label() }}</b></span>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="step-tile h-10 w-8 text-lg"><x-glyph :char="$h->seat->kanji()" class="size-5" /></span>
                <span class="text-sm leading-tight text-felt-muted">{{ __('Votre siège') }}<br><b class="text-base text-felt-ink">{{ $h->seat->label() }}</b>@if ($h->isDealer())<span class="text-gilt"> · {{ __('donneur') }}</span>@endif</span>
            </div>
        </div>

        <div class="flex w-full flex-wrap items-center gap-2 sm:ml-auto sm:w-auto">
            <span class="display rounded-lg bg-felt-deep/60 px-3 py-1.5 text-lg text-gilt">{{ $h->tsumo ? 'Tsumo' : 'Ron' }}</span>
            @if ($h->riichi)
                <span class="flex items-center gap-2 rounded-lg bg-felt-deep/60 px-3 py-2 text-sm font-bold text-felt-ink">
                    <svg class="h-2.5 w-14" viewBox="0 0 120 12" aria-hidden="true"><rect x=".5" y=".5" width="119" height="11" rx="2.5" fill="#f6f0e2" stroke="rgba(0,0,0,.25)"/><circle cx="60" cy="6" r="3.2" fill="#c0352a"/></svg>
                    Riichi
                </span>
            @endif
            @foreach ($tags as $tag)
                <span class="rounded-lg bg-felt-deep/60 px-3 py-2 text-sm text-felt-ink">{{ $tag }}</span>
            @endforeach
            {{-- Honba (bâtons de 100) et bâtons de riichi posés sur la table, quand on les compte. --}}
            @if ($tableLine = \App\Training\Steps\PointsStep::tableLine($hand))
                <span class="flex items-center gap-2 rounded-lg bg-felt-deep/60 px-3 py-2 text-sm text-felt-ink" data-table-bonus>
                    @if ($h->honba > 0)
                        <svg class="h-2.5 w-10" viewBox="0 0 120 12" aria-hidden="true"><rect x=".5" y=".5" width="119" height="11" rx="2.5" fill="#f6f0e2" stroke="rgba(0,0,0,.25)"/>@foreach ([12, 22, 98, 108] as $x)<circle cx="{{ $x }}" cy="4" r="1.4" fill="#1a1714"/><circle cx="{{ $x }}" cy="8" r="1.4" fill="#1a1714"/>@endforeach</svg>
                    @endif
                    {{ $tableLine }}
                </span>
            @endif
            <button type="button" class="ml-auto rounded-lg px-3 py-2 text-sm text-felt-muted hover:bg-felt-raised hover:text-felt-ink sm:ml-0" x-on:click="share()">
                <span x-show="!copied">{{ __('Partager cette main') }}</span>
                <span x-cloak x-show="copied" class="text-gilt">{{ __('Lien copié') }}</span>
            </button>
        </div>
    </div>

    {{-- La main : tuiles cachées, tuile gagnante à part, appels à droite. --}}
    <div class="mt-6 flex flex-wrap items-end gap-x-[calc(var(--tile)*0.55)] gap-y-5 [--tile:clamp(1.15rem,calc((100vw-5.5rem)/15.2),3.15rem)] sm:mt-10 sm:gap-y-6 sm:[--tile:clamp(1.15rem,calc((100vw-8.5rem)/15.6),3.15rem)]">
        <span class="tiles gap-[1px]" aria-label="{{ __('Tuiles en main') }}">
            @foreach ($g->handTiles as $i => $t)
                @php($key = $groups['hand'][$i] ?? null)
                <x-tile :tile="$t->tile" :red="$t->red" :group="$key" />
            @endforeach
        </span>

        <span class="relative flex flex-col items-center" aria-label="{{ __('Tuile gagnante') }}">
            <span class="tiles">
                <x-tile :tile="$g->winTile->tile" :red="$g->winTile->red" win :group="$groups['win']" />
            </span>
            <span class="mt-3 text-xs font-bold text-gilt">{{ $h->tsumo ? 'tsumo' : 'ron' }}</span>
        </span>

        @if ($g->melds !== [])
            <span class="flex flex-wrap items-end gap-x-[calc(var(--tile)*0.35)] gap-y-4">
                @foreach ($g->melds as $m => $meld)
                    @php($key = $groups['melds'][$m] ?? null)
                    <span class="flex flex-col items-center">
                        <span class="tiles meld-tiles">
                            @foreach ($meld->tiles as $i => $t)
                                <x-tile :tile="$t->tile" :red="$t->red"
                                        :back="$meld->type === \App\Domain\Mahjong\Hand\MeldType::ClosedKan && ($i === 0 || $i === 3)"
                                        :side="$meld->rotated === $i" :group="$key" />
                            @endforeach
                        </span>
                        <span class="mt-2 text-xs text-felt-muted">{{ $meld->type->label() }}</span>
                    </span>
                @endforeach
            </span>
        @endif
    </div>

    {{-- Mur mort : indicateurs de dora (et d'ura-dora après un riichi). --}}
    <div class="mt-6 flex flex-wrap items-end gap-x-8 gap-y-4 border-t border-felt-line pt-4 sm:mt-8 sm:pt-5" style="--tile: clamp(1.15rem, 3.4vw, 1.85rem)">
        @foreach (array_filter(['Dora' => $h->dora, 'Ura' => $h->riichi ? $h->ura : null], fn ($v) => $v !== null) as $label => $indicators)
            <div class="flex items-end gap-3">
                <span class="pb-1 text-sm text-felt-muted">{{ $label }}</span>
                <span class="tiles gap-[1px]">
                    @foreach ($wall($indicators) as $t)
                        @if ($t === null)
                            <x-tile :tile="0" back />
                        @else
                            <x-tile :tile="$t" />
                        @endif
                    @endforeach
                </span>
            </div>
        @endforeach
        @if ($hand->rules->aka)
            <p class="pb-1 text-sm text-felt-muted">{{ __('Chaque cinq rouge vaut 1 dora.') }}</p>
        @endif
    </div>
</section>
