<?php

namespace App\Http\Controllers\Annonces;

use App\Http\Controllers\Controller;
use App\Http\Requests\Annonces\AjouterPhotosRequest;
use App\Http\Requests\Annonces\RechercheAnnoncesRequest;
use App\Http\Requests\Annonces\StoreAnnonceRequest;
use App\Http\Requests\Annonces\UpdateAnnonceRequest;
use App\Http\Resources\Annonces\AnnonceDetailResource;
use App\Http\Resources\Annonces\AnnonceHoteResource;
use App\Http\Resources\Annonces\AnnonceResumeResource;
use App\Http\Responses\ApiResponse;
use App\Models\Annonces\Annonce;
use App\Services\Annonces\AnnonceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

    public function references(): JsonResponse
    {
        return ApiResponse::success(
            $this->annonceService->getReferences(),
            'Références récupérées.',
        );
    }

    public function show(string $id): JsonResponse
    {
        $annonce = $this->annonceService->getDetailsAnnonce($id);
        if (! $annonce) {
            return ApiResponse::error('Annonce non disponible ou inexistante.', 404, null, 'ANNONCE_INTROUVABLE');
        }

        return ApiResponse::success((new AnnonceDetailResource($annonce))->resolve(), 'Détail de l\'annonce.');
    }

    public function mesAnnonces(Request $request): JsonResponse
    {
        $parPage = max(1, min($request->integer('per_page', 10), 50));

        $criteres = $request->validate([
            'statut' => ['nullable', 'in:en_attente,publiee,suspendue,desactivee,rejetee'],
            'recherche' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $resultat = $this->annonceService->mesAnnonces(
            $request->user()->getKey(),
            $criteres,
            $parPage,
        );

        return ApiResponse::success([
            'items' => AnnonceHoteResource::collection(collect($resultat['items']))->resolve(),
            'pagination' => $resultat['pagination'],
            'statistiques' => $resultat['statistiques'],
        ], 'Mes annonces récupérées.');
    }

    public function showHote(Request $request, string $id): JsonResponse
    {
        $annonce = $this->annonceService->getAnnonceHote($id, $request->user()->getKey());

        if (! $annonce) {
            return ApiResponse::error('Annonce introuvable.', 404, null, 'ANNONCE_INTROUVABLE');
        }

        return ApiResponse::success(
            (new AnnonceHoteResource($annonce))->resolve(),
            'Annonce récupérée.',
        );
    }

    public function store(StoreAnnonceRequest $request): JsonResponse
    {
        Gate::authorize('create', Annonce::class);

        $annonce = $this->annonceService->creerAnnonce(
            $request->user()->getKey(),
            $request->validated(),
            $request->file('photos', []),
        );

        return ApiResponse::success(
            (new AnnonceHoteResource($annonce))->resolve(),
            'Annonce créée avec succès. Elle sera publiée après modération.',
            201,
        );
    }

    public function update(UpdateAnnonceRequest $request, string $id): JsonResponse
    {
        $annonce = $this->annonceService->getAnnoncePourGestion($id);

        if (! $annonce) {
            return ApiResponse::error('Annonce introuvable.', 404, null, 'ANNONCE_INTROUVABLE');
        }

        Gate::authorize('update', $annonce);

        $annonce = $this->annonceService->modifierAnnonce($id, $request->validated());

        return ApiResponse::success(
            (new AnnonceHoteResource($annonce))->resolve(),
            'Annonce mise à jour.',
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $annonce = $this->annonceService->getAnnoncePourGestion($id);

        if (! $annonce) {
            return ApiResponse::error('Annonce introuvable.', 404, null, 'ANNONCE_INTROUVABLE');
        }

        Gate::authorize('delete', $annonce);

        $this->annonceService->supprimerAnnonce($id);

        return ApiResponse::success(null, 'Annonce supprimée.');
    }

    public function ajouterPhotos(AjouterPhotosRequest $request, string $id): JsonResponse
    {
        $annonce = $this->annonceService->getAnnoncePourGestion($id);

        if (! $annonce) {
            return ApiResponse::error('Annonce introuvable.', 404, null, 'ANNONCE_INTROUVABLE');
        }

        Gate::authorize('update', $annonce);

        $annonce = $this->annonceService->ajouterPhotos($annonce, $request->file('photos', []));

        return ApiResponse::success(
            (new AnnonceHoteResource($annonce))->resolve(),
            'Photos ajoutées.',
        );
    }

    public function supprimerPhoto(Request $request, string $id): JsonResponse
    {
        $donnees = $request->validate([
            'url' => ['required', 'string'],
        ]);

        $annonce = $this->annonceService->getAnnoncePourGestion($id);

        if (! $annonce) {
            return ApiResponse::error('Annonce introuvable.', 404, null, 'ANNONCE_INTROUVABLE');
        }

        Gate::authorize('update', $annonce);

        $annonce = $this->annonceService->supprimerPhoto($annonce, $donnees['url']);

        return ApiResponse::success(
            (new AnnonceHoteResource($annonce))->resolve(),
            'Photo supprimée.',
        );
    }
}
