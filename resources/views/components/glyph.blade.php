{{-- Un caractère japonais du sprite (étapes, sceaux, logo), coloré par currentColor. --}}
@props(['char'])
<svg {{ $attributes->class('glyph') }} viewBox="0 0 100 100" aria-hidden="true" focusable="false"><use href="{{ \App\View\Tiles\TileSprite::url() }}#{{ \App\View\Tiles\TileArt::glyphId($char) }}" fill="currentColor"/></svg>
