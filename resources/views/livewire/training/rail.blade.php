{{-- La main en petit, collée en haut de l'écran quand le tapis n'est plus visible. --}}
@php($g = $hand->generated)
<div x-cloak x-show="!tableVisible" x-transition.opacity.duration.150ms
     class="fixed inset-x-0 top-0 z-30 border-b-4 border-(--wood) bg-felt/95 text-felt-ink shadow-[0_8px_24px_-12px_rgb(0_0_0/0.6)] backdrop-blur"
     aria-hidden="true">
    <div class="mx-auto flex max-w-6xl flex-wrap items-end gap-x-3 gap-y-1 px-3 py-2 sm:px-6" style="--tile: clamp(1.05rem, calc((100vw - 2.5rem) / 17), 1.7rem)">
        <span class="tiles gap-px">
            @foreach ($g->handTiles as $i => $t)
                <x-tile :tile="$t->tile" :red="$t->red" :group="$groups['hand'][$i] ?? null" />
            @endforeach
        </span>
        <span class="tiles"><x-tile :tile="$g->winTile->tile" :red="$g->winTile->red" :group="$groups['win']" class="outline-2 outline-offset-1 outline-gilt rounded-[3px]" /></span>
        @foreach ($g->melds as $m => $meld)
            <span class="tiles">
                @foreach ($meld->tiles as $i => $t)
                    <x-tile :tile="$t->tile" :red="$t->red"
                            :back="$meld->type === \App\Domain\Mahjong\Hand\MeldType::ClosedKan && ($i === 0 || $i === 3)"
                            :side="$meld->rotated === $i" :group="$groups['melds'][$m] ?? null" />
                @endforeach
            </span>
        @endforeach
        <span class="ml-auto flex items-end gap-2 text-xs text-felt-muted">
            {{ $g->hand->tsumo ? 'Tsumo' : 'Ron' }} · {{ $g->hand->seat->label() }}@if ($g->hand->isDealer()) (donneur)@endif
            <span class="tiles gap-px">
                @foreach ($g->hand->dora as $t)
                    <x-tile :tile="$t" />
                @endforeach
            </span>
        </span>
    </div>
</div>
