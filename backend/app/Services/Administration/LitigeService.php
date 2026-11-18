<?php

namespace App\Services\Administration;

use App\Enums\TypeNotification;
use App\Models\Administration\Administrateur;
use App\Models\Administration\TicketLitige;
use App\Models\Notifications\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LitigeService
{
    /**
     * Liste paginée des litiges avec filtres.
     */
    public function getLitiges(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TicketLitige::with([
            'declarant.profil',
            'evaluation.annonce',
            'evaluation.cible',
            'reservation.annonce',
            'reservation.voyageur',
            'reservation.hote',
            'admin.utilisateur',
        ])->latest('date_creation');

        if (! empty($filters['statut']) && $filters['statut'] !== 'tous') {
            $query->where('statut', $filters['statut']);
        }

        if (! empty($filters['recherche'])) {
            $like = '%'.mb_strtolower(trim($filters['recherche'])).'%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(motif) LIKE ?', [$like])
                    ->orWhereHas('declarant', function ($qdeclarant) use ($like) {
                        $qdeclarant->whereRaw('LOWER(nom) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(prenom) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                    });
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Résout et clôture définitivement un ticket de litige et notifie les deux parties.
     */
    public function resoudreTicket(string $ticketId, string $decision, string $adminUserId): TicketLitige
    {
        $litige = TicketLitige::with(['evaluation', 'reservation'])->findOrFail($ticketId);

        $admin = Administrateur::where('id_utilisateur', $adminUserId)->first();

        $litige->statut = TicketLitige::STATUT_CLOTURE;
        $litige->date_cloture = now();
        if ($admin) {
            $litige->id_admin = $admin->id_admin;
        }
        $litige->save();

        // Notifier le déclarant de la résolution
        if ($litige->id_declarant) {
            Notification::create([
                'id_utilisateur' => $litige->id_declarant,
                'titre' => 'Résolution de litige',
                'contenu' => "Votre litige (\"{$litige->motif}\") a été arbitré et clôturé par le support. Décision/Message : {$decision}",
                'type_alerte' => TypeNotification::SYSTEME->value,
                'est_lue' => false,
            ]);
        }

        // Notifier la seconde partie concernée (cible de l'évaluation ou autre acteur de la réservation)
        $secondePartieId = $litige->evaluation?->id_cible;
        if (! $secondePartieId && $litige->reservation) {
            $secondePartieId = $litige->reservation->id_voyageur === $litige->id_declarant
                ? $litige->reservation->id_hote
                : $litige->reservation->id_voyageur;
        }

        if ($secondePartieId && $secondePartieId !== $litige->id_declarant) {
            Notification::create([
                'id_utilisateur' => $secondePartieId,
                'titre' => 'Résolution de litige',
                'contenu' => "Le litige déclaré vous concernant (\"{$litige->motif}\") a été arbitré et clôturé par le support. Décision/Message : {$decision}",
                'type_alerte' => TypeNotification::SYSTEME->value,
                'est_lue' => false,
            ]);
        }

        return $litige->fresh(['declarant.profil', 'evaluation.annonce', 'reservation.annonce', 'admin.utilisateur']);
    }
}
