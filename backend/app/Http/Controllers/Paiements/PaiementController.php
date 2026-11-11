<?php

namespace App\Http\Controllers\Paiements;

use App\Http\Controllers\Controller;
use App\Http\Requests\Paiements\PaiementRequest;
use App\Services\Paiements\PaiementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Passerelle de paiement simulée et séquestre (RG20, RG21, RG22)
 */
class PaiementController extends Controller
{
    public function __construct(protected PaiementService $paiementService) {}

    public function payer(PaiementRequest $request, string $reservationId): JsonResponse
    {
        // TODO: Traitement paiement simulé par carte bancaire
        return response()->json(['message' => 'Paiement effectué avec succès']);
    }

    public function recu(string $paiementId): JsonResponse
    {
        return response()->json(['recu' => null]);
    }

    public function historique(Request $request): JsonResponse
    {
        return response()->json(['transactions' => []]);
    }
}
