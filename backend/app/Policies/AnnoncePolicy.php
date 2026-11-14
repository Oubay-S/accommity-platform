<?php

namespace App\Policies;

use App\Models\Utilisateurs\User;
use App\Models\Annonces\Annonce;

class AnnoncePolicy
{
    public function create(User $user): bool
    {
        return $user->estHoteVerifie();
    }

    public function update(User $user, Annonce $annonce): bool
    {
        return $user->getKey() === $annonce->id_hote || $user->estAdmin();
    }

    public function delete(User $user, Annonce $annonce): bool
    {
        return $user->getKey() === $annonce->id_hote || $user->estAdmin();
    }
}
