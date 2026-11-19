<?php

namespace App\Services\Paiements;

use App\Enums\StatutReservation;
use App\Models\Paiements\Remboursement;
use App\Models\Reservations\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Calcul et exécution des remboursements selon les politiques d'annulation (RG17, RG18, RG19, RG23, RG24).
 */
class RemboursementService
{
    /**
     * Calcule l'aperçu du montant remboursable avant annulation.
     *
     * @return array{
     *   montant_paye: float,
     *   montant_rembourse: float,
     *   montant_non_remb: float,
     *   taux_applique: float,
     *   politique: string|null,
     *   description: string|null
     * }
     */
    public function calculerRemboursement(string $reservationId, string $acteur = 'voyageur'): array
    {
        $reservation = Reservation::with(['politique', 'paiement'])->findOrFail($reservationId);
        $paiement = $reservation->paiement;

        if (!$paiement || $paiement->statut !== 'reussi') {
            return [
                'montant_paye'      => 0.0,
                'montant_rembourse' => 0.0,
                'montant_non_remb'  => 0.0,
                'taux_applique'     => 0.0,
                'politique'         => $reservation->politique?->type_politique,
                'description'       => 'Aucun paiement n\'a été débité.',
            ];
        }

        $montantPaye = (float) $paiement->montant;

        // RG24 : Si l'hôte annule -> remboursement intégral garanti (100%)
        if ($acteur === 'hote') {
            return [
                'montant_paye'      => $montantPaye,
                'montant_rembourse' => $montantPaye,
                'montant_non_remb'  => 0.0,
                'taux_applique'     => 100.0,
                'politique'         => $reservation->politique?->type_politique,
                'description'       => 'Annulation par l\'hôte : remboursement intégral garanti (100%).',
            ];
        }

        // Si la réservation est encore en attente -> remboursement intégral
        if ($reservation->statut === StatutReservation::EN_ATTENTE) {
            return [
                'montant_paye'      => $montantPaye,
                'montant_rembourse' => $montantPaye,
                'montant_non_remb'  => 0.0,
                'taux_applique'     => 100.0,
                'politique'         => $reservation->politique?->type_politique,
                'description'       => 'Demande en attente : remboursement intégral.',
            ];
        }

        // RG17, RG18, RG19 : Calcul selon la politique d'annulation rattachée
        $politique = $reservation->politique;
        if (!$politique) {
            return [
                'montant_paye'      => $montantPaye,
                'montant_rembourse' => $montantPaye,
                'montant_non_remb'  => 0.0,
                'taux_applique'     => 100.0,
                'politique'         => 'flexible',
                'description'       => 'Politique par défaut (remboursement intégral).',
            ];
        }

        $dateArrivee = Carbon::parse($reservation->date_arrivee)->startOfDay();
        $heuresAvantArrivee = now()->diffInHours($dateArrivee, false);

        $delaiTotal = (int) $politique->delai_remb_total;
        $delaiPartiel = (int) $politique->delai_remb_partiel;
        $tauxPartiel = (float) $politique->taux_remb_partiel;

        if ($heuresAvantArrivee >= $delaiTotal) {
            $taux = 100.0;
        } elseif ($delaiPartiel > 0 && $heuresAvantArrivee >= $delaiPartiel) {
            $taux = $tauxPartiel;
        } else {
            $taux = 0.0;
        }

        $montantRembourse = round($montantPaye * ($taux / 100), 2);
        $montantNonRemb = round($montantPaye - $montantRembourse, 2);

        return [
            'montant_paye'      => $montantPaye,
            'montant_rembourse' => $montantRembourse,
            'montant_non_remb'  => $montantNonRemb,
            'taux_applique'     => $taux,
            'politique'         => $politique->type_politique,
            'description'       => $politique->description,
        ];
    }

    /**
     * Exécute le remboursement réel et enregistre l'entité Remboursement (RG23).
     */
    public function executerRemboursement(string $reservationId, ?array $apercu = null, string $acteur = 'voyageur'): ?Remboursement
    {
        $apercu = $apercu ?? $this->calculerRemboursement($reservationId, $acteur);

        $reservation = Reservation::with('paiement')->findOrFail($reservationId);
        $paiement = $reservation->paiement;

        if (!$paiement || $paiement->statut !== 'reussi') {
            return null;
        }

        $motifMap = [
            'voyageur' => 'annulation_voyageur',
            'hote'     => 'annulation_hote',
            'systeme'  => 'expiration_demande',
        ];

        $motif = $motifMap[$acteur] ?? 'annulation_voyageur';

        if ($apercu['montant_rembourse'] > 0) {
            $paiement->update(['statut' => 'rembourse']);
        }

        return Remboursement::create([
            'id_remboursement' => Str::uuid()->toString(),
            'id_reservation'   => $reservationId,
            'id_voyageur'      => $reservation->id_voyageur,
            'id_paiement'      => $paiement->id_paiement,
            'montant'          => $apercu['montant_rembourse'],
            'taux_applique'    => $apercu['taux_applique'],
            'motif'            => $motif,
        ]);
    }
}
