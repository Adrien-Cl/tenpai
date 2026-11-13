<?php

namespace App\Http\Controllers;

use App\View\Tiles\TileSprite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TileSpriteController extends Controller
{
    /**
     * Le sprite SVG des tuiles. L'URL change avec son contenu : on peut le garder en cache longtemps.
     */
    public function __invoke(Request $request): Response
    {
        $etag = '"'.TileSprite::version().'"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, ['ETag' => $etag]);
        }

        return response(TileSprite::content(), 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag' => $etag,
        ]);
    }
}
