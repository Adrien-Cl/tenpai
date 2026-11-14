{{--
    Une tuile de mahjong en SVG.
    tile : numéro 0-33 · red : cinq rouge · side : couchée (tuile prise par un appel)
    back : retournée · win : tuile gagnante · group : ligne des fu qui l'éclaire (variable Alpine « lit »)
--}}
@props([
    'tile' => 0,
    'red' => false,
    'side' => false,
    'back' => false,
    'win' => false,
    'group' => null,
])
@php
    $sprite = \App\View\Tiles\TileSprite::url();
    $face = $sprite.'#f-'.$tile.($red ? 'r' : '');
    $label = $back ? __('Tuile retournée') : \App\Domain\Mahjong\Tile::name((int) $tile, (bool) $red);
@endphp
<span {{ $attributes->class(['tile', 'tile--side' => $side && ! $back, 'tile--down' => $back, 'tile--win' => $win]) }} role="img" aria-label="{{ $label }}" @if ($group) x-bind:class="{ 'tile--lit': lit === '{{ $group }}' }" @endif>@if ($back)<svg viewBox="0 0 34 46" aria-hidden="true" focusable="false"><use href="{{ $sprite }}#tile-down"/></svg>@elseif ($side)<svg viewBox="0 0 40 40" aria-hidden="true" focusable="false"><use href="{{ $sprite }}#tile-side"/><g transform="translate(0 32) rotate(-90)"><use href="{{ $face }}" width="30" height="40"/></g></svg>@else<svg viewBox="0 0 34 46" aria-hidden="true" focusable="false"><use href="{{ $sprite }}#tile-up"/><use href="{{ $face }}" x="2" width="30" height="40"/></svg>@endif</span>
