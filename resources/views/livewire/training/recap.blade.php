{{-- Récapitulatif : détail des han, calcul, paiement en bâtons de points. --}}
@php
    $evaluation = $hand->evaluation;
    $h = $hand->hand();
    $payment = $evaluation->payment();
    $okCount = count(array_filter($results, fn ($r) => $r['ok']));
    $fmt = fn (int $n) => \App\Support\Format::points($n);

    // Bâtons de points, du plus gros au plus petit (au plus cinq dessinés par valeur).
    $remaining = $payment->total;
    $sticks = [];
    foreach ([10000, 5000, 1000, 100] as $value) {
        $count = intdiv($remaining, $value);
        $remaining -= $count * $value;
        if ($count) {
            $sticks[] = [$value, $count];
        }
    }
    $dot = fn ($x, $y, $r, $c) => "<circle cx=\"{$x}\" cy=\"{$y}\" r=\"{$r}\" fill=\"{$c}\"/>";
    $stickSvg = function (int $value) use ($dot): string {
        $ink = '#1a1714'; $red = '#c0352a'; $d = '';
        if ($value === 100) { foreach ([14, 21, 139, 146] as $x) { $d .= $dot($x, 4.6, 1.5, $ink).$dot($x, 9.4, 1.5, $ink); } }
        if ($value === 1000) { $d = $dot(80, 7, 3.4, $red); }
        if ($value === 5000) { $d = $dot(80, 7, 3, $red); foreach ([62, 98] as $x) { $d .= $dot($x, 7, 2, $ink); } foreach ([24, 136] as $x) { $d .= $dot($x, 7, 2.2, $red); } }
        if ($value === 10000) { $d = $dot(80, 7, 3.4, $red); foreach ([[73, 3.4], [87, 3.4], [73, 10.6], [87, 10.6]] as [$x, $y]) { $d .= $dot($x, $y, 1.4, $ink); } foreach ([16, 144] as $x) { $d .= $dot($x, 7, 2.2, $ink); } }
        return '<svg class="block h-auto w-full" viewBox="0 0 160 14" aria-hidden="true"><rect x=".5" y=".5" width="159" height="13" rx="3" fill="#f6f0e2" stroke="rgba(0,0,0,.22)"/>'.$d.'</svg>';
    };
@endphp

<section class="paper relative overflow-hidden" aria-labelledby="recap-title">
    <header class="flex gap-5 px-5 pt-6 sm:px-8 sm:pt-8">
        <span class="step-tile hidden h-14 w-11 shrink-0 sm:grid"><x-glyph char="計" class="size-6" /></span>
        <div class="min-w-0 flex-1 pr-24">
            <p class="text-sm text-ink-muted">Récapitulatif · {{ $okCount }} étape{{ $okCount > 1 ? 's' : '' }} juste{{ $okCount > 1 ? 's' : '' }} sur 5</p>
            <h2 id="recap-title" class="display mt-0.5 text-2xl sm:text-[1.9rem]">
                {{ $evaluation->han }} han, {{ $evaluation->fu() }} fu{{ $payment->limit ? " · ".$payment->limit->label() : "" }}
            </h2>
        </div>
        <div class="absolute top-6 right-5 flex flex-col items-center gap-1.5 sm:top-7 sm:right-8">
            @if ($okCount === 5)
                <span class="hanko is-new size-20!" aria-hidden="true"><span class="flex flex-col"><x-glyph char="満" class="size-8" /><x-glyph char="点" class="size-8" /></span></span>
                <span class="text-xs font-bold text-ok">Sans faute</span>
            @else
                <span class="hanko hanko--ko is-new size-20!" aria-hidden="true"><span class="display num text-4xl">{{ $okCount }}<span class="text-xl">/5</span></span></span>
                <span class="text-xs font-bold text-ko">{{ $okCount }} juste{{ $okCount > 1 ? 's' : '' }}</span>
            @endif
        </div>
    </header>

    <div class="px-5 pt-6 pb-2 sm:px-8">
        <p class="display num text-4xl sm:text-5xl">
            @if (! $h->tsumo)
                {{ $fmt($payment->ron) }}
            @elseif ($evaluation->dealer)
                {{ $fmt($payment->each) }}
            @else
                {{ $fmt($payment->fromOthers) }} / {{ $fmt($payment->fromDealer) }}
            @endif
        </p>
        <p class="mt-1 text-ink-muted">
            @if (! $h->tsumo)
                points en ron, payés par celui qui a jeté la tuile
            @elseif ($evaluation->dealer)
                de chaque adversaire, {{ $fmt($payment->total) }} au total
            @else
                par non-donneur / par le donneur, {{ $fmt($payment->total) }} au total
            @endif
        </p>
    </div>

    <div class="grid gap-8 px-5 py-6 sm:px-8 md:grid-cols-[minmax(0,1fr)_15rem]">
        <div>
            <table class="w-full">
                <caption class="sr-only">Détail des han</caption>
                <tbody>
                    @foreach ($evaluation->yaku() as $yakuHan)
                        <tr class="border-b border-paper-line"><td class="py-2">{{ $yakuHan->yaku->label() }}</td><td class="display num py-2 text-right">{{ $yakuHan->han }}</td></tr>
                    @endforeach
                    @foreach ([['Dora', $evaluation->dora->count], ['Aka-dora', $evaluation->aka], ['Ura-dora', $evaluation->ura->count]] as [$label, $count])
                        @if ($count)
                            <tr class="border-b border-paper-line"><td class="py-2">{{ $label }}</td><td class="display num py-2 text-right">{{ $count }}</td></tr>
                        @endif
                    @endforeach
                    <tr><td class="pt-3 font-bold">Total</td><td class="display num pt-3 text-right text-lg">{{ $evaluation->han }} han</td></tr>
                </tbody>
            </table>
            <div class="mt-6 space-y-1.5 rounded-lg bg-paper-sunk px-4 py-3">
                @foreach (\App\Training\Steps\PointsStep::formula($hand) as $line)
                    <p>{!! $line !!}</p>
                @endforeach
            </div>
        </div>

        <div aria-label="Bâtons de points pour {{ $fmt($payment->total) }} points">
            <p class="text-sm text-ink-muted">En bâtons, {{ $fmt($payment->total) }} points</p>
            <div class="mt-3 space-y-3">
                @foreach ($sticks as [$value, $count])
                    <div class="flex items-center gap-3">
                        <span class="grid flex-1 gap-[3px]">
                            @for ($i = 0; $i < min($count, 5); $i++)
                                {!! $stickSvg($value) !!}
                            @endfor
                        </span>
                        <span class="num w-20 text-right text-sm text-ink-muted">{{ $count }} × {{ $fmt($value) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <footer class="flex flex-wrap items-center gap-3 border-t border-paper-line px-5 py-4 sm:px-8">
        <button type="button" class="btn btn-primary" wire:click="nextHand" data-shortcut="n" x-init="$el.focus({ preventScroll: true })">Main suivante</button>
        <span class="hidden text-sm text-ink-muted md:inline">ou touche N</span>
        <button type="button" class="btn ml-auto px-3 text-ink-muted hover:text-ink" x-on:click="share()">
            <span x-show="!copied">Partager cette main</span>
            <span x-cloak x-show="copied">Lien copié</span>
        </button>
    </footer>
</section>
