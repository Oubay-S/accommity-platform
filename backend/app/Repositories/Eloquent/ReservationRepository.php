<?php

namespace App\Repositories\Eloquent;

use App\Models\Reservations\Reservation;
use App\Repositories\Interfaces\ReservationRepositoryInterface;

class ReservationRepository implements ReservationRepositoryInterface
{
    public function findById(int $id) { return Reservation::find($id); }
    public function getParVoyageur(int $voyageurId) { return Reservation::where('voyageur_id', $voyageurId)->get(); }
    public function getParHote(int $hoteId) { return Reservation::whereHas('annonce', fn($q) => $q->where('hote_id', $hoteId))->get(); }
    public function create(array $data) { return Reservation::create($data); }
    public function updateStatut(int $id, string $statut) { return tap(Reservation::findOrFail($id))->update(['statut' => $statut]); }
}
