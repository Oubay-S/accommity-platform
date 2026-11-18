<?php

namespace App\Repositories\Eloquent;

use App\Models\Evaluations\Evaluation;
use App\Repositories\Interfaces\EvaluationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EvaluationRepository implements EvaluationRepositoryInterface
{
    public function findById(string $id): ?Evaluation
    {
        return Evaluation::query()->with(['auteur.profil', 'notesDetaillees'])->find($id);
    }

    public function existePour(string $reservationId, string $auteurId): bool
    {
        return Evaluation::query()
            ->where('id_reservation', $reservationId)
            ->where('id_auteur', $auteurId)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function creer(array $data): Evaluation
    {
        return Evaluation::query()->create($data);
    }

    public function pourAnnonce(string $annonceId, ?int $note, int $parPage): LengthAwarePaginator
    {
        $query = Evaluation::query()
            ->where('id_annonce', $annonceId)
            ->where('type_auteur', 'voyageur')
            ->with(['auteur.profil', 'notesDetaillees'])
            ->orderByDesc('date_creation')
            ->orderByDesc('id_evaluation');

        if ($note !== null) {
            $query->whereBetween('note', [$note, $note + 0.99]);
        }

        return $query->paginate($parPage);
    }

    public function recuesPar(string $userId, int $parPage): LengthAwarePaginator
    {
        return Evaluation::query()
            ->where('id_cible', $userId)
            ->with(['auteur.profil', 'annonce', 'notesDetaillees'])
            ->orderByDesc('date_creation')
            ->orderByDesc('id_evaluation')
            ->paginate($parPage);
    }

    public function donneesPar(string $userId, int $parPage): LengthAwarePaginator
    {
        return Evaluation::query()
            ->where('id_auteur', $userId)
            ->with(['cible', 'annonce', 'notesDetaillees'])
            ->orderByDesc('date_creation')
            ->orderByDesc('id_evaluation')
            ->paginate($parPage);
    }

    public function moyennePourCible(string $userId): float
    {
        return (float) Evaluation::query()->where('id_cible', $userId)->avg('note');
    }

    public function supprimer(Evaluation $evaluation): void
    {
        $evaluation->delete();
    }
}
