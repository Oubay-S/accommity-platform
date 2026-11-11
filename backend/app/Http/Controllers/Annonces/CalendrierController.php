<?php

namespace App\Http\Controllers\Annonces;

use App\Http\Controllers\Controller;
use App\Http\Requests\Annonces\BloquerDateRequest;
use App\Services\Annonces\CalendrierService;
use Illuminate\Http\JsonResponse;

/**
 * Gestion des calendriers et disponibilités (RG08, RG09)
 */
class CalendrierController extends Controller
{
    public function __construct(protected CalendrierService $calendrierService) {}

    public function show(string $annonceId): JsonResponse
    {
        return response()->json(['disponibilites' => []]);
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
