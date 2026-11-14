<?php

namespace App\Policies;

use App\Models\Utilisateurs\User;
use App\Models\Reservations\Reservation;

class ReservationPolicy
{
    public function view(User $user, Reservation $reservation): bool
    {
        return $user->getKey() === $reservation->id_voyageur
            || $user->getKey() === $reservation->id_hote
            || $user->estAdmin();
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $user->getKey() === $reservation->id_voyageur
            || $user->getKey() === $reservation->id_hote;
    }
}
