<?php

namespace App\Services\Utilisateurs;

use App\Exceptions\ApiException;
use App\Models\Utilisateurs\User;
use App\Models\Utilisateurs\VerificationIdentite;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Vérification d'identité des hôtes (RG03).
 *
 * Cycle de vie (Figure 68) : en_cours → validee | rejetee.
 * Le document est stocké sur le disque privé `local`
 * (`storage/app/private/verifications/...`) : seul un administrateur
 * peut le télécharger (loi 09-08).
 */
class VerificationService
{
    public function __construct(protected NotificationService $notifications) {}
    /**
     * @param  array{type_piece:string}  $data
     */
    public function soumettre(User $utilisateur, array $data, UploadedFile $document): VerificationIdentite
    {
        if (! $utilisateur->estHote()) {
            throw new ApiException(
                'Seuls les hôtes peuvent déposer une pièce d\'identité.',
                403,
                'HOTE_REQUIS',
            );
        }

        if ($utilisateur->verifications()->validees()->exists()) {
            throw new ApiException(
                'Votre identité est déjà vérifiée.',
                409,
                'DEJA_VERIFIE',
            );
        }

        if ($utilisateur->verifications()->enCours()->exists()) {
            throw new ApiException(
                'Une demande de vérification est déjà en cours de traitement.',
                409,
                'VERIFICATION_EN_COURS',
            );
        }

        return DB::transaction(function () use ($utilisateur, $data, $document): VerificationIdentite {
            $chemin = $document->store('verifications', 'local');

            return $utilisateur->verifications()->create([
                'type_piece' => $data['type_piece'],
                'statut' => VerificationIdentite::STATUT_EN_COURS,
                'chemin_document' => $chemin,
            ]);
        });
    }

    /** Dernière demande de l'utilisateur (pour l'écran de statut). */
    public function derniere(User $utilisateur): ?VerificationIdentite
    {
        return $utilisateur->verifications()->latest('date_soumission')->first();
    }

    public function valider(string $verificationId, User $administrateur): VerificationIdentite
    {
        $idAdmin = $this->idAdmin($administrateur);

        $verification = VerificationIdentite::findOrFail($verificationId);

        if ($verification->estValidee()) {
            return $verification;
        }

        $verification->update([
            'statut' => VerificationIdentite::STATUT_VALIDEE,
            'motif_rejet' => null,
            'id_admin_traitant' => $idAdmin,
            'date_traitement' => now(),
        ]);

        $verification->refresh();

        // RG03 / RO06 : notifier l'hôte (in-app + email).
        $this->notifications->identiteTraitee($verification);

        return $verification;
    }

    public function rejeter(string $verificationId, string $motif, User $administrateur): VerificationIdentite
    {
        if (trim($motif) === '') {
            throw new ApiException('Le motif de rejet est obligatoire.', 422, 'MOTIF_REQUIS');
        }

        $idAdmin = $this->idAdmin($administrateur);

        $verification = VerificationIdentite::findOrFail($verificationId);

        $verification->update([
            'statut' => VerificationIdentite::STATUT_REJETEE,
            'motif_rejet' => $motif,
            'id_admin_traitant' => $idAdmin,
            'date_traitement' => now(),
        ]);

        $verification->refresh();

        // RG03 / RO06 : notifier l'hôte du rejet et de son motif.
        $this->notifications->identiteTraitee($verification);

        return $verification;
    }

    /** Téléchargement sécurisé du document (endpoint admin uniquement). */
    public function telecharger(VerificationIdentite $verification): StreamedResponse
    {
        $disk = Storage::disk('local');

        if ($verification->chemin_document === null || ! $disk->exists($verification->chemin_document)) {
            throw new ApiException('Document introuvable.', 404, 'DOCUMENT_INTROUVABLE');
        }

        return $disk->download($verification->chemin_document);
    }

    private function idAdmin(User $utilisateur): string
    {
        $idAdmin = $utilisateur->administrateur()->value('id_admin');

        if ($idAdmin === null) {
            throw new ApiException(
                'Accès réservé à l\'administration.',
                403,
                'ADMIN_REQUIS',
            );
        }

        return $idAdmin;
    }
}
