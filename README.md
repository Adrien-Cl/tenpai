# Tenpai

[![CI](https://github.com/Adrien-Cl/tenpai/actions/workflows/ci.yml/badge.svg)](https://github.com/Adrien-Cl/tenpai/actions/workflows/ci.yml)

**聴牌, tenpai : il ne manque plus qu'une tuile.**

Tenpai est un entraîneur au comptage des mains de mahjong riichi. La main est déjà gagnée ; à vous de dire combien elle vaut, en cinq étapes corrigées et expliquées, sans calculette.

## Fonctionnalités

- **Entraînement** (`/entrainement`) : une main gagnante générée aléatoirement, à compter étape par étape.
  1. **Yaku** : repérer les yaku de la main, par leur nom.
  2. **Han** : donner la valeur de chacun, main ouverte ou fermée.
  3. **Dora** : compter les dora, les cinq rouges et les ura-dora.
  4. **Fu** : décompter les fu groupe par groupe, puis arrondir.
  5. **Points** : trouver la limite et ce que paie chaque joueur.
- **Mains partageables** : chaque main est tirée d'une graine (`/entrainement/{graine}`). La même URL donne toujours la même main.
- **Défi du jour** (`/defi`) : la même main pour tout le monde, tirée de la date (heure de Paris).
- **Barème** (`/bareme`) : les tables de han, de fu et de points, à consulter à tout moment.
- **Décor saisonnier** : la scène d'accueil change avec les saisons, à la manière des estampes « bambou et moineau ».

### Règles de la table

Riichi courant, style salle japonaise :

- cinq tuiles rouges (aka-dora) ;
- pas de kiriage mangan ;
- double vent compté à 4 fu.

Ces options sont définies dans [`App\Domain\Mahjong\Rules`](app/Domain/Mahjong/Rules.php). Elles changent le calcul, jamais la main générée.

## Stack

- PHP 8.4+ et Laravel 13
- Livewire 4 pour l'entraînement interactif
- Tailwind CSS 4 et Vite
- Pest 4 pour les tests

Les tuiles sont dessinées en SVG côté serveur et servies sous forme de sprite (`/tuiles.svg`).

## Architecture

| Dossier | Rôle |
|---|---|
| [`app/Domain/Mahjong`](app/Domain/Mahjong) | Le moteur, en PHP pur, sans dépendance à Laravel : tuiles, décomposition des mains, détection des yaku, calcul des fu et des points, génération des mains à partir d'une graine. |
| [`app/Training`](app/Training) | Les cinq étapes de l'entraînement, la correction des réponses. |
| [`app/Livewire`](app/Livewire) | Le composant d'entraînement. |
| [`app/View`](app/View) | Le rendu : dessin des tuiles, barème, saisons. |

Un test d'architecture ([`tests/Unit/Mahjong/ArchitectureTest.php`](tests/Unit/Mahjong/ArchitectureTest.php)) garantit que le moteur reste indépendant du framework et que la génération des mains n'utilise aucun hasard non reproductible.

## Installation

Prérequis : PHP 8.4+, Composer, Node.js 22+.

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

## Tests et qualité

```bash
php artisan test --compact   # Pest : moteur (Unit) et pages (Feature)
vendor/bin/pint              # Formatage du code
```

Les tests du moteur (`tests/Unit`) tournent sur des classes PHP pures. Seuls les tests `tests/Feature` démarrent Laravel.

## Intégration continue

GitHub Actions ([`.github/workflows/ci.yml`](.github/workflows/ci.yml)) s'exécute à chaque push sur `main` et sur chaque pull request :

- **Qualité du code** : `composer validate` et Pint ;
- **Build front** : compilation des assets avec Vite ;
- **Tests** : Pest sur PHP 8.4 et 8.5 ;
- **Audit de sécurité** : `composer audit` et `npm audit`.

Dependabot propose chaque semaine les mises à jour de Composer, npm et des actions GitHub.

## Licence

MIT.
