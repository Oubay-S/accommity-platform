<?php

namespace App\Http\Controllers\Paiements;

use App\Http\Controllers\Controller;
use App\Services\Paiements\RemboursementService;
use Illuminate\Http\JsonResponse;

/**
 * Calcul et exécution des remboursements (RG18, RG19, RG23, RG24)
 */
class RemboursementController extends Controller
{
    public function __construct(protected RemboursementService $remboursementService) {}

    public function apercu(string $reservationId): JsonResponse
    {
        // TODO: Calcul du montant remboursable avant annulation
        return response()->json(['montant_remboursable' => 0]);
    }
}
