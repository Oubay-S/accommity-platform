<?php

namespace App\Http\Controllers\Annonces;

use App\Http\Controllers\Controller;
use App\Http\Requests\Annonces\StoreAnnonceRequest;
use App\Http\Requests\Annonces\UpdateAnnonceRequest;
use App\Services\Annonces\AnnonceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestion du cycle de vie des annonces (RG06, RG07, RG10, RG11)
 */
class AnnonceController extends Controller
{
    public function __construct(protected AnnonceService $annonceService) {}

    public function index(Request $request): JsonResponse
    {
        // TODO: Recherche et filtrage multicritères (destination, dates, voyageurs, prix)
        return response()->json(['annonces' => []]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['annonce' => null]);
    }

    public function store(StoreAnnonceRequest $request): JsonResponse
    {
        // TODO: Création d'une annonce par un hôte vérifié
        return response()->json(['message' => 'Annonce créée avec succès'], 201);
    }

    public function update(UpdateAnnonceRequest $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Annonce mise à jour']);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Annonce supprimée']);
    }

    public function mesAnnonces(Request $request): JsonResponse
    {
        return response()->json(['annonces' => []]);
    }
}
