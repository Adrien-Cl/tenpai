# Tenpai

[![CI](https://github.com/Adrien-Cl/tenpai/actions/workflows/ci.yml/badge.svg)](https://github.com/Adrien-Cl/tenpai/actions/workflows/ci.yml)

**聴牌, tenpai : il ne manque plus qu'une tuile.**

Tenpai est un entraîneur au comptage des mains de mahjong riichi. La main est déjà gagnée ; à vous de dire combien elle vaut, en cinq étapes corrigées et expliquées, sans calculette. En français, et en anglais sous `/en`.

## Fonctionnalités

- **Entraînement** (`/entrainement`) : une main gagnante générée aléatoirement, à compter étape par étape.
  1. **Yaku** : repérer les yaku de la main, par leur nom.
  2. **Han** : donner la valeur de chacun, main ouverte ou fermée.
  3. **Dora** : compter les dora, les cinq rouges et les ura-dora.
  4. **Fu** : décompter les fu groupe par groupe, puis arrondir.
  5. **Points** : trouver la limite et ce que paie chaque joueur.
- **Entraînement ciblé** (`/entrainement?theme=chinitsu`) : une main qui contient à coup sûr le yaku choisi, parmi 17 thèmes.
- **Exercices courts** (`/exercice/fu/{graine}`, `/exercice/points/{graine}`) : la main commence aux fu ou aux points, les étapes précédentes sont données. Comptés à part.
- **Mains partageables** : chaque main est tirée d'une graine (`/entrainement/{graine}`, `chinitsu-ab12cd34` pour un thème). La même URL donne toujours la même main, avec son image d'aperçu (`/apercu/{graine}.png`).
- **Défi du jour** (`/defi`) : la même main pour tout le monde, tirée de la date (heure de Paris). Résultat à partager, calendrier des cinq dernières semaines et série en cours.
- **Revoir ses erreurs** : les mains ratées à refaire, et les yaku les plus souvent oubliés. Les progrès restent dans le navigateur.
- **Barème** (`/bareme`) : les tables de han, de fu et de points, à consulter à tout moment.
- **Application installable** : manifeste et service worker ; hors ligne, l'accueil et le barème restent consultables.
- **Décor saisonnier** : la scène d'accueil change avec les saisons, à la manière des estampes « bambou et moineau ».

### Règles de la table

Par défaut, riichi courant, style salle japonaise :

- cinq tuiles rouges (aka-dora) ;
- pas de kiriage mangan ;
- double vent compté à 4 fu.

Chacun peut les changer (bouton réglages de l'en-tête) ; le choix est gardé dans un cookie. Les options sont définies dans [`App\Domain\Mahjong\Rules`](app/Domain/Mahjong/Rules.php). Elles changent le calcul, jamais la main générée.

## Langues

Le français est la langue source, sans préfixe ; l'anglais vit sous `/en`, avec ses propres adresses (`/en/training`, `/en/scoring`…). Les routes sont déclarées une fois par langue dans [`routes/web.php`](routes/web.php), à partir de [`App\Support\Locale`](app/Support/Locale.php) ; dans les vues, `localized_route()` donne l'adresse dans la langue de la page.

Les phrases françaises servent de clés de traduction (`__('…')`, fichier [`lang/en.json`](lang/en.json)) :

- le moteur, qui ne connaît pas Laravel, passe ses textes par [`App\Domain\Mahjong\Text`](app/Domain/Mahjong/Text.php), branché sur les traductions au démarrage ;
- le JavaScript reçoit ses phrases de [`App\Support\ScriptText`](app/Support/ScriptText.php) et les lit avec `t()` ([`resources/js/text.js`](resources/js/text.js)).

[`tests/Feature/TranslationTest.php`](tests/Feature/TranslationTest.php) vérifie que chaque phrase a sa traduction, qu'aucune traduction n'est orpheline et que la liste du JavaScript suit le code.

## Stack

- PHP 8.4+ et Laravel 13
- Livewire 4 pour l'entraînement interactif
- Tailwind CSS 4 et Vite
- Pest 4 (avec ses tests navigateur) et Vitest pour les tests

Les tuiles sont dessinées en SVG côté serveur et servies sous forme de sprite (`/tuiles.svg`).

## Architecture

| Dossier | Rôle |
|---|---|
| [`app/Domain/Mahjong`](app/Domain/Mahjong) | Le moteur, en PHP pur, sans dépendance à Laravel : tuiles, décomposition des mains, détection des yaku, calcul des fu et des points, génération des mains à partir d'une graine. |
| [`app/Training`](app/Training) | Les cinq étapes de l'entraînement, la correction des réponses. |
| [`app/Livewire`](app/Livewire) | Le composant d'entraînement (mains complètes et exercices courts). |
| [`app/View`](app/View) | Le rendu : dessin des tuiles, aperçu des mains partagées, barème, saisons. |
| [`app/Support`](app/Support) | Langues, règles de la table, mise en forme des nombres et des calculs. |

Un test d'architecture ([`tests/Unit/Mahjong/ArchitectureTest.php`](tests/Unit/Mahjong/ArchitectureTest.php)) garantit que le moteur reste indépendant du framework et que la génération des mains n'utilise aucun hasard non reproductible.

## Installation

Prérequis : PHP 8.4+, Composer, Node.js 22+. L'extension PHP `gd` dessine l'aperçu propre à chaque main partagée (sans elle, l'image générale du site est utilisée) ; les tests navigateur demandent aussi l'extension `sockets`.

```bash
git clone https://github.com/Adrien-Cl/tenpai.git
cd tenpai
composer setup
```

`composer setup` installe les dépendances PHP et JS, crée le `.env`, génère la clé, lance les migrations et compile les assets.

## Développement

```bash
composer dev
```

Lance les processus de développement (`php artisan dev` : serveur, Vite…) avec rechargement à chaud.

Les images de partage (tuiles pré-rendues, fonds, `public/og.png`) sont produites avec les styles du site. Après un changement du dessin des tuiles ou de la charte, site lancé :

```bash
node resources/og/generate.mjs http://localhost:8000
```

## Tests et qualité

```bash
php artisan test --compact                             # Pest : moteur, pages et navigateur
php artisan test --compact --exclude-testsuite=Browser # sans navigateur
npm test                                               # Vitest : recherche des yaku
vendor/bin/pint                                        # Formatage du code
```

Les tests du moteur (`tests/Unit`) tournent sur des classes PHP pures. Les tests `tests/Feature` démarrent Laravel ; les tests `tests/Browser` pilotent un vrai Chromium (Playwright : `npx playwright install chromium` la première fois).

## Intégration continue

GitHub Actions ([`.github/workflows/ci.yml`](.github/workflows/ci.yml)) s'exécute à chaque push sur `main` et sur chaque pull request :

- **Qualité du code** : `composer validate` et Pint ;
- **Front** : tests Vitest, puis compilation des assets avec Vite ;
- **Tests** : Pest sur PHP 8.4 et 8.5 ;
- **Tests navigateur** : Pest et Playwright (captures d'écran conservées en cas d'échec) ;
- **Audit de sécurité** : `composer audit` et `npm audit`.

Dependabot propose chaque semaine les mises à jour de Composer, npm et des actions GitHub.

## Licence

MIT.
