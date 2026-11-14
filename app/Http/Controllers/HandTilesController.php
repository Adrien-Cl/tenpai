<?php

namespace App\Http\Controllers;

use App\Domain\Mahjong\Generation\HandGenerator;
use App\Domain\Mahjong\Generation\Seed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HandTilesController extends Controller
{
    /** Autant que la liste « À revoir » peut en garder. */
    public const int MAX_SEEDS = 20;

    /**
     * Les tuiles de quelques mains, en abrégé, pour les redessiner dans la liste « À revoir »
     * quand le navigateur ne les a pas gardées. Les graines invalides sont ignorées.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $seeds = collect(explode(',', (string) $request->query('graines', '')))
            ->filter(fn (string $seed): bool => Seed::isValid($seed))
            ->unique()
            ->take(self::MAX_SEEDS);

        $generator = new HandGenerator;
        $tiles = $seeds->mapWithKeys(fn (string $seed): array => [
            Seed::fromString($seed)->value => $generator->generate(Seed::fromString($seed))->tableCodes(),
        ]);

        return response()->json((object) $tiles->all(), 200, ['Cache-Control' => 'public, max-age=604800']);
    }
}
