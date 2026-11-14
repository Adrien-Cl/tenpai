<?php

use App\Domain\Mahjong\Generation\Theme;
use App\Domain\Mahjong\Hand\MeldType;
use App\Domain\Mahjong\Limit;
use App\Domain\Mahjong\Text;
use App\Domain\Mahjong\Tile;
use App\Domain\Mahjong\Wait;
use App\Domain\Mahjong\Wind;
use App\Domain\Mahjong\Yaku;
use App\Domain\Mahjong\Yakuman;
use App\Livewire\Training;
use App\Support\ScriptText;
use App\Support\TableRules;
use App\View\Season;

/**
 * Une chaîne PHP entre apostrophes, complète (suivie d'une virgule ou d'une parenthèse).
 */
const QUOTED = "'((?:[^'\\\\]|\\\\.)*)'";

/**
 * Les phrases écrites en dur dans le code : __('…'), trans_choice('…'), Text::get('…'),
 * et les deux branches de __($condition ? '…' : '…').
 *
 * @return list<string>
 */
function literalTranslationKeys(): array
{
    $files = [
        ...glob(app_path('{,*/,*/*/,*/*/*/,*/*/*/*/}*.php'), GLOB_BRACE),
        ...glob(resource_path('views/{,*/,*/*/,*/*/*/}*.blade.php'), GLOB_BRACE),
    ];
    $keys = [];

    foreach ($files as $file) {
        $source = file_get_contents($file);

        preg_match_all('/(?:__|trans_choice|Text::get)\(\s*'.QUOTED.'\s*[,)]/u', $source, $calls);
        preg_match_all('/(?:__|Text::get)\(\s*[$\w>()-]+\s*\?\s*'.QUOTED.'\s*:\s*'.QUOTED.'/u', $source, $ternaries);

        array_push($keys, ...$calls[1], ...$ternaries[1], ...$ternaries[2]);
    }

    return array_map(stripslashes(...), $keys);
}

/**
 * Les phrases composées par le moteur (match, noms des tuiles…), relevées au passage.
 *
 * @return list<string>
 */
function engineTranslationKeys(): array
{
    $keys = [];

    Text::translateWith(function (string $text, array $replace) use (&$keys): string {
        $keys[] = $text;

        return $text;
    });

    try {
        foreach (Yaku::cases() as $yaku) {
            $yaku->label();
            $yaku->shortLabel();
            $yaku->gloss();
            $yaku->description();
        }

        foreach (Yakuman::cases() as $yakuman) {
            $yakuman->gloss();
            $yakuman->description();
        }

        foreach (Limit::cases() as $limit) {
            $limit->label();
            $limit->condition();
        }

        foreach (Wait::cases() as $wait) {
            $wait->gloss();
            $wait->description();
        }

        array_map(fn (MeldType $meld) => $meld->label(), MeldType::cases());
        array_map(fn (Wind $wind) => $wind->label(), Wind::cases());
        array_map(fn (Theme $theme) => [$theme->label(), $theme->gloss()], Theme::cases());

        for ($tile = 0; $tile < Tile::COUNT; $tile++) {
            Tile::name($tile, red: true);
            Tile::label($tile, red: true);
        }
    } finally {
        Text::translateWith(fn (string $text, array $replace): string => __($text, $replace));
    }

    return $keys;
}

/**
 * Les phrases passées par variable : options des règles, exercices, saisons, textes du JavaScript.
 *
 * @return list<string>
 */
function dynamicTranslationKeys(): array
{
    $keys = [...ScriptText::KEYS, 'og.png', 'l j F', 'j F'];

    foreach (TableRules::OPTIONS as [, $label, $help]) {
        array_push($keys, $label, $help);
    }

    foreach (Training::DRILLS as ['label' => $label]) {
        $keys[] = $label;
    }

    app()->setLocale('fr');

    foreach (Season::cases() as $season) {
        $keys[] = $season->kigo();
    }

    return $keys;
}

/**
 * @return array<string, string>
 */
function englishTranslations(): array
{
    return json_decode(file_get_contents(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);
}

it('traduit en anglais chaque phrase du site', function () {
    $keys = array_unique([...literalTranslationKeys(), ...engineTranslationKeys(), ...dynamicTranslationKeys()]);
    $english = englishTranslations();

    $missing = array_values(array_filter($keys, fn (string $key): bool => trim($english[$key] ?? '') === ''));

    expect($missing)->toBe([], "Phrases sans traduction dans lang/en.json :\n".implode("\n", $missing));
});

it('ne garde pas de traduction inutile', function () {
    $keys = array_unique([...literalTranslationKeys(), ...engineTranslationKeys(), ...dynamicTranslationKeys()]);

    $unused = array_values(array_diff(array_keys(englishTranslations()), $keys));

    expect($unused)->toBe([], "Traductions que plus rien n’utilise :\n".implode("\n", $unused));
});

it('garde la même liste de valeurs « :nom » dans chaque traduction', function () {
    foreach (englishTranslations() as $french => $english) {
        preg_match_all('/:([a-z]\w*)/i', $french, $source);
        preg_match_all('/:([a-z]\w*)/i', $english, $target);

        $normalize = fn (array $names): array => array_values(array_unique(array_map('strtolower', $names)));

        expect($normalize($target[1]))->toEqualCanonicalizing($normalize($source[1]), "Valeurs différentes pour « {$french} »");
    }
});

it('envoie au JavaScript toutes ses phrases, et seulement elles', function () {
    $keys = [];

    foreach (['app.js', 'yaku-search.js'] as $file) {
        $source = file_get_contents(resource_path("js/{$file}"));
        preg_match_all("/\\bt\\('((?:[^'\\\\]|\\\\.)*)'/u", $source, $calls);
        preg_match_all("/^\\s*\\[\\/.*\\/, '((?:[^'\\\\]|\\\\.)*)'\\],\$/mu", $source, $notes);
        array_push($keys, ...$calls[1], ...$notes[1]);
    }

    expect(array_values(array_unique(array_map(stripslashes(...), $keys))))->toEqualCanonicalizing(ScriptText::KEYS);
});
