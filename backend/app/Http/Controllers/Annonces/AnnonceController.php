<?php

namespace App\Http\Controllers\Annonces;

use App\Http\Controllers\Controller;
use App\Http\Requests\Annonces\RechercheAnnoncesRequest;
use App\Http\Requests\Annonces\StoreAnnonceRequest;
use App\Http\Requests\Annonces\UpdateAnnonceRequest;
use App\Http\Resources\Annonces\AnnonceDetailResource;
use App\Http\Resources\Annonces\AnnonceResumeResource;
use App\Http\Responses\ApiResponse;
use App\Services\Annonces\AnnonceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestion du cycle de vie des annonces (RG06, RG07, RG10, RG11)
 */
class AnnonceController extends Controller
{
    public function __construct(protected AnnonceService $annonceService) {}

    public function index(RechercheAnnoncesRequest $request): JsonResponse
    {
        $parPage = max(1, min($request->integer('per_page', 12), 50));
        $paginator = $this->annonceService->rechercherAnnoncesPubliees($request->validated(), $parPage);

        return ApiResponse::success([
            'items' => AnnonceResumeResource::collection($paginator->getCollection())->resolve(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Liste des annonces récupérée.');
    }

    public function enVedette(): JsonResponse
    {
        return ApiResponse::success(
            AnnonceResumeResource::collection($this->annonceService->getAnnoncesEnVedette(6))->resolve(),
            'Annonces à la une.',
        );
    }

    public function show(string $id): JsonResponse
    {
        $annonce = $this->annonceService->getDetailsAnnonce($id);
        if (!$annonce) {
            return ApiResponse::error('Annonce non disponible ou inexistante.', 404, null, 'ANNONCE_INTROUVABLE');
        }

        return ApiResponse::success((new AnnonceDetailResource($annonce))->resolve(), 'Détail de l\'annonce.');
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
