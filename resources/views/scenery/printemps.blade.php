{{-- Printemps : une branche de cerisier devant le soleil, et des pétales qui volent. --}}
@php
    // [x, y, rayon, rotation] des fleurs le long de la branche.
    $blossoms = [
        [470, 52, 11, 10], [420, 74, 12, -20], [372, 100, 14, 30], [330, 60, 13, 0], [305, 38, 10, 40],
        [430, 132, 13, 15], [395, 112, 9, -10], [265, 128, 12, 25], [232, 152, 11, -30], [228, 200, 12, 5],
        [196, 172, 10, 50], [150, 190, 11, -15], [118, 168, 9, 20], [94, 214, 10, 35], [66, 232, 8, 0],
    ];
    $buds = [[290, 24, 4, 30], [58, 242, 3.5, -20], [240, 216, 4, 10], [446, 152, 4, -35], [128, 152, 3.5, 15]];
    // Pétales qui volent, sur les bords de la fenêtre : [x, y, échelle, rotation].
    $petals = [
        [860, 210, 1.3, 30], [930, 330, 1.1, -40], [820, 470, 1.4, 80], [960, 600, 1.2, 10], [880, 760, 1, -70],
        [990, 880, 1.3, 120], [60, 260, 1.1, 200], [150, 420, 1.3, 60], [40, 600, 1, -15], [180, 780, 1.2, 160],
        [100, 920, 1, 95], [965, 150, 1.1, -110], [760, 880, 1.2, 25], [230, 160, 0.9, 140],
    ];
@endphp
<svg class="sc-branch" viewBox="0 0 520 380">
    <defs>
        <path id="sc-petal" d="M0 0C-5.5-3-5.2-9.2-1.6-10.2L0-8.6L1.6-10.2C5.2-9.2 5.5-3 0 0Z"/>
    </defs>
    <g class="sc-wood" fill="none" stroke-linecap="round">
        <path d="M540 26C470 40 420 70 360 92" stroke-width="11"/>
        <path d="M360 92C300 114 240 140 190 168" stroke-width="7"/>
        <path d="M190 168C150 190 110 205 66 232" stroke-width="4"/>
        <path d="M360 92C340 70 320 54 296 30" stroke-width="4"/>
        <path d="M440 62C434 92 432 112 430 150" stroke-width="4"/>
        <path d="M250 132C242 160 236 184 236 214" stroke-width="3"/>
        <path d="M150 190C138 180 126 172 120 160" stroke-width="2.4"/>
    </g>
    @foreach ($blossoms as [$x, $y, $r, $rotation])
        <g transform="translate({{ $x }} {{ $y }}) rotate({{ $rotation }}) scale({{ $r / 10 }})">
            @for ($k = 0; $k < 5; $k++)
                <use href="#sc-petal" class="sc-blossom" transform="rotate({{ $k * 72 }})"/>
            @endfor
            <circle r="2.3" class="sc-blossom-heart"/>
            @for ($k = 0; $k < 5; $k++)
                <circle r=".7" cx="{{ round(4.2 * sin(deg2rad($k * 72 + 36)), 2) }}" cy="{{ round(-4.2 * cos(deg2rad($k * 72 + 36)), 2) }}" class="sc-blossom-heart"/>
            @endfor
        </g>
    @endforeach
    @foreach ($buds as [$x, $y, $r, $rotation])
        <ellipse cx="{{ $x }}" cy="{{ $y }}" rx="{{ $r * 0.7 }}" ry="{{ $r }}" transform="rotate({{ $rotation }} {{ $x }} {{ $y }})" class="sc-blossom-heart"/>
    @endforeach
</svg>
<svg class="sc-fall" viewBox="0 0 1000 1000" preserveAspectRatio="xMidYMid slice">
    @foreach ($petals as [$x, $y, $scale, $rotation])
        <use href="#sc-petal" class="sc-blossom sc-drift" transform="translate({{ $x }} {{ $y }}) rotate({{ $rotation }}) scale({{ $scale }})"/>
    @endforeach
</svg>
