<?php

namespace App\Services\Paiements;

use App\Enums\StatutReservation;
use App\Enums\TypeNotification;
use App\Exceptions\ApiException;
use App\Models\Paiements\Paiement;
use App\Models\Paiements\Recu;
use App\Models\Reservations\RecapitulatifReservation;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use App\Repositories\Interfaces\PaiementRepositoryInterface;
use App\Services\Notifications\NotificationService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Passerelle de paiement simulée, séquestre et reçus (RG20, RG21, RG22).
 */
class PaiementService
{
    public function __construct(
        protected PaiementRepositoryInterface $paiementRepository,
        protected VersementService $versementService,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Traite un paiement simulé par carte bancaire pour une réservation confirmée.
     *
     * @return array{paiement: Paiement, recu: Recu}
     */
    public function traiterPaiementSimule(string $reservationId, User $voyageur, array $carteInfo): array
    {
        $reservation = Reservation::with(['annonce.hote', 'voyageur', 'paiement'])->findOrFail($reservationId);

        if ($reservation->id_voyageur !== $voyageur->getKey()) {
            throw new ApiException('Vous n\'êtes pas le voyageur associé à cette réservation.', 403, 'ACCES_REFUSE');
        }

        if ($reservation->statut !== StatutReservation::CONFIRMEE) {
            throw new ApiException(
                'Seules les réservations confirmées peuvent faire l\'objet d\'un règlement.',
                409,
                'RESERVATION_NON_CONFIRMEE'
            );
        }

        if ($reservation->paiement && $reservation->paiement->statut === 'reussi') {
            throw new ApiException(
                'Cette réservation a déjà été payée avec succès.',
                409,
                'PAIEMENT_DEJA_EFFECTUE'
            );
        }

        // Simulation passerelle bancaire (RG20) :
        // Mode ouvert / simulation : toute formule ou numéro de carte saisi est accepté.
        // Seul le numéro de test d'échec unitaire spécifique '4000000000001111' simule un refus bancaire.
        $numero = preg_replace('/[\s\-]/', '', (string) ($carteInfo['numero_carte'] ?? '4242424242424242'));
        $succes = ($numero !== '4000000000001111');

        $paiement = $this->paiementRepository->create([
            'id_paiement'      => Str::uuid()->toString(),
            'id_reservation'   => $reservationId,
            'id_voyageur'      => $voyageur->getKey(),
            'montant'          => $reservation->montant_total,
            'methode_paiement' => 'carte_bancaire',
            'statut'           => $succes ? 'reussi' : 'echoue',
        ]);

        if (!$succes) {
            throw new ApiException(
                'La transaction a été refusée par la banque émettrice. Veuillez vérifier vos coordonnées bancaires.',
                402,
                'PAIEMENT_REFUSE'
            );
        }

        // 1. Émission du reçu fiscal
        $recu = $this->genererRecu($paiement->id_paiement);

        // 2. Génération du récapitulatif comptable
        $this->genererRecapitulatif($reservation, $paiement);

        // 3. Planification du versement en séquestre (RG21)
        $this->versementService->planifierVersement($reservationId);

        // 4. Notifications in-app & e-mail
        $this->notificationService->notifier(
            $voyageur,
            TypeNotification::PAIEMENT,
            'Paiement confirmé',
            "Votre paiement de {$reservation->montant_total} DH pour {$reservation->annonce?->titre} a été validé. Reçu N° {$recu->numero_facture}.",
            $reservation,
            ['paiement_id' => $paiement->id_paiement, 'recu_id' => $recu->id_recu],
            true
        );

        if ($reservation->annonce?->hote) {
            $this->notificationService->notifier(
                $reservation->annonce->hote,
                TypeNotification::PAIEMENT,
                'Séjour réglé par le voyageur',
                "Le voyageur a réglé la réservation pour {$reservation->annonce->titre}. Les fonds sont sécurisés en séquestre.",
                $reservation,
                ['reservation_id' => $reservation->id_reservation],
                true
            );
        }

        return [
            'paiement' => $paiement->fresh(['recu']),
            'recu'     => $recu,
        ];
    }

    /**
     * Génère un reçu officiel avec numéro de facture unique (ex: ACM-20261119-A1B2C3D4).
     */
    public function genererRecu(string $paiementId): Recu
    {
        $hash = strtoupper(substr(str_replace('-', '', $paiementId), 0, 8));
        $numeroFacture = 'ACM-' . now()->format('Ymd') . '-' . $hash;

        return Recu::create([
            'id_recu'         => Str::uuid()->toString(),
            'id_paiement'     => $paiementId,
            'numero_facture'  => $numeroFacture,
            'chemin_pdf'      => null,
        ]);
    }

    /**
     * Génère l'entité RecapitulatifReservation.
     */
    protected function genererRecapitulatif(Reservation $reservation, Paiement $paiement): RecapitulatifReservation
    {
        $nbNuits = Carbon::parse($reservation->date_arrivee)->diffInDays(Carbon::parse($reservation->date_depart));
        if ($nbNuits < 1) {
            $nbNuits = 1;
        }

        $montantHote = round((float) $reservation->montant_total - (float) $reservation->frais_service, 2);

        return RecapitulatifReservation::create([
            'id_recapitulatif'     => Str::uuid()->toString(),
            'id_reservation'       => $reservation->id_reservation,
            'details_sejour'       => json_encode([
                'nb_nuits'    => $nbNuits,
                'tarif_nuit'  => (float) $reservation->annonce?->tarif_nuit,
                'arrivee'     => Carbon::parse($reservation->date_arrivee)->format('Y-m-d'),
                'depart'      => Carbon::parse($reservation->date_depart)->format('Y-m-d'),
            ], JSON_UNESCAPED_UNICODE),
            'montant_total'        => $reservation->montant_total,
            'frais_service'        => $reservation->frais_service,
            'montant_hote'         => $montantHote,
            'coordonnees_voyageur' => json_encode([
                'nom'       => $reservation->voyageur?->nom,
                'prenom'    => $reservation->voyageur?->prenom,
                'email'     => $reservation->voyageur?->email,
                'telephone' => $reservation->voyageur?->telephone,
            ], JSON_UNESCAPED_UNICODE),
            'coordonnees_hote'     => json_encode([
                'nom'       => $reservation->hote?->nom,
                'prenom'    => $reservation->hote?->prenom,
                'email'     => $reservation->hote?->email,
            ], JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Consultation du détail d'un reçu avec vérification des droits d'accès.
     */
    public function getRecuDetail(string $paiementId, User $user): Paiement
    {
        $paiement = $this->paiementRepository->findById($paiementId);

        if (!$paiement) {
            throw new ApiException('Paiement introuvable.', 404, 'PAIEMENT_INTROUVABLE');
        }

        $estVoyageur = $paiement->id_voyageur === $user->getKey();
        $estHote     = $paiement->reservation?->id_hote === $user->getKey();
        $estAdmin    = method_exists($user, 'estAdmin') ? $user->estAdmin() : false;

        if (!$estVoyageur && !$estHote && !$estAdmin) {
            throw new ApiException('Accès non autorisé à ce reçu.', 403, 'ACCES_REFUSE');
        }

        return $paiement;
    }

    /**
     * Historique paginé des transactions du voyageur.
     */
    public function getHistorique(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return $this->paiementRepository->getHistoriqueUtilisateur($user->getKey(), $perPage);
    }
}
