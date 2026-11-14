<?php

namespace App\Repositories\Eloquent;

use App\Models\Reservations\Reservation;
use App\Repositories\Interfaces\ReservationRepositoryInterface;

class ReservationRepository implements ReservationRepositoryInterface
{
    public function findById(string $id) { return Reservation::find($id); }
    public function getParVoyageur(string $voyageurId) { return Reservation::where('id_voyageur', $voyageurId)->get(); }
    public function getParHote(string $hoteId) { return Reservation::where('id_hote', $hoteId)->get(); }
    public function create(array $data) { return Reservation::create($data); }
    public function updateStatut(string $id, string $statut) { return tap(Reservation::findOrFail($id))->update(['statut' => $statut]); }
}
