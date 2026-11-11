<?php

namespace App\Enums;

enum RoleUtilisateur: string
{
    case VOYAGEUR = 'voyageur';
    case HOTE = 'hote';
    case ADMIN = 'admin';
}
