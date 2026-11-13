<?php

namespace App\Training;

enum FieldState: string
{
    /** Réponse juste. */
    case Ok = 'ok';

    /** Réponse fausse ou manquante. */
    case Ko = 'ko';

    /** Réponse affichée à la demande (« Voir la réponse »). */
    case Revealed = 'revealed';

    /** Yaku annoncé à tort. */
    case Extra = 'extra';

    /** Yaku oublié. */
    case Missed = 'missed';
}
