<?php

namespace App\Services\Notifications;

use App\Enums\TypeNotification;
use App\Mail\NotificationEmail;
use App\Models\Evaluations\Evaluation;
use App\Models\Notifications\Notification;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use App\Models\Utilisateurs\VerificationIdentite;
use Illuminate\Support\Facades\Mail;

/**
 * Notifications in-app + emails (RO06, RO07).
 *
 * Contrat public utilisé par les autres packages :
 *   notifier()                  création générique
 *   reservationStatutChangee()  RO06
 *   identiteTraitee()           RG03
 *   nouvelAvis()                RG27
 *   rappel()                    RO07 (idempotent)
 */
class NotificationService
{
    /**
     * @param  array<string, mixed>  $data  métadonnées de deep-link Angular
     */
    public function notifier(
        User $destinataire,
        TypeNotification $type,
        string $titre,
        string $contenu,
        ?Reservation $reservation = null,
        array $data = [],
        bool $envoyerEmail = false,
    ): Notification {
        $notification = Notification::create([
            'id_utilisateur' => $destinataire->getKey(),
            'id_reservation' => $reservation?->getKey(),
            'titre' => $titre,
            'contenu' => $contenu,
            'type_alerte' => $type,
            'est_lue' => false,
            'data' => $data === [] ? null : $data,
        ]);

        if ($envoyerEmail) {
            $this->envoyerEmail($destinataire, $titre, $contenu);
        }

        return $notification;
    }

    public function envoyerEmail(User|string $destinataire, string $sujet, string $contenu): void
    {
        $email = $destinataire instanceof User ? $destinataire->email : $destinataire;

        if ($email === null || $email === '') {
            return;
        }

        try {
            Mail::to($email)->send(new NotificationEmail($sujet, $contenu));
        } catch (\Throwable $e) {
            // Un serveur SMTP indisponible ne doit jamais faire échouer l'action
            // métier : la notification in-app est déjà enregistrée.
            report($e);
        }
    }

    /** RO06 : notification à chaque changement de statut de réservation. */
    public function reservationStatutChangee(
        Reservation $reservation,
        User $destinataire,
        string $libelleStatut,
    ): Notification {
        return $this->notifier(
            $destinataire,
            TypeNotification::RESERVATION,
            'Mise à jour de votre réservation',
            "Votre réservation est désormais « {$libelleStatut} ».",
            $reservation,
            ['reservation_id' => $reservation->getKey()],
            true,
        );
    }

    /** RG03 : identité validée ou rejetée. */
    public function identiteTraitee(VerificationIdentite $verification): Notification
    {
        $validee = $verification->estValidee();

        return $this->notifier(
            $verification->utilisateur,
            TypeNotification::SYSTEME,
            $validee ? 'Identité vérifiée' : 'Vérification d\'identité rejetée',
            $validee
                ? 'Votre pièce d\'identité a été validée. Vous pouvez publier vos annonces.'
                : 'Votre pièce d\'identité a été rejetée. Motif : '.($verification->motif_rejet ?? 'non précisé').'.',
            null,
            ['verification_id' => $verification->getKey()],
            true,
        );
    }

    /** RG27 : nouvel avis reçu. */
    public function nouvelAvis(Evaluation $evaluation, User $destinataire): Notification
    {
        return $this->notifier(
            $destinataire,
            TypeNotification::AVIS,
            'Nouvel avis reçu',
            'Un nouvel avis a été déposé sur une de vos réservations.',
            null,
            [
                'evaluation_id' => $evaluation->getKey(),
                'annonce_id' => $evaluation->id_annonce,
            ],
            true,
        );
    }

    /**
     * RO07 : rappel check-in. Idempotent — un même rappel (destinataire +
     * réservation + titre) n'est jamais créé deux fois.
     */
    public function rappel(Reservation $reservation, User $destinataire, string $echeance): Notification
    {
        $titre = "Rappel : arrivée dans {$echeance}";

        $existante = Notification::where('id_utilisateur', $destinataire->getKey())
            ->where('id_reservation', $reservation->getKey())
            ->where('type_alerte', TypeNotification::RAPPEL->value)
            ->where('titre', $titre)
            ->first();

        if ($existante !== null) {
            return $existante;
        }

        return $this->notifier(
            $destinataire,
            TypeNotification::RAPPEL,
            $titre,
            "Votre séjour commence dans {$echeance}. Pensez à préparer votre arrivée.",
            $reservation,
            ['reservation_id' => $reservation->getKey()],
            true,
        );
    }

    public function marquerToutesCommeLues(User $utilisateur): int
    {
        return $utilisateur->notifications()
            ->where('est_lue', false)
            ->update(['est_lue' => true]);
    }
}
