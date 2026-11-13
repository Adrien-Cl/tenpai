{{-- Été : bambous et moineaux en plein jour, des lucioles la nuit. --}}
@php
    // Une libellule rouge (akatombo) au-dessus des collines, et les lucioles du soir : [x, y, rayon].
    $fireflies = [
        [180, 620, 3], [240, 540, 2.2], [130, 760, 2.6], [210, 700, 2], [90, 480, 2.4],
        [150, 380, 2.8], [250, 860, 2], [60, 300, 2.2], [60, 900, 2.6], [200, 220, 1.8],
    ];
@endphp
<svg class="sc-dragonfly" viewBox="0 0 120 80">
    <g transform="translate(60 40) rotate(-18)">
        <ellipse class="sc-wing" cx="-14" cy="-9" rx="16" ry="4.2" transform="rotate(-18 -14 -9)"/>
        <ellipse class="sc-wing" cx="-14" cy="9" rx="16" ry="4.2" transform="rotate(18 -14 9)"/>
        <ellipse class="sc-wing" cx="12" cy="-8" rx="14" ry="3.8" transform="rotate(14 12 -8)"/>
        <ellipse class="sc-wing" cx="12" cy="8" rx="14" ry="3.8" transform="rotate(-14 12 8)"/>
        <path class="sc-dragonfly-body" d="M-6 0H40" stroke-width="3.2" stroke-linecap="round"/>
        <circle class="sc-dragonfly-head" cx="-9" cy="0" r="4"/>
    </g>
</svg>
<svg class="sc-fall sc-fireflies" viewBox="0 0 1000 1000" preserveAspectRatio="xMinYMax slice">
    <defs>
        <radialGradient id="sc-firefly">
            <stop offset="0" stop-color="#fff7c2"/>
            <stop offset=".35" stop-color="#e9e17a" stop-opacity=".8"/>
            <stop offset="1" stop-color="#e9e17a" stop-opacity="0"/>
        </radialGradient>
    </defs>
    @foreach ($fireflies as $i => [$x, $y, $r])
        <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $r * 5 }}" fill="url(#sc-firefly)" class="sc-glow" style="--i: {{ $i }}"/>
    @endforeach
</svg>
