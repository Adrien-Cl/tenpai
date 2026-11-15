<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * Le robots.txt : tout est ouvert aux robots, et le sitemap est indiqué avec l'adresse du site.
     * Le JSON des mains à revoir ne sert qu'à l'interface.
     */
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: '.route('hands.tiles', absolute: false),
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
