<?php

namespace App\Services\Evaluations;

use App\Enums\StatutReservation;
use App\Exceptions\ApiException;
use App\Http\Resources\Evaluations\EvaluationResource;
use App\Models\Administration\TicketLitige;
use App\Models\Annonces\Annonce;
use App\Models\Evaluations\Evaluation;
use App\Models\Evaluations\NoteDetaillee;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\Profil;
use App\Models\Utilisateurs\User;
use App\Repositories\Interfaces\EvaluationRepositoryInterface;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

class EvaluationService
{
    private const CRITERES = [
        'proprete',
        'communication',
        'emplacement',
        'rapport_qualite_prix',
        'exactitude',
    ];

    public function __construct(
        protected EvaluationRepositoryInterface $evaluationRepository,
        protected NotificationService $notificationService,
    ) {}

    /**
     * @param  array{id_reservation:string,note:int,commentaire:string,notes_detaillees?:array<string,mixed>}  $data
     */
    public function creer(User $auteur, array $data): Evaluation
    {
        $reservation = Reservation::query()->find($data['id_reservation']);

        if ($reservation === null) {
            throw new ApiException('Réservation introuvable.', 404, 'RESERVATION_INTROUVABLE');
        }

        $auteurId = $auteur->getKey();
        $estVoyageur = $reservation->id_voyageur === $auteurId;
        $estHote = $reservation->id_hote === $auteurId;

        if (! $estVoyageur && ! $estHote) {
            throw new ApiException(
                'Vous ne participez pas à cette réservation.',
                403,
                'NON_PARTICIPANT',
            );
        }

        if ($reservation->statut !== StatutReservation::TERMINEE) {
            throw new ApiException(
                'Un avis ne peut être déposé qu\'après la fin du séjour.',
                422,
                'RESERVATION_NON_TERMINEE',
            );
        }

        if ($this->evaluationRepository->existePour($reservation->getKey(), $auteurId)) {
            throw new ApiException('Vous avez déjà déposé un avis pour ce séjour.', 409, 'AVIS_DEJA_DEPOSE');
        }

        $notes = $data['notes_detaillees'] ?? null;

        if ($estVoyageur && ! $this->notesValides($notes)) {
            throw new ApiException(
                'Les notes détaillées sont obligatoires pour un avis voyageur.',
                422,
                'NOTES_DETAILLEES_REQUISES',
            );
        }

        $cibleId = $estVoyageur ? $reservation->id_hote : $reservation->id_voyageur;

        try {
            $evaluation = DB::transaction(function () use (
                $reservation,
                $auteurId,
                $cibleId,
                $estVoyageur,
                $data,
                $notes
            ): Evaluation {
                $evaluation = $this->evaluationRepository->creer([
                    'id_reservation' => $reservation->getKey(),
                    'id_auteur' => $auteurId,
                    'id_cible' => $cibleId,
                    'id_annonce' => $reservation->id_annonce,
                    'type_auteur' => $estVoyageur ? 'voyageur' : 'hote',
                    'note' => $data['note'],
                    'commentaire' => $data['commentaire'],
                    'est_signale' => false,
                ]);

                if ($estVoyageur && is_array($notes)) {
                    NoteDetaillee::query()->create([
                        'id_evaluation' => $evaluation->getKey(),
                        'proprete' => $notes['proprete'],
                        'communication' => $notes['communication'],
                        'emplacement' => $notes['emplacement'],
                        'rapport_qualite_prix' => $notes['rapport_qualite_prix'],
                        'exactitude' => $notes['exactitude'],
                    ]);
                }

                $this->recalculerNoteMoyenne($cibleId);

                return $evaluation;
            });
        } catch (QueryException $exception) {
            if ($this->estViolationUnicite($exception)) {
                throw new ApiException('Vous avez déjà déposé un avis pour ce séjour.', 409, 'AVIS_DEJA_DEPOSE');
            }

            throw $exception;
        }

        $evaluation = $this->evaluationRepository->findById($evaluation->getKey()) ?? $evaluation;
        $cible = User::query()->find($cibleId);

        if ($cible !== null) {
            try {
                $this->notificationService->nouvelAvis($evaluation, $cible);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $evaluation;
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, pagination: array<string, int>, statistiques: array<string, mixed>}
     */
    public function listerPourAnnonce(string $annonceId, ?int $note, int $parPage): array
    {
        if (! Annonce::query()->whereKey($annonceId)->exists()) {
            throw new ApiException('Annonce introuvable.', 404, 'ANNONCE_INTROUVABLE');
        }

        $paginator = $this->evaluationRepository->pourAnnonce($annonceId, $note, $parPage);

        return [
            'items' => EvaluationResource::collection($paginator->items())->resolve(),
            'pagination' => $this->pagination($paginator),
            'statistiques' => $this->statistiquesAnnonce($annonceId),
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, pagination: array<string, int>, statistiques: array<string, mixed>}
     */
    public function listerRecues(User $utilisateur, int $parPage): array
    {
        $paginator = $this->evaluationRepository->recuesPar($utilisateur->getKey(), $parPage);

        return [
            'items' => EvaluationResource::collection($paginator->items())->resolve(),
            'pagination' => $this->pagination($paginator),
            'statistiques' => $this->statistiquesUtilisateur($utilisateur->getKey()),
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, pagination: array<string, int>}
     */
    public function listerDonnees(User $utilisateur, int $parPage): array
    {
        $paginator = $this->evaluationRepository->donneesPar($utilisateur->getKey(), $parPage);

        return [
            'items' => EvaluationResource::collection($paginator->items())->resolve(),
            'pagination' => $this->pagination($paginator),
        ];
    }

    /**
     * Séjours terminés pour lesquels l'utilisateur n'a pas encore déposé d'avis.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listerAEvaluer(User $utilisateur): array
    {
        $utilisateurId = $utilisateur->getKey();

        $reservations = Reservation::query()
            ->where('statut', StatutReservation::TERMINEE->value)
            ->where(function ($query) use ($utilisateurId): void {
                $query->where('id_voyageur', $utilisateurId)
                    ->orWhere('id_hote', $utilisateurId);
            })
            ->whereNotExists(function ($query) use ($utilisateurId): void {
                $query->select(DB::raw('1'))
                    ->from('evaluations')
                    ->whereColumn('evaluations.id_reservation', 'reservations.id_reservation')
                    ->where('evaluations.id_auteur', $utilisateurId);
            })
            ->orderByDesc('date_depart')
            ->limit(50)
            ->get();

        $annonces = Annonce::query()
            ->whereIn('id_annonce', $reservations->pluck('id_annonce')->unique()->values())
            ->get()
            ->keyBy('id_annonce');

        $ciblesIds = $reservations->map(function (Reservation $reservation) use ($utilisateurId): string {
            return $reservation->id_voyageur === $utilisateurId
                ? $reservation->id_hote
                : $reservation->id_voyageur;
        })->unique()->values();

        $cibles = User::query()->whereIn('id_utilisateur', $ciblesIds)->get()->keyBy('id_utilisateur');
        $profils = Profil::query()->whereIn('id_utilisateur', $ciblesIds)->get()->keyBy('id_utilisateur');

        return $reservations->map(function (Reservation $reservation) use (
            $utilisateurId,
            $annonces,
            $cibles,
            $profils
        ): array {
            $estVoyageur = $reservation->id_voyageur === $utilisateurId;
            $cibleId = $estVoyageur ? $reservation->id_hote : $reservation->id_voyageur;
            $annonce = $annonces->get($reservation->id_annonce);
            $cible = $cibles->get($cibleId);
            $profil = $profils->get($cibleId);

            return [
                'id_reservation' => $reservation->getKey(),
                'role' => $estVoyageur ? 'voyageur' : 'hote',
                'date_arrivee' => $reservation->date_arrivee->format('Y-m-d'),
                'date_depart' => $reservation->date_depart->format('Y-m-d'),
                'annonce' => $annonce === null ? null : [
                    'id_annonce' => $annonce->getKey(),
                    'titre' => $annonce->titre,
                    'photo_principale' => $annonce->photo_url[0] ?? null,
                ],
                'cible' => $cible === null ? null : [
                    'id_utilisateur' => $cible->getKey(),
                    'prenom' => $cible->prenom,
                    'nom' => $cible->nom,
                    'photo_url' => $profil?->photo_url,
                ],
            ];
        })->values()->all();
    }

    public function signaler(User $declarant, string $evaluationId, string $motif): TicketLitige
    {
        $evaluation = $this->evaluationRepository->findById($evaluationId);

        if ($evaluation === null) {
            throw new ApiException('Avis introuvable.', 404, 'AVIS_INTROUVABLE');
        }

        if ($evaluation->id_auteur === $declarant->getKey()) {
            throw new ApiException('Vous ne pouvez pas signaler votre propre avis.', 403, 'AUTO_SIGNALEMENT');
        }

        $dejaSignale = TicketLitige::query()
            ->where('id_evaluation', $evaluationId)
            ->where('id_declarant', $declarant->getKey())
            ->exists();

        if ($dejaSignale) {
            throw new ApiException('Vous avez déjà signalé cet avis.', 409, 'AVIS_DEJA_SIGNE');
        }

        return DB::transaction(function () use ($evaluation, $declarant, $motif): TicketLitige {
            $ticket = TicketLitige::query()->create([
                'id_evaluation' => $evaluation->getKey(),
                'id_declarant' => $declarant->getKey(),
                'id_reservation' => $evaluation->id_reservation,
                'motif' => $motif,
                'statut' => 'en_cours',
            ]);

            $evaluation->forceFill([
                'est_signale' => true,
                'motif_signalement' => $evaluation->motif_signalement ?? $motif,
            ])->save();

            return $ticket;
        });
    }

    private function recalculerNoteMoyenne(string $utilisateurId): void
    {
        $profil = Profil::query()->firstOrCreate(['id_utilisateur' => $utilisateurId]);
        $profil->update([
            'note_moyenne' => round($this->evaluationRepository->moyennePourCible($utilisateurId), 2),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $notes
     */
    private function notesValides(?array $notes): bool
    {
        if ($notes === null) {
            return false;
        }

        foreach (self::CRITERES as $critere) {
            if (! isset($notes[$critere]) || ! is_numeric($notes[$critere])) {
                return false;
            }

            $valeur = (float) $notes[$critere];

            if ($valeur < 1 || $valeur > 5) {
                return false;
            }
        }

        return true;
    }

    private function estViolationUnicite(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23505', '23000'], true)
            && str_contains($exception->getMessage(), 'evaluations');
    }

    /**
     * @return array<string, int>
     */
    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    /**
     * @return array{note_moyenne: float, nb_avis: int, repartition: array<int, int>}
     */
    private function statistiquesAnnonce(string $annonceId): array
    {
        $notes = Evaluation::query()
            ->where('id_annonce', $annonceId)
            ->where('type_auteur', 'voyageur')
            ->pluck('note')
            ->map(fn ($note): int => (int) $note);

        return $this->statistiquesDepuisNotes($notes->all());
    }

    /**
     * @return array{note_moyenne: float, nb_avis: int, repartition: array<int, int>}
     */
    private function statistiquesUtilisateur(string $utilisateurId): array
    {
        $notes = Evaluation::query()
            ->where('id_cible', $utilisateurId)
            ->pluck('note')
            ->map(fn ($note): int => (int) $note);

        return $this->statistiquesDepuisNotes($notes->all());
    }

    /**
     * @param  array<int, int>  $notes
     * @return array{note_moyenne: float, nb_avis: int, repartition: array<int, int>}
     */
    private function statistiquesDepuisNotes(array $notes): array
    {
        $nombre = count($notes);
        $repartition = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        foreach ($notes as $note) {
            if (isset($repartition[$note])) {
                $repartition[$note]++;
            }
        }

        return [
            'note_moyenne' => $nombre === 0 ? 0.0 : round(array_sum($notes) / $nombre, 2),
            'nb_avis' => $nombre,
            'repartition' => $repartition,
        ];
    }
}
