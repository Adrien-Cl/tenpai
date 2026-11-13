{{-- Hiver : neige sur les bambous (voir scenery.bamboo) et flocons qui tombent. --}}
@php
    // [x, y, rayon] : des flocons épars, plus serrés vers les bords.
    $flakes = [
        [800, 120, 3.2], [860, 240, 2.4], [920, 90, 2], [780, 330, 3.6], [960, 400, 2.6], [840, 480, 2.2],
        [900, 600, 2.8], [980, 700, 2], [820, 760, 3], [940, 860, 2.4], [760, 920, 3.2], [880, 960, 2.2],
        [40, 160, 2.4], [120, 300, 3], [70, 460, 2.2], [180, 560, 2.8], [30, 700, 2], [140, 820, 3.2],
        [220, 940, 2.4], [200, 220, 2], [960, 180, 2.6], [770, 560, 2], [250, 400, 1.8], [10, 880, 2.6],
    ];
@endphp
<svg class="sc-fall" viewBox="0 0 1000 1000" preserveAspectRatio="xMidYMid slice">
    @foreach ($flakes as [$x, $y, $r])
        <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $r }}" class="sc-flake"/>
    @endforeach
</svg>
