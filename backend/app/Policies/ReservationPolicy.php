<?php

namespace App\Policies;

use App\Models\Utilisateurs\User;
use App\Models\Reservations\Reservation;

class ReservationPolicy
{
    public function view(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->voyageur_id || $user->id === $reservation->annonce->hote_id || $user->estAdmin();
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->voyageur_id || $user->id === $reservation->annonce->hote_id;
    }
}
