{{-- Le sceau de Tenpai : 聴牌 réservé en ivoire dans un carré vermillon. --}}
@php($sprite = \App\View\Tiles\TileSprite::url())
<svg {{ $attributes->class('flex-none') }} viewBox="0 0 100 100" aria-hidden="true" focusable="false">
    <rect x="3" y="3" width="94" height="94" rx="20" fill="#C23A2B"/>
    <rect x="9" y="9" width="82" height="82" rx="14" fill="none" stroke="#FBF3E3" stroke-opacity=".35" stroke-width="2"/>
    <g fill="#FBF3E3" transform="rotate(-3 50 50)">
        <use href="{{ $sprite }}#{{ \App\View\Tiles\TileArt::glyphId('聴') }}" x="27" y="11" width="46" height="46"/>
        <use href="{{ $sprite }}#{{ \App\View\Tiles\TileArt::glyphId('牌') }}" x="27" y="47" width="46" height="46"/>
    </g>
</svg>
