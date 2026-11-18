<?php

namespace App\Repositories\Eloquent;

use App\Enums\StatutAnnonce;
use App\Enums\StatutReservation;
use App\Models\Annonces\Annonce;
use App\Repositories\Interfaces\AnnonceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AnnonceRepository implements AnnonceRepositoryInterface
{
    public function findById(string $id) { return Annonce::find($id); }
    public function search(array $criteres) { return Annonce::query()->paginate(15); }
    public function create(array $data) { return Annonce::create($data); }
    public function update(string $id, array $data) { return tap(Annonce::findOrFail($id))->update($data); }
    public function delete(string $id) { return Annonce::destroy($id); }

    public function rechercherAnnoncesDisponibles(array $criteres, int $parPage): LengthAwarePaginator
    {
        // [PORTED] seules les annonces publiées
        $query = Annonce::query()->where('statut', StatutAnnonce::PUBLIEE->value);

        // [PORTED] catégorie exacte
        if (!empty($criteres['id_categorie'])) {
            $query->where('id_categorie', $criteres['id_categorie']);
        }

        // [PORTED] type de logement (insensible à la casse : la colonne est un texte libre)
        if (!empty($criteres['type_logement'])) {
            $query->whereRaw('LOWER(type_logement) = ?', [mb_strtolower($criteres['type_logement'])]);
        }

        // [PORTED] localisation : ville, région ou pays (ancien `location`).
        // L'ancien LIKE était insensible à la casse sous MySQL ; LOWER() garde ce
        // comportement sous PostgreSQL et SQLite (tests).
        if (!empty($criteres['ville'])) {
            $loc = '%' . mb_strtolower($criteres['ville']) . '%';
            $query->whereHas('categorie', function ($q) use ($loc) {
                $q->whereRaw('LOWER(ville) LIKE ?', [$loc])
                  ->orWhereRaw('LOWER(region) LIKE ?', [$loc])
                  ->orWhereRaw('LOWER(pays) LIKE ?', [$loc]);
            });
        }

        // [PORTED] capacité
        if (!empty($criteres['voyageurs'])) {
            $query->where('capacite', '>=', (int) $criteres['voyageurs']);
        }

        // [PORTED] dates : exclut les annonces ayant une réservation confirmée ou en
        // cours qui chevauche l'intervalle (3 cas, recopiés de l'ancien code).
        if (!empty($criteres['date_arrivee']) && !empty($criteres['date_depart'])) {
            $checkin = $criteres['date_arrivee'];
            $checkout = $criteres['date_depart'];

            $query->whereDoesntHave('reservations', function ($q) use ($checkin, $checkout) {
                $q->whereIn('statut', [StatutReservation::CONFIRMEE->value, StatutReservation::EN_COURS->value])
                  ->where(function ($sub) use ($checkin, $checkout) {
                      $sub->where(function ($sq) use ($checkin, $checkout) {
                          $sq->whereDate('date_arrivee', '>=', $checkin)->whereDate('date_arrivee', '<', $checkout);
                      })->orWhere(function ($sq) use ($checkin, $checkout) {
                          $sq->whereDate('date_depart', '>', $checkin)->whereDate('date_depart', '<=', $checkout);
                      })->orWhere(function ($sq) use ($checkin, $checkout) {
                          $sq->whereDate('date_arrivee', '<=', $checkin)->whereDate('date_depart', '>=', $checkout);
                      });
                  });
            });
        }

        // [ADDED] tranche de prix (CdC 4.13)
        if (isset($criteres['prix_min'])) {
            $query->where('tarif_nuit', '>=', $criteres['prix_min']);
        }
        if (isset($criteres['prix_max'])) {
            $query->where('tarif_nuit', '<=', $criteres['prix_max']);
        }

        // [ADDED] équipements : chaque équipement demandé doit figurer dans la colonne texte
        foreach ($criteres['equipements'] ?? [] as $equipement) {
            $query->whereRaw('LOWER(equipements) LIKE ?', ['%' . mb_strtolower(trim($equipement)) . '%']);
        }

        // [ADDED] tri + ordre stable pour la pagination
        match ($criteres['tri'] ?? 'recent') {
            'prix_asc' => $query->orderBy('tarif_nuit'),
            'prix_desc' => $query->orderByDesc('tarif_nuit'),
            default => $query->orderByDesc('date_creation'),
        };
        $query->orderBy('id_annonce');

        // [PORTED] eager loading categorie + hote
        return $this->avecNote($query->with(['categorie', 'hote']))->paginate($parPage);
    }

    public function getAnnoncesEnVedette(int $limite): Collection
    {
        // [ADDED] l'ancien accueil affichait toutes les annonces publiées sans filtre ;
        // on garde ce comportement, limité aux plus récentes.
        return $this->avecNote(
            Annonce::query()->where('statut', StatutAnnonce::PUBLIEE->value)->with('categorie')
        )->orderByDesc('date_creation')->limit($limite)->get();
    }

    public function findDetailsById(string $id): ?Annonce
    {
        // [PORTED] mêmes relations que l'ancien findById()
        return $this->avecNote(
            Annonce::query()->with(['hote.profil', 'categorie', 'politiqueAnnulation'])
        )->find($id);
    }

    /** [PORTED] partie lecture de calculerNoteGlobale() : moyenne des avis voyageurs. */
    private function avecNote(Builder $query): Builder
    {
        $avisVoyageurs = fn ($q) => $q->where('type_auteur', 'voyageur');

        return $query
            ->withAvg(['evaluations as note_moyenne' => $avisVoyageurs], 'note')
            ->withCount(['evaluations as nb_avis' => $avisVoyageurs]);
    }
}
