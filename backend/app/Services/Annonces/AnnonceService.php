<?php

namespace App\Services\Annonces;

use App\Enums\StatutAnnonce;
use App\Enums\StatutReservation;
use App\Models\Annonces\Annonce;
use App\Repositories\Interfaces\AnnonceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class AnnonceService
{
    public function __construct(protected AnnonceRepositoryInterface $annonceRepository) {}

    public function rechercherAnnonces(array $criteres) { /* TODO */ }
    public function creerAnnonce(string $hoteId, array $data) { /* TODO */ }
    public function modifierAnnonce(string $id, array $data) { /* TODO */ }
    public function supprimerAnnonce(string $id) { /* TODO */ }

    // DS1 [PORTED]
    public function rechercherAnnoncesPubliees(array $criteres, int $parPage): LengthAwarePaginator
    {
        return $this->annonceRepository->rechercherAnnoncesDisponibles($criteres, $parPage);
    }

    // [ADDED]
    public function getAnnoncesEnVedette(int $limite = 6): Collection
    {
        return $this->annonceRepository->getAnnoncesEnVedette($limite);
    }

    // DS2 [PORTED] : null si inexistante ou non publiée (l'ancien code levait une
    // Exception "Annonce non disponible ou inexistante.")
    public function getDetailsAnnonce(string $id): ?Annonce
    {
        if (!Str::isUuid($id)) {
            return null; // évite une erreur SQL 500 sur la colonne uuid PostgreSQL
        }
        $annonce = $this->annonceRepository->findDetailsById($id);

        return ($annonce && $annonce->statut === StatutAnnonce::PUBLIEE) ? $annonce : null;
    }

    // [PORTED] getDatesOccupees() verbatim, adapted to the new enums and the relation name `calendriers`
    public function getDatesOccupees(Annonce $annonce): array
    {
        $occupees = [];

        // 1. Réservations confirmées / en cours : nuits [arrivée, départ[
        foreach ($annonce->reservations()->whereIn('statut', [StatutReservation::CONFIRMEE->value, StatutReservation::EN_COURS->value])->get() as $res) {
            $curr = Carbon::parse($res->date_arrivee);
            $end = Carbon::parse($res->date_depart);
            while ($curr->lt($end)) {
                $occupees[] = $curr->format('Y-m-d');
                $curr->addDay();
            }
        }

        // 2. Blocages manuels : [début, fin] inclus
        foreach ($annonce->calendriers()->where('est_disponible', false)->get() as $cal) {
            $curr = Carbon::parse($cal->date_debut);
            $end = Carbon::parse($cal->date_fin);
            while ($curr->lte($end)) {
                $occupees[] = $curr->format('Y-m-d');
                $curr->addDay();
            }
        }

        $occupees = array_values(array_unique($occupees));
        sort($occupees);

        return $occupees;
    }
}
