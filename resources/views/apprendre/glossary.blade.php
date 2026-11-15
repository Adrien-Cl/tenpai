@php($letters = \App\View\Learn\Glossary::byLetter())
<x-guide :guide="$guide">
    <x-slot:intro>{{ __('Le riichi se joue avec des mots japonais, même en France. Les voici, de A à Z, chacun expliqué en une ou deux phrases.') }}</x-slot:intro>

    {{-- L'index : une lettre par initiale, qui mène à ses mots. --}}
    <nav aria-label="{{ __('Index alphabétique') }}" class="flex flex-wrap gap-1.5">
        @foreach (array_keys($letters) as $letter)
            <a href="#lettre-{{ strtolower($letter) }}" class="grid size-9 place-items-center rounded-md bg-paper-sunk font-bold shadow-[inset_0_0_0_1px_var(--paper-line)] hover:bg-ink hover:text-paper">{{ $letter }}</a>
        @endforeach
    </nav>

    @foreach ($letters as $letter => $entries)
        <section aria-labelledby="lettre-{{ strtolower($letter) }}">
            <h2 id="lettre-{{ strtolower($letter) }}" class="border-b border-paper-line pb-2">{{ $letter }}</h2>
            <dl class="mt-4 space-y-5">
                @foreach ($entries as $entry)
                    <div id="{{ $entry['id'] }}" class="scroll-mt-6">
                        <dt class="flex flex-wrap items-baseline gap-x-3">
                            <dfn class="text-lg font-bold not-italic">{{ $entry['term'] }}</dfn>
                            <span lang="ja" class="text-ink-muted">{{ $entry['japanese'] }}</span>
                        </dt>
                        <dd class="mt-1">
                            {{ $entry['definition'] }}
                            @if ($entry['guide'])
                                <a href="{{ localized_route($entry['guide']->routeName()) }}" class="text-sm whitespace-nowrap text-ink-muted underline decoration-ink-line underline-offset-4 hover:text-ink hover:decoration-shu">{{ $entry['guide']->label() }} →</a>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endforeach

    <p class="guide-note">{{ __('Un mot manque ? Les noms des yaku, avec leur valeur, sont tous dans le barème.') }} <a href="{{ localized_route('scoring') }}">{{ __('Voir tous les yaku') }}</a>.</p>
</x-guide>
