<?php

namespace App\Services\Administration;

use App\Enums\StatutAnnonce;
use App\Enums\StatutReservation;
use App\Enums\TypeNotification;
use App\Models\Administration\TicketLitige;
use App\Models\Annonces\Annonce;
use App\Models\Evaluations\Evaluation;
use App\Models\Notifications\Notification;
use App\Models\Paiements\Paiement;
use App\Models\Paiements\Remboursement;
use App\Models\Paiements\Versement;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use App\Models\Utilisateurs\VerificationIdentite;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class AdminService
{
    /**
     * Récupère l'ensemble des KPIs et statistiques globales de la plateforme (4.13.1).
     */
    public function getStatistiquesGlobales(): array
    {
        $totalUsers = User::count();
        $totalHotes = User::where('est_hote', true)->whereDoesntHave('administrateur')->count();
        $totalVoyageurs = User::where('est_voyageur', true)->whereDoesntHave('administrateur')->count();
        $totalActifs = User::where('est_actif', true)->count();
        $totalInactifs = User::where('est_actif', false)->count();
        $verificationsEnCours = VerificationIdentite::where('statut', VerificationIdentite::STATUT_EN_COURS)->count();

        $annoncesTotal = Annonce::count();
        $annoncesPubliees = Annonce::where('statut', StatutAnnonce::PUBLIEE->value)->count();
        $annoncesEnAttente = Annonce::where('statut', StatutAnnonce::EN_ATTENTE->value)->count();
        $annoncesSuspendues = Annonce::where('statut', StatutAnnonce::SUSPENDUE->value)->count();
        $annoncesRejetees = Annonce::where('statut', StatutAnnonce::REJETEE->value)->count();

        $reservationsTotal = Reservation::count();
        $reservationsEnAttente = Reservation::where('statut', StatutReservation::EN_ATTENTE->value)->count();
        $reservationsConfirmees = Reservation::where('statut', StatutReservation::CONFIRMEE->value)->count();
        $reservationsEnCours = Reservation::where('statut', StatutReservation::EN_COURS->value)->count();
        $reservationsTerminees = Reservation::where('statut', StatutReservation::TERMINEE->value)->count();
        $reservationsAnnulees = Reservation::where('statut', StatutReservation::ANNULEE->value)->count();
        $reservationsRefusees = Reservation::where('statut', StatutReservation::REFUSEE->value)->count();
        $reservationsExpirees = Reservation::where('statut', StatutReservation::EXPIREE->value)->count();

        $totalPaiements = (float) Paiement::where('statut', 'reussi')->sum('montant');
        $nbPaiements = Paiement::where('statut', 'reussi')->count();
        $totalVersements = (float) Versement::where('statut', 'traite')->sum('montant');
        $nbVersements = Versement::where('statut', 'traite')->count();
        $totalRemboursements = (float) Remboursement::sum('montant');
        $nbRemboursements = Remboursement::count();

        $avisSignales = Evaluation::where('est_signale', true)->count();
        $litigesEnCours = TicketLitige::where('statut', TicketLitige::STATUT_EN_COURS)->count();
        $litigesClotures = TicketLitige::where('statut', TicketLitige::STATUT_CLOTURE)->count();

        // Récupération des éléments urgents nécessitant une action
        $annoncesUrgentes = Annonce::with(['hote', 'categorie'])
            ->where('statut', StatutAnnonce::EN_ATTENTE->value)
            ->latest('date_creation')
            ->limit(5)
            ->get();

        $litigesUrgents = TicketLitige::with(['declarant', 'evaluation'])
            ->where('statut', TicketLitige::STATUT_EN_COURS)
            ->latest('date_creation')
            ->limit(5)
            ->get();

        return [
            'utilisateurs' => [
                'total' => $totalUsers,
                'hotes' => $totalHotes,
                'voyageurs' => $totalVoyageurs,
                'actifs' => $totalActifs,
                'inactifs' => $totalInactifs,
                'verifications_en_cours' => $verificationsEnCours,
            ],
            'annonces' => [
                'total' => $annoncesTotal,
                'publiees' => $annoncesPubliees,
                'en_attente' => $annoncesEnAttente,
                'suspendues' => $annoncesSuspendues,
                'rejetees' => $annoncesRejetees,
            ],
            'reservations' => [
                'total' => $reservationsTotal,
                'en_attente' => $reservationsEnAttente,
                'confirmees' => $reservationsConfirmees,
                'en_cours' => $reservationsEnCours,
                'terminees' => $reservationsTerminees,
                'annulees' => $reservationsAnnulees,
                'refusees' => $reservationsRefusees,
                'expirees' => $reservationsExpirees,
            ],
            'finances' => [
                'volume_total_paiements' => $totalPaiements,
                'nombre_transactions' => $nbPaiements,
                'volume_versements_hotes' => $totalVersements,
                'nombre_versements' => $nbVersements,
                'volume_remboursements' => $totalRemboursements,
                'nombre_remboursements' => $nbRemboursements,
            ],
            'moderation' => [
                'avis_signales' => $avisSignales,
                'litiges_en_cours' => $litigesEnCours,
                'litiges_clotures' => $litigesClotures,
            ],
            'urgences' => [
                'annonces_en_attente' => $annoncesUrgentes,
                'litiges_ouverts' => $litigesUrgents,
            ],
        ];
    }

    /**
     * Liste paginée des utilisateurs avec filtres multiples.
     */
    public function getUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::with(['profil', 'verificationIdentite'])->latest('date_creation');

        if (!empty($filters['recherche'])) {
            $like = '%' . mb_strtolower(trim($filters['recherche'])) . '%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(nom) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(prenom) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
            });
        }

        if (!empty($filters['role']) && $filters['role'] !== 'tous') {
            if ($filters['role'] === 'hote') {
                $query->where('est_hote', true)->whereDoesntHave('administrateur');
            } elseif ($filters['role'] === 'voyageur') {
                $query->where('est_voyageur', true)->whereDoesntHave('administrateur');
            } elseif ($filters['role'] === 'admin') {
                $query->whereHas('administrateur');
            } elseif ($filters['role'] === 'en_attente') {
                $query->where('est_hote', true)
                      ->whereHas('verificationIdentite', function ($q) {
                          $q->where('statut', VerificationIdentite::STATUT_EN_COURS);
                      });
            }
        }

        if (isset($filters['statut']) && $filters['statut'] !== 'tous') {
            if ($filters['statut'] === 'actif') {
                $query->where('est_actif', true);
            } elseif ($filters['statut'] === 'inactif') {
                $query->where('est_actif', false);
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Met à jour les informations de contact d'un utilisateur.
     */
    public function updateUser(string $userId, array $data): User
    {
        $user = User::findOrFail($userId);

        $user->update(array_intersect_key($data, array_flip(['nom', 'prenom', 'email', 'telephone'])));

        return $user->fresh(['profil', 'verificationIdentite']);
    }

    /**
     * Supprime définitivement un compte utilisateur.
     */
    public function deleteUser(string $userId, ?string $currentUserId = null): void
    {
        $user = User::findOrFail($userId);

        if ($currentUserId && $user->id_utilisateur === $currentUserId) {
            throw new InvalidArgumentException("Vous ne pouvez pas supprimer votre propre compte administrateur.");
        }

        $user->delete();
    }

    /**
     * Active ou désactive un compte utilisateur.
     */
    public function toggleUserActif(string $userId, ?string $currentUserId = null): User
    {
        $user = User::findOrFail($userId);

        if ($currentUserId && $user->id_utilisateur === $currentUserId) {
            throw new InvalidArgumentException("Vous ne pouvez pas désactiver votre propre compte administrateur.");
        }

        $user->est_actif = !$user->est_actif;
        $user->save();

        return $user;
    }

    /**
     * Liste paginée des annonces pour la modération.
     */
    public function getAnnonces(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Annonce::with([
            'hote.profil',
            'hote.verificationIdentite',
            'categorie',
            'politiqueAnnulation',
        ])->latest('date_creation');

        if (!empty($filters['statut']) && $filters['statut'] !== 'tous') {
            $query->where('statut', $filters['statut']);
        }

        if (!empty($filters['recherche'])) {
            $like = '%' . mb_strtolower(trim($filters['recherche'])) . '%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(titre) LIKE ?', [$like])
                  ->orWhereHas('hote', function ($qhote) use ($like) {
                      $qhote->whereRaw('LOWER(nom) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(prenom) LIKE ?', [$like]);
                  })
                  ->orWhereHas('categorie', function ($qcat) use ($like) {
                      $qcat->whereRaw('LOWER(ville) LIKE ?', [$like]);
                  });
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Approuve et publie une annonce en attente.
     */
    public function approuverAnnonce(string $annonceId): Annonce
    {
        $annonce = Annonce::findOrFail($annonceId);
        $annonce->statut = StatutAnnonce::PUBLIEE->value;
        $annonce->motif_moderation = null;
        $annonce->save();

        Notification::create([
            'id_utilisateur' => $annonce->id_hote,
            'titre' => 'Annonce approuvée !',
            'contenu' => "Félicitations ! Votre hébergement \"{$annonce->titre}\" a été validé et est désormais visible par les voyageurs.",
            'type_alerte' => TypeNotification::SYSTEME->value,
            'est_lue' => false,
        ]);

        return $annonce;
    }

    /**
     * Suspend ou rejette une annonce avec motif.
     */
    public function suspendreAnnonce(string $annonceId, string $motif, string $nouveauStatut = 'suspendue'): Annonce
    {
        $statutCible = $nouveauStatut === 'rejetee'
            ? StatutAnnonce::REJETEE->value
            : StatutAnnonce::SUSPENDUE->value;

        $annonce = Annonce::findOrFail($annonceId);
        $annonce->statut = $statutCible;
        $annonce->motif_moderation = $motif;
        $annonce->save();

        Notification::create([
            'id_utilisateur' => $annonce->id_hote,
            'titre' => 'Notification de modération de votre annonce',
            'contenu' => "Votre annonce \"{$annonce->titre}\" a été mise à jour avec le statut \"{$statutCible}\". Motif : {$motif}",
            'type_alerte' => TypeNotification::SYSTEME->value,
            'est_lue' => false,
        ]);

        return $annonce;
    }

    /**
     * Liste des avis signalés nécessitant modération.
     */
    public function getAvisSignales(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Evaluation::with(['auteur.profil', 'cible.profil', 'annonce', 'reservation.annonce'])
            ->where('est_signale', true)
            ->latest('date_creation');

        if (!empty($filters['recherche'])) {
            $like = '%' . mb_strtolower(trim($filters['recherche'])) . '%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(commentaire) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(motif_signalement) LIKE ?', [$like])
                  ->orWhereHas('auteur', function ($qa) use ($like) {
                      $qa->whereRaw('LOWER(nom) LIKE ?', [$like])
                         ->orWhereRaw('LOWER(prenom) LIKE ?', [$like])
                         ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                  });
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Supprime définitivement un avis signalé non conforme et notifie auteur et cible.
     */
    public function supprimerAvis(string $avisId): void
    {
        $avis = Evaluation::findOrFail($avisId);
        $idAuteur = $avis->id_auteur;
        $idCible = $avis->id_cible;

        $avis->delete();

        if ($idAuteur) {
            Notification::create([
                'id_utilisateur' => $idAuteur,
                'titre' => 'Avis supprimé par la modération',
                'contenu' => "Votre avis a été supprimé suite à un signalement, car il ne respectait pas nos conditions d'utilisation.",
                'type_alerte' => TypeNotification::SYSTEME->value,
                'est_lue' => false,
            ]);
        }

        if ($idCible) {
            Notification::create([
                'id_utilisateur' => $idCible,
                'titre' => 'Signalement traité',
                'contenu' => "L'avis que vous avez signalé a été retiré de la plateforme par nos équipes.",
                'type_alerte' => TypeNotification::SYSTEME->value,
                'est_lue' => false,
            ]);
        }
    }

    /**
     * Ignore le signalement, conserve l'avis publié et notifie la cible.
     */
    public function ignorerSignalementAvis(string $avisId): Evaluation
    {
        $avis = Evaluation::findOrFail($avisId);
        $avis->est_signale = false;
        $avis->motif_signalement = null;
        $avis->save();

        if ($avis->id_cible) {
            Notification::create([
                'id_utilisateur' => $avis->id_cible,
                'titre' => 'Signalement rejeté',
                'contenu' => "Après examen, nos équipes ont estimé que l'avis signalé ne violait pas nos règles. L'avis a été conservé.",
                'type_alerte' => TypeNotification::SYSTEME->value,
                'est_lue' => false,
            ]);
        }

        return $avis;
    }

    /**
     * Liste paginée de toutes les réservations de la plateforme (supervision).
     */
    public function getReservations(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Reservation::with(['annonce', 'voyageur.profil', 'hote.profil'])->latest('date_creation');

        if (!empty($filters['statut']) && $filters['statut'] !== 'tous') {
            $query->where('statut', $filters['statut']);
        }

        if (!empty($filters['recherche'])) {
            $like = '%' . mb_strtolower(trim($filters['recherche'])) . '%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('voyageur', function ($qv) use ($like) {
                    $qv->whereRaw('LOWER(nom) LIKE ?', [$like])
                       ->orWhereRaw('LOWER(prenom) LIKE ?', [$like]);
                })->orWhereHas('hote', function ($qh) use ($like) {
                    $qh->whereRaw('LOWER(nom) LIKE ?', [$like])
                       ->orWhereRaw('LOWER(prenom) LIKE ?', [$like]);
                })->orWhereHas('annonce', function ($qa) use ($like) {
                    $qa->whereRaw('LOWER(titre) LIKE ?', [$like]);
                });
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Liste consolidée des transactions financières (paiements, versements, remboursements).
     */
    public function getTransactionsFinancieres(string $type = 'paiements', array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if ($type === 'versements') {
            $query = Versement::with(['reservation.annonce', 'hote'])->latest('date_liberation_prevue');
            if (!empty($filters['statut']) && $filters['statut'] !== 'tous') {
                $query->where('statut', $filters['statut']);
            }
            return $query->paginate($perPage);
        }

        if ($type === 'remboursements') {
            $query = Remboursement::with(['reservation.annonce', 'voyageur'])->latest('date_remboursement');
            if (!empty($filters['motif']) && $filters['motif'] !== 'tous') {
                $query->where('motif', $filters['motif']);
            }
            return $query->paginate($perPage);
        }

        // Par défaut : paiements reçus
        $query = Paiement::with(['reservation.annonce', 'voyageur'])->latest('date_transaction');
        if (!empty($filters['statut']) && $filters['statut'] !== 'tous') {
            $query->where('statut', $filters['statut']);
        }
        return $query->paginate($perPage);
    }
}
