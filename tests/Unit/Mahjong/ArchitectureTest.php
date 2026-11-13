<?php

arch('le moteur ne dépend pas de Laravel')
    ->expect('App\Domain')
    ->not->toUse(['Illuminate', 'Livewire']);

arch('le moteur ne laisse pas de code de débogage')
    ->expect('App\Domain')
    ->not->toUse(['dd', 'dump', 'var_dump', 'ray']);

arch('le moteur n’utilise pas de hasard non reproductible')
    ->expect('App\Domain\Mahjong\Generation\HandGenerator')
    ->not->toUse(['rand', 'mt_rand', 'random_int', 'shuffle', 'array_rand']);
