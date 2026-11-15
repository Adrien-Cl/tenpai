<?php

it('sert chaque page de la partie « Apprendre », dans les deux langues', function (string $url, string $title, string $heading) {
    $this->get($url)
        ->assertOk()
        ->assertSee("<title>{$title} · Tenpai</title>", false)
        ->assertSee($heading);
})->with([
    'accueil' => ['/apprendre', 'Apprendre le mahjong riichi : règles et guides pour débutants', 'Le riichi en trois phrases'],
    'tuiles' => ['/apprendre/les-tuiles', 'Les 136 tuiles du mahjong riichi', 'La notation 123m'],
    'partie' => ['/apprendre/deroulement-d-une-partie', 'Comment se déroule une partie de mahjong riichi', 'Les appels : chii, pon, kan'],
    'points' => ['/apprendre/calcul-des-points', 'Calculer les points au mahjong riichi : le guide débutant', 'Les valeurs à connaître'],
    'lexique' => ['/apprendre/lexique', 'Lexique du mahjong riichi : les mots de la table', 'Index alphabétique'],
    'accueil anglais' => ['/en/learn', 'Learn riichi mahjong: rules and guides for beginners', 'Riichi in three sentences'],
    'tuiles en anglais' => ['/en/learn/tiles', 'The 136 tiles of riichi mahjong', '123m notation'],
    'partie en anglais' => ['/en/learn/how-to-play', 'How a game of riichi mahjong is played', 'Calls: chii, pon, kan'],
    'points en anglais' => ['/en/learn/scoring-basics', 'Scoring in riichi mahjong: the beginner’s guide', 'The values to know'],
    'lexique en anglais' => ['/en/learn/glossary', 'Riichi mahjong glossary: the words of the table', 'Alphabetical index'],
]);

it('relie chaque guide à sa traduction', function () {
    $this->get('/apprendre/lexique')
        ->assertSee('<link rel="alternate" hreflang="fr" href="'.url('/apprendre/lexique').'">', false)
        ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/en/learn/glossary').'">', false);
});

it('mène aux guides depuis le menu, le pied de page et l’accueil', function () {
    $this->get('/')
        ->assertSeeInOrder(['<header', 'href="'.url('/apprendre').'"', '</header>'], false)
        ->assertSeeInOrder(['id="learn-title"', 'href="'.url('/apprendre/les-tuiles').'"', 'href="'.url('/apprendre/lexique').'"'], false)
        ->assertSeeInOrder(['<footer', 'href="'.url('/apprendre/calcul-des-points').'"', '</footer>'], false);
});

it('enchaîne les guides dans l’ordre de lecture', function () {
    $this->get('/apprendre/deroulement-d-une-partie')
        ->assertSeeInOrder(['Guide précédent', 'Les tuiles', 'Guide suivant', 'Calcul des points']);

    $this->get('/apprendre/lexique')
        ->assertSeeInOrder(['Guide précédent', 'Calcul des points', 'Et maintenant', 'Commencer une main']);
});

it('compte la main d’exemple avec le moteur, dans la langue de la page', function () {
    $this->get('/apprendre/calcul-des-points')
        ->assertSee('Riichi (1 han) et Yakuhai : Chun (1 han)')
        ->assertSee('Brelan fermé de terminales/honneurs : 8 fu.')
        ->assertSee('Total : 40 fu.')
        ->assertSee("Résultat : 3 han 40 fu, le joueur qui a défaussé le 6 paie 5\u{00A0}200 points.");

    $this->get('/en/learn/scoring-basics')
        ->assertSee('Result: 3 han 40 fu, the player who discarded the 6 pays 5,200 points.');
});

it('donne à chaque terme du lexique une ancre, et des liens vers les guides', function () {
    $this->get('/apprendre/lexique')
        ->assertSee('<div id="furiten" class="scroll-mt-6">', false)
        ->assertSee('<dfn class="text-lg font-bold not-italic">Furiten</dfn>', false)
        ->assertSee('<span lang="ja" class="text-ink-muted">振聴</span>', false)
        ->assertSeeInOrder(['id="lettre-a"', 'Agari', 'id="lettre-y"', 'Yaochuuhai'], false);
});
