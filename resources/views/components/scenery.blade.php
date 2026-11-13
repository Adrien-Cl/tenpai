{{--
    Le décor de la pièce, à la manière des estampes « bambou et moineau » (竹に雀) de Hiroshige :
    soleil (lune la nuit) dans les brumes dorées, bambouseraie, collines au lavis,
    et une variante par saison. Purement décoratif.
--}}
@props(['season'])
<div class="scenery" data-season="{{ $season->value }}" aria-hidden="true">
    <svg class="sc-sky" viewBox="0 0 440 320">
        <defs>
            <radialGradient id="sc-glow">
                <stop offset=".45" style="stop-color: var(--orb-glow)"/>
                <stop offset="1" stop-color="transparent" stop-opacity="0"/>
            </radialGradient>
            {{-- Paillettes d'or (kirihaku) semées sur les brumes. --}}
            <pattern id="sc-kirihaku" width="26" height="14" patternUnits="userSpaceOnUse">
                <rect x="3" y="4" width="2.2" height="2.2" transform="rotate(20 4 5)"/>
                <rect x="15" y="9" width="1.6" height="1.6" transform="rotate(-15 16 10)"/>
                <rect x="21" y="2" width="1.2" height="1.2"/>
            </pattern>
        </defs>
        <circle class="sc-orb-glow" cx="282" cy="132" r="170" fill="url(#sc-glow)"/>
        <circle class="sc-orb" cx="282" cy="132" r="96"/>
        <g class="sc-mist">
            <rect x="150" y="150" width="290" height="24" rx="12"/>
            <rect x="60" y="188" width="250" height="20" rx="10"/>
            <rect x="236" y="86" width="204" height="16" rx="8"/>
            <rect x="120" y="226" width="150" height="14" rx="7"/>
        </g>
        <g class="sc-flecks">
            <rect x="150" y="150" width="290" height="24" rx="12"/>
            <rect x="60" y="188" width="250" height="20" rx="10"/>
            <rect x="236" y="86" width="204" height="16" rx="8"/>
        </g>
        <g class="sparrow" transform="translate(408 262) rotate(-14) scale(0.55)">
            <path class="sp-wing" d="M0 0C-8 -20 -26 -28 -40 -26C-30 -18 -18 -8 -6 2Z"/>
            <ellipse class="sp-body" cx="2" cy="4" rx="16" ry="9"/>
            <path class="sp-tail" d="M-12 4L-28 0L-26 8Z"/>
            <circle class="sp-body" cx="16" cy="-1" r="7.5"/>
            <ellipse class="sp-cheek" cx="18" cy="2" rx="4.5" ry="3.4"/>
            <path class="sp-ink" d="M22.5 -3L28 -1L22.5 1Z"/>
            <circle class="sp-ink" cx="19" cy="-3" r="1.4"/>
            <path class="sp-wing" d="M6 0C10 -16 4 -30 -6 -36C-4 -24 -4 -12 0 2Z"/>
        </g>
    </svg>

    @include('scenery.bamboo', ['season' => $season])

    <svg class="sc-hills" viewBox="0 0 1200 240" preserveAspectRatio="xMidYMax slice">
        <defs>
            <linearGradient id="sc-wash" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="currentColor" stop-opacity="1"/>
                <stop offset="1" stop-color="currentColor" stop-opacity="0"/>
            </linearGradient>
        </defs>
        <path class="h2" fill="url(#sc-wash)" d="M0 150C70 120 120 92 170 104S260 70 320 58 410 96 470 112 560 66 640 40 760 92 820 110 930 72 990 80 1100 116 1200 100V240H0Z"/>
        <path class="h1" fill="url(#sc-wash)" d="M0 196C90 172 150 150 230 160S350 126 420 136 520 176 600 168 720 120 800 130 900 168 980 160 1100 134 1200 150V240H0Z"/>
    </svg>

    @include('scenery.'.$season->value)
</div>
