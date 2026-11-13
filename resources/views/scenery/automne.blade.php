{{-- Automne : une branche d'érable, des feuilles qui tombent, des susuki au pied de la pièce. --}}
@php
    // Feuille d'érable (momiji) à sept lobes dentelés, longueur 10, pointe vers le haut.
    $polar = fn (float $degrees, float $radius): string => round(sin(deg2rad($degrees)) * $radius * 10, 2).' '.round(-cos(deg2rad($degrees)) * $radius * 10, 2);
    $lobes = [[-116, 0.55], [-78, 0.82], [-39, 0.96], [0, 1], [39, 0.96], [78, 0.82], [116, 0.55]];
    $maple = 'M'.$polar(-140, 0.2);
    foreach ($lobes as $i => [$angle, $length]) {
        $next = $lobes[$i + 1][0] ?? 140;
        $maple .= 'L'.$polar($angle - 13, $length * 0.66).'L'.$polar($angle - 6, $length * 0.8).'L'.$polar($angle, $length).'L'.$polar($angle + 6, $length * 0.8).'L'.$polar($angle + 13, $length * 0.66);
        $maple .= 'L'.$polar(($angle + $next) / 2, $next === 140 ? 0.22 : 0.46);
    }
    $maple .= 'Z';

    // [x, y, échelle, rotation, teinte 1-3] le long de la branche.
    $leaves = [
        [470, 60, 2.4, 20, 1], [430, 92, 2.7, -30, 2], [384, 70, 2.2, 10, 1], [350, 122, 2.9, 40, 3], [300, 96, 2.4, -15, 1],
        [262, 140, 2.6, 25, 2], [226, 118, 2.1, -40, 1], [206, 174, 2.6, 15, 3], [160, 160, 2.2, -10, 1], [130, 204, 2.4, 30, 2],
        [96, 186, 1.9, -25, 1], [404, 150, 2.1, 55, 1],
    ];
    // Feuilles qui tombent, sur les bords de la fenêtre pour ne pas gêner la lecture.
    $falling = [
        [880, 300, 2.4, 40, 1], [950, 520, 2.1, -60, 2], [830, 700, 2.3, 120, 3], [970, 840, 1.9, 15, 1],
        [140, 420, 2.2, -100, 1], [60, 640, 1.9, 70, 2], [190, 860, 2, 160, 1], [900, 120, 1.8, -30, 3],
    ];
@endphp
<svg class="sc-branch" viewBox="0 0 520 380">
    <defs>
        <g id="sc-maple">
            <path d="{{ $maple }}"/>
            <path d="M0 0L0 6.5" stroke-width="1" fill="none" class="sc-stem"/>
        </g>
    </defs>
    <g class="sc-wood" fill="none" stroke-linecap="round">
        <path d="M540 30C480 44 430 70 370 96" stroke-width="8"/>
        <path d="M370 96C310 120 250 146 200 172" stroke-width="5"/>
        <path d="M200 172C160 188 126 196 90 196" stroke-width="3"/>
        <path d="M370 96C352 84 330 76 300 92" stroke-width="2.6"/>
        <path d="M440 64C424 90 412 120 404 150" stroke-width="2.6"/>
        <path d="M260 140C240 132 226 124 222 116" stroke-width="2"/>
    </g>
    @foreach ($leaves as [$x, $y, $scale, $rotation, $tint])
        <use href="#sc-maple" class="sc-leaf-{{ $tint }}" transform="translate({{ $x }} {{ $y }}) rotate({{ 180 + $rotation }}) scale({{ $scale }})"/>
    @endforeach
</svg>
<svg class="sc-fall" viewBox="0 0 1000 1000" preserveAspectRatio="xMidYMid slice">
    @foreach ($falling as [$x, $y, $scale, $rotation, $tint])
        <use href="#sc-maple" class="sc-leaf-{{ $tint }} sc-drift" transform="translate({{ $x }} {{ $y }}) rotate({{ $rotation }}) scale({{ $scale }})"/>
    @endforeach
</svg>
{{-- Susuki (herbes d'argent), l'herbe de la lune d'automne. --}}
<svg class="sc-grass" viewBox="0 0 300 420" preserveAspectRatio="xMaxYMax meet">
    <g fill="none" stroke-linecap="round" class="sc-blades">
        <path d="M250 420C246 330 214 250 150 190" stroke-width="3"/>
        <path d="M270 420C268 340 250 260 214 200" stroke-width="2.6"/>
        <path d="M230 420C220 350 186 300 120 270" stroke-width="2.4"/>
        <path d="M290 420C292 360 286 300 270 240" stroke-width="2.2"/>
        <path d="M210 420C196 372 160 340 100 330" stroke-width="2.2"/>
    </g>
    <g fill="none" stroke-linecap="round" class="sc-plumes-soft">
        <path d="M168 206C146 182 130 160 122 128" stroke-width="9"/>
        <path d="M222 214C214 186 210 160 214 126" stroke-width="8"/>
        <path d="M274 252C270 220 272 196 282 166" stroke-width="7"/>
    </g>
    <g fill="none" stroke-linecap="butt" class="sc-plumes">
        <path d="M168 206C146 182 130 160 122 128" stroke-width="11" stroke-dasharray="1.6 2.4"/>
        <path d="M222 214C214 186 210 160 214 126" stroke-width="10" stroke-dasharray="1.6 2.4"/>
        <path d="M274 252C270 220 272 196 282 166" stroke-width="9" stroke-dasharray="1.4 2.2"/>
    </g>
</svg>
