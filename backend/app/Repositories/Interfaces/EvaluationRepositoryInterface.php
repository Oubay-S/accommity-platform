<?php

namespace App\Repositories\Interfaces;

use App\Models\Evaluations\Evaluation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EvaluationRepositoryInterface
{
    public function findById(string $id): ?Evaluation;

    public function existePour(string $reservationId, string $auteurId): bool;

    /**
     * @param  array<string, mixed>  $data
     */
    public function creer(array $data): Evaluation;

    public function pourAnnonce(string $annonceId, ?int $note, int $parPage): LengthAwarePaginator;

    public function recuesPar(string $userId, int $parPage): LengthAwarePaginator;

    public function donneesPar(string $userId, int $parPage): LengthAwarePaginator;

    public function moyennePourCible(string $userId): float;

    public function supprimer(Evaluation $evaluation): void;
}
