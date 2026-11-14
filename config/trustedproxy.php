<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reverse proxies de confiance
    |--------------------------------------------------------------------------
    |
    | Derrière un reverse proxy qui termine le TLS, l'application reçoit la
    | requête en HTTP : sans lui faire confiance, elle se croit servie en HTTP
    | et produit des adresses « http:// » (dont celle des mises à jour
    | Livewire, bloquée par le navigateur comme contenu mixte), et prend
    | l'adresse du proxy pour celle de chaque visiteur.
    |
    | Liste d'adresses ou de plages séparées par des virgules, « * » pour tout
    | appelant, ou « private_ranges » pour les réseaux privés (Docker…).
    | Vide, aucun en-tête X-Forwarded-* n'est pris en compte.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
