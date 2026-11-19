<?php

namespace App\Http\Controllers\Paiements;

use App\Http\Controllers\Controller;
use App\Models\Reservations\Reservation;
use App\Services\Paiements\RemboursementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Calcul et exécution des remboursements (RG18, RG19, RG23, RG24).
 */
class RemboursementController extends Controller
{
    public function __construct(protected RemboursementService $remboursementService) {}

    /**
     * Calcul en temps réel du montant remboursable avant confirmation d'annulation (CdC 4.8).
     */
    public function apercu(Request $request, string $id): JsonResponse
    {
        $reservation = Reservation::findOrFail($id);

        $user = $request->user();
        $acteur = ($reservation->id_voyageur === $user->getKey()) ? 'voyageur' : 'hote';

        $apercu = $this->remboursementService->calculerRemboursement($id, $acteur);

        return response()->json([
            'success' => true,
            'message' => 'Aperçu du remboursement calculé avec succès.',
            'data'    => $apercu,
        ]);
    }
}
