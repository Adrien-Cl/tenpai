<?php

namespace App\Http\Controllers;

use App\Domain\Mahjong\Generation\HandGenerator;
use App\Domain\Mahjong\Generation\Seed;
use App\View\Tiles\HandPreview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class HandPreviewController extends Controller
{
    /**
     * L'image d'aperçu d'une main partagée. Sans GD, l'image générale du site.
     */
    public function __invoke(string $seed): Response|RedirectResponse
    {
        abort_unless(Seed::isValid($seed), 404);

        if (! HandPreview::isAvailable()) {
            return redirect(asset(__('og.png')));
        }

        $seed = Seed::fromString($seed);
        $png = Cache::remember(
            'apercu:'.app()->getLocale().":{$seed->value}",
            now()->addWeek(),
            fn (): string => (new HandPreview)->render((new HandGenerator)->generate($seed)),
        );

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
