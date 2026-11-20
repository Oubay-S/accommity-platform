<?php

namespace App\Services\Reservations;

use App\Enums\ModeReservation;
use App\Enums\StatutAnnonce;
use App\Enums\StatutReservation;
use App\Enums\TypeNotification;
use App\Exceptions\ApiException;
use App\Models\Annonces\Annonce;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use App\Services\Notifications\NotificationService;
use App\Services\Paiements\RemboursementService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Moteur métier des réservations (RG12, RG13, RG14, RG15, RG17).
 */
class ReservationService
{
    public function __construct(
        protected ReservationRepositoryInterface $reservationRepository,
        protected StatutService $statutService,
        protected MinuterieService $minuterieService,
        protected RemboursementService $remboursementService,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Crée une réservation (instantanée ou sur demande) avec validation anti-chevauchement.
     */
    public function creerReservation(array $data, User $voyageur): Reservation
    {
        $annonce = Annonce::with(['hote', 'politique'])->findOrFail($data['id_annonce']);

        // Vérification de la disponibilité commerciale
        if ($annonce->statut !== StatutAnnonce::PUBLIEE) {
            throw new ApiException('Cet hébergement n\'est pas disponible à la réservation.', 409, 'ANNONCE_NON_DISPONIBLE');
        }

        // Interdiction d'auto-réservation
        if ($annonce->id_hote === $voyageur->getKey()) {
            throw new ApiException('Vous ne pouvez pas réserver votre propre logement.', 403, 'AUTO_RESERVATION');
        }

        // Capacité maximale
        $nbVoyageurs = (int) $data['nb_voyageurs'];
        if ($nbVoyageurs > $annonce->capacite) {
            throw new ApiException(
                "Le nombre de personnes ({$nbVoyageurs}) dépasse la capacité d'accueil ({$annonce->capacite}).",
                422,
                'CAPACITE_DEPASSEE'
            );
        }

        $dateArrivee = Carbon::parse($data['date_arrivee'])->startOfDay();
        $dateDepart = Carbon::parse($data['date_depart'])->startOfDay();

        if ($dateDepart->lte($dateArrivee)) {
            throw new ApiException('La date de départ doit être strictement postérieure à l\'arrivée.', 422, 'DATES_INVALIDES');
        }

        $strArrivee = $dateArrivee->toDateString();
        $strDepart = $dateDepart->toDateString();

        // RG12 : Contrôle anti-chevauchement avec les réservations existantes
        if ($this->reservationRepository->existeChevauchement($annonce->id_annonce, $strArrivee, $strDepart)) {
            throw new ApiException('Ces dates ne sont plus disponibles pour cet hébergement.', 409, 'DATES_INDISPONIBLES');
        }

        // Contrôle de non-chevauchement avec les blocages manuels hôte
        $bloqueManuel = $annonce->calendriers()
            ->where('type_blockage', 'bloque_manuel')
            ->where('est_disponible', false)
            ->whereDate('date_debut', '<', $strDepart)
            ->whereDate('date_fin', '>=', $strArrivee)
            ->exists();

        if ($bloqueManuel) {
            throw new ApiException('Ces dates sont bloquées par l\'hôte sur le calendrier.', 409, 'DATES_BLOQUEES');
        }

        // Calcul tarifaire (commission plateforme fixée par configuration, défaut 12%)
        $nbNuits = $dateArrivee->diffInDays($dateDepart);
        if ($nbNuits < 1) {
            $nbNuits = 1;
        }

        $montantBrut = $nbNuits * (float) $annonce->tarif_nuit;
        $commissionRate = (float) config('hebergement.commission_rate', 0.12);
        $fraisService = round($montantBrut * $commissionRate, 2);
        $montantTotal = round($montantBrut + $fraisService, 2);

        $isInstant = $annonce->mode_reservation === ModeReservation::INSTANTANEE;

        $reservation = $this->reservationRepository->create([
            'id_reservation' => Str::uuid()->toString(),
            'id_annonce' => $annonce->id_annonce,
            'id_voyageur' => $voyageur->getKey(),
            'id_hote' => $annonce->id_hote,
            'id_politique' => $annonce->id_politique,
            'date_arrivee' => $strArrivee,
            'date_depart' => $strDepart,
            'nb_voyageurs' => $nbVoyageurs,
            'mode_reservation' => $annonce->mode_reservation,
            'montant_total' => $montantTotal,
            'frais_service' => $fraisService,
            'message_optionnel' => $data['message_optionnel'] ?? null,
            'statut' => $isInstant ? StatutReservation::CONFIRMEE->value : StatutReservation::EN_ATTENTE->value,
            'date_confirmation' => $isInstant ? now() : null,
        ]);

        if ($isInstant) {
            // Notification immédiate pour réservation instantanée
            $this->notificationService->reservationStatutChangee($reservation, $voyageur, 'Confirmée');
            if ($annonce->hote) {
                /** @var User $hote */
                $hote = $annonce->hote;
                $this->notificationService->notifier(
                    $hote,
                    TypeNotification::RESERVATION,
                    'Nouvelle réservation instantanée',
                    "{$voyageur->prenom} {$voyageur->nom} a réservé {$annonce->titre}.",
                    $reservation,
                    ['reservation_id' => $reservation->id_reservation],
                    true
                );
            }
        } else {
            // RG15 : Démarrage de la minuterie de 24h et alerte pour l'hôte
            $this->minuterieService->demarrerMinuterie24h($reservation->id_reservation);

            if ($annonce->hote) {
                /** @var User $hote */
                $hote = $annonce->hote;
                $this->notificationService->notifier(
                    $hote,
                    TypeNotification::RESERVATION,
                    'Nouvelle demande de réservation',
                    "{$voyageur->prenom} a soumis une demande pour {$annonce->titre}. Vous avez 24h pour y répondre.",
                    $reservation,
                    ['reservation_id' => $reservation->id_reservation],
                    true
                );
            }
        }

        return $this->reservationRepository->findById($reservation->id_reservation);
    }

    /**
     * Réponse de l'hôte (acceptation ou refus sous 24h — RG13).
     */
    public function repondreDemande(string $reservationId, User $hote, bool $acceptee, ?string $motifRefus = null): Reservation
    {
        $reservation = $this->reservationRepository->findById($reservationId);

        if (! $reservation) {
            throw new ApiException('Réservation introuvable.', 404, 'RESERVATION_INTROUVABLE');
        }

        if ($reservation->id_hote !== $hote->getKey()) {
            throw new ApiException('Vous n\'êtes pas l\'hôte de cette réservation.', 403, 'ACCES_REFUSE');
        }

        if ($reservation->statut !== StatutReservation::EN_ATTENTE) {
            throw new ApiException('Cette réservation n\'est plus en attente de réponse.', 409, 'STATUT_INVALIDE');
        }

        if ($acceptee) {
            // Deuxième vérification anti-chevauchement au moment de l'acceptation
            if ($this->reservationRepository->existeChevauchement(
                $reservation->id_annonce,
                Carbon::parse($reservation->date_arrivee)->toDateString(),
                Carbon::parse($reservation->date_depart)->toDateString(),
                $reservation->id_reservation
            )) {
                throw new ApiException(
                    'Ces dates ont été réservées entre-temps par un autre voyageur.',
                    409,
                    'DATES_INDISPONIBLES'
                );
            }

            $this->statutService->transitionnerStatut($reservationId, 'confirmee');
            $this->reservationRepository->update($reservationId, ['date_confirmation' => now()]);
            $this->minuterieService->annulerMinuterie($reservationId);

            if ($reservation->voyageur) {
                $this->notificationService->notifier(
                    $reservation->voyageur,
                    TypeNotification::RESERVATION,
                    'Demande de réservation acceptée !',
                    "L'hôte a accepté votre demande pour {$reservation->annonce?->titre}. Vous pouvez maintenant régler votre séjour.",
                    $reservation,
                    ['reservation_id' => $reservation->id_reservation],
                    true
                );
            }
        } else {
            if (empty(trim((string) $motifRefus))) {
                throw new ApiException('Un motif est obligatoire pour refuser une demande.', 422, 'MOTIF_REQUIS');
            }

            $this->statutService->transitionnerStatut($reservationId, 'refusee');
            $this->reservationRepository->update($reservationId, ['motif_refus' => $motifRefus]);
            $this->minuterieService->annulerMinuterie($reservationId);

            if ($reservation->voyageur) {
                $this->notificationService->notifier(
                    $reservation->voyageur,
                    TypeNotification::RESERVATION,
                    'Demande de réservation refusée',
                    "Votre demande a été refusée par l'hôte. Motif : {$motifRefus}.",
                    $reservation,
                    ['reservation_id' => $reservation->id_reservation],
                    true
                );
            }
        }

        return $this->reservationRepository->findById($reservationId);
    }

    /**
     * Annule une réservation et calcule le barème de remboursement (RG17, RG24).
     */
    public function annulerReservation(string $reservationId, User $user, string $motif): array
    {
        $reservation = $this->reservationRepository->findById($reservationId);

        if (! $reservation) {
            throw new ApiException('Réservation introuvable.', 404, 'RESERVATION_INTROUVABLE');
        }

        $estVoyageur = $reservation->id_voyageur === $user->getKey();
        $estHote = $reservation->id_hote === $user->getKey();
        $estAdmin = $user->estAdmin();

        if (! $estVoyageur && ! $estHote && ! $estAdmin) {
            throw new ApiException('Vous n\'avez pas le droit d\'annuler cette réservation.', 403, 'ACCES_REFUSE');
        }

        $acteur = $estVoyageur ? 'voyageur' : ($estHote ? 'hote' : 'systeme');

        // Calcul du remboursement selon la politique AVANT l'annulation
        $apercu = $this->remboursementService->calculerRemboursement($reservationId, $acteur);

        // Application du statut 'annulee' via la machine à états
        $this->statutService->transitionnerStatut($reservationId, 'annulee');

        $this->reservationRepository->update($reservationId, [
            'motif_annulation' => $motif,
            'acteur_annulation' => $acteur,
            'date_annulation' => now(),
        ]);

        $this->minuterieService->annulerMinuterie($reservationId);

        // Déclenchement du remboursement si un paiement réussi était enregistré
        if ($reservation->paiement && $reservation->paiement->statut === 'reussi') {
            $this->remboursementService->executerRemboursement($reservationId, $apercu, $acteur);
        }

        // Notification de l'autre partie
        $cible = $estVoyageur ? $reservation->hote : $reservation->voyageur;
        if ($cible) {
            $qui = $estVoyageur ? 'Le voyageur' : ($estHote ? "L'hôte" : "L'administration");
            $this->notificationService->notifier(
                $cible,
                TypeNotification::RESERVATION,
                'Réservation annulée',
                "{$qui} a annulé la réservation pour {$reservation->annonce?->titre}. Motif : {$motif}.",
                $reservation,
                ['reservation_id' => $reservation->id_reservation],
                true
            );
        }

        return $apercu;
    }

    /**
     * Commande planifiée : expire les demandes sans réponse sous 24h (RG15).
     */
    public function expirerDemandesEnAttente(): int
    {
        $demandes = $this->reservationRepository->getDemandesExpirees();
        $compteur = 0;

        foreach ($demandes as $demande) {
            $this->statutService->transitionnerStatut($demande->id_reservation, 'expiree');
            $this->minuterieService->annulerMinuterie($demande->id_reservation);

            if ($demande->voyageur) {
                $this->notificationService->notifier(
                    $demande->voyageur,
                    TypeNotification::RESERVATION,
                    'Demande de réservation expirée',
                    "L'hôte n'a pas répondu sous le délai de 24h pour {$demande->annonce?->titre}.",
                    $demande,
                    ['reservation_id' => $demande->id_reservation],
                    true
                );
            }

            $compteur++;
        }

        return $compteur;
    }

    /**
     * Mes voyages (voyageur).
     */
    public function getParVoyageur(string $voyageurId, int $perPage = 10, ?string $statut = null): LengthAwarePaginator
    {
        return $this->reservationRepository->getHistoriqueVoyageur($voyageurId, $perPage, $statut);
    }

    /**
     * Demandes reçues (hôte).
     */
    public function getDemandesHote(string $hoteId, int $perPage = 10, ?string $statut = null): LengthAwarePaginator
    {
        return $this->reservationRepository->getDemandesHote($hoteId, $perPage, $statut);
    }

    /**
     * Détail d'une réservation.
     */
    public function getReservation(string $reservationId): ?Reservation
    {
        return $this->reservationRepository->findById($reservationId);
    }
}
