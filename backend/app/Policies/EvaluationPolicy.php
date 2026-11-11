<?php

namespace App\Policies;

use App\Models\Utilisateurs\User;

class EvaluationPolicy
{
    public function create(User $user): bool { return true; }
}
