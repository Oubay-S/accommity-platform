<?php

namespace App\Http\Controllers\Annonces;

use App\Http\Controllers\Controller;
use App\Http\Requests\Annonces\BloquerDateRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Annonces\AnnonceService;
use App\Services\Annonces\CalendrierService;
use Illuminate\Http\JsonResponse;

/**
 * Gestion des calendriers et disponibilités (RG08, RG09)
 */
class CalendrierController extends Controller
{
    public function __construct(protected CalendrierService $calendrierService) {}

    public function show(string $annonceId, AnnonceService $annonceService): JsonResponse
    {
        $annonce = $annonceService->getDetailsAnnonce($annonceId);
        if (!$annonce) {
            return ApiResponse::error('Annonce non disponible ou inexistante.', 404, null, 'ANNONCE_INTROUVABLE');
        }

        return ApiResponse::success([
            'dates_indisponibles' => $annonceService->getDatesOccupees($annonce),
        ], 'Disponibilités récupérées.');
    }

    public function bloquer(BloquerDateRequest $request, string $annonceId): JsonResponse
    {
        return response()->json(['message' => 'Dates bloquées']);
    }

    public function debloquer(BloquerDateRequest $request, string $annonceId): JsonResponse
    {
        return response()->json(['message' => 'Dates débloquées']);
    }
}
