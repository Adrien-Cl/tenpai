{{--
    Des tuiles d'exemple, en notation courte : « 123m 456p 77z ». Chaque bloc séparé d'un espace forme un groupe.
    0 désigne un cinq rouge. size : largeur d'une tuile.
--}}
@props(['tiles', 'size' => '2.25rem'])
@php
    $groups = array_map(function (string $group): array {
        preg_match_all('/(\d+)([mpsz])/', $group, $parts, PREG_SET_ORDER);
        $row = [];
        foreach ($parts as [, $digits, $suit]) {
            foreach (str_split($digits) as $digit) {
                $row[] = ['tile' => \App\Domain\Mahjong\Tile::parse($digit.$suit)[0], 'red' => $digit === '0'];
            }
        }

        return $row;
    }, preg_split('/\s+/', trim($tiles)));
@endphp
<span {{ $attributes->class('inline-flex flex-wrap items-end gap-x-[calc(var(--tile)*0.4)] gap-y-3 align-middle') }} style="--tile: {{ $size }}">
    @foreach ($groups as $group)
        <span class="tiles">@foreach ($group as $tile)<x-tile :tile="$tile['tile']" :red="$tile['red']" />@endforeach</span>
    @endforeach
</span>
