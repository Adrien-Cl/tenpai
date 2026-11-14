{{-- La marque d'une étape corrigée (◎ juste, ✕ à revoir, · donnée), et ce qu'elle dit aux lecteurs d'écran. --}}
@if ($result)
    @if ($result['given'] ?? false)
        <span class="{{ $givenClass }}" aria-hidden="true">·</span>
    @else
        <span class="{{ $result['ok'] ? $okClass : $koClass }}" aria-hidden="true">{{ $result['ok'] ? '◎' : '✕' }}</span>
    @endif
    <span class="sr-only">{{ ($result['given'] ?? false) ? __('donnée') : ($result['ok'] ? __('juste') : ($result['revealed'] ? __('réponse affichée') : __('à revoir'))) }}</span>
@endif
