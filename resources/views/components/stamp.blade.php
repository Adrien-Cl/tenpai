{{-- Sceau hanko de correction : 正解 juste, 残念 à revoir, 答 réponse affichée ou donnée. --}}
@props(['result', 'big' => false])
@php
    $given = $result['given'] ?? false;
    [$chars, $label, $variant] = match (true) {
        $given => [['答'], __('Donnée'), 'hanko--rev'],
        $result['ok'] => [['正', '解'], __('Juste'), ''],
        $result['revealed'] => [['答'], __('Réponse'), 'hanko--rev'],
        default => [['残', '念'], __('À revoir'), 'hanko--ko'],
    };
    $glyphSize = match (true) {
        $big && count($chars) > 1 => 'size-9',
        $big => 'size-12',
        count($chars) > 1 => 'size-6',
        default => 'size-9',
    };
@endphp
<div {{ $attributes->class('flex flex-col items-center gap-1.5') }}>
    <span @class(['hanko is-new', $variant, 'size-24!' => $big]) aria-hidden="true">
        <span class="flex flex-col items-center">
            @foreach ($chars as $char)
                <x-glyph :char="$char" :class="$glyphSize" />
            @endforeach
        </span>
    </span>
    <span @class(['text-xs font-bold', 'text-ink-muted' => $given, 'text-ok' => ! $given && $result['ok'], 'text-ko' => ! $given && ! $result['ok']])>{{ $label }}</span>
</div>
