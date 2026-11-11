<?php

namespace App\Http\Controllers\Reservations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\StoreReservationRequest;
use App\Http\Requests\Reservations\RepondreReservationRequest;
use App\Http\Requests\Reservations\AnnulerReservationRequest;
use App\Services\Reservations\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestion du workflow de réservation (RG12, RG13, RG14, RG15, RG17)
 */
class ReservationController extends Controller
{
    public function __construct(protected ReservationService $reservationService) {}

    public function store(StoreReservationRequest $request): JsonResponse
    {
        // TODO: Créer réservation (instantanée ou sur demande)
        return response()->json(['message' => 'Demande de réservation enregistrée'], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['reservation' => null]);
    }

    public function mesVoyages(Request $request): JsonResponse
    {
        return response()->json(['reservations' => []]);
    }

    public function demandesHote(Request $request): JsonResponse
    {
        return response()->json(['demandes' => []]);
    }

    public function repondre(RepondreReservationRequest $request, string $id): JsonResponse
    {
        // TODO: Acceptation ou refus par l'hôte sous 24h
        return response()->json(['message' => 'Réponse prise en compte']);
    }

    public function annuler(AnnulerReservationRequest $request, string $id): JsonResponse
    {
        // TODO: Annulation et calcul du remboursement selon la politique
        return response()->json(['message' => 'Réservation annulée']);
    }
}
