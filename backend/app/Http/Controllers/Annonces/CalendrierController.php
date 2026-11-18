<?php

namespace App\Http\Controllers\Annonces;

use App\Http\Controllers\Controller;
use App\Http\Requests\Annonces\BloquerDateRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Annonces\Annonce;
use App\Services\Annonces\AnnonceService;
use App\Services\Annonces\CalendrierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Gestion des calendriers et disponibilités (RG08, RG09)
 */
class CalendrierController extends Controller
{
    public function __construct(
        protected CalendrierService $calendrierService,
        protected AnnonceService $annonceService,
    ) {}

    public function show(string $annonceId): JsonResponse
    {
        $annonce = $this->annonceService->getDetailsAnnonce($annonceId);
        if (! $annonce) {
            return ApiResponse::error('Annonce non disponible ou inexistante.', 404, null, 'ANNONCE_INTROUVABLE');
        }

        return ApiResponse::success([
            'dates_indisponibles' => $this->annonceService->getDatesOccupees($annonce),
        ], 'Disponibilités récupérées.');
    }

    public function calendrierHote(Request $request, string $annonceId): JsonResponse
    {
        $annonce = $this->annonceHote($request, $annonceId);

        if (! $annonce) {
            return ApiResponse::error('Annonce introuvable.', 404, null, 'ANNONCE_INTROUVABLE');
        }

        Gate::authorize('update', $annonce);

        $donnees = $request->validate([
            'debut' => ['nullable', 'date_format:Y-m-d'],
            'fin' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return ApiResponse::success(
            $this->calendrierService->getCalendrierHote($annonce, $donnees['debut'] ?? null, $donnees['fin'] ?? null),
            'Calendrier récupéré.',
        );
    }

    public function bloquer(BloquerDateRequest $request, string $annonceId): JsonResponse
    {
        $annonce = $this->annonceHote($request, $annonceId);

        if (! $annonce) {
            return ApiResponse::error('Annonce introuvable.', 404, null, 'ANNONCE_INTROUVABLE');
        }

        Gate::authorize('update', $annonce);

        $blocage = $this->calendrierService->bloquerDates(
            $annonce,
            $request->validated('date_debut'),
            $request->validated('date_fin'),
        );

        return ApiResponse::success([
            'id_calendrier' => $blocage->getKey(),
            'date_debut' => $blocage->date_debut->format('Y-m-d'),
            'date_fin' => $blocage->date_fin->format('Y-m-d'),
        ], 'Dates bloquées.');
    }

    public function debloquer(BloquerDateRequest $request, string $annonceId): JsonResponse
    {
        $annonce = $this->annonceHote($request, $annonceId);

        if (! $annonce) {
            return ApiResponse::error('Annonce introuvable.', 404, null, 'ANNONCE_INTROUVABLE');
        }

        Gate::authorize('update', $annonce);

        $supprimes = $this->calendrierService->debloquerDates(
            $annonce,
            $request->validated('date_debut'),
            $request->validated('date_fin'),
        );

        return ApiResponse::success([
            'blocages_supprimes' => $supprimes,
        ], 'Dates débloquées.');
    }

    private function annonceHote(Request $request, string $annonceId): ?Annonce
    {
        return $this->annonceService->getAnnonceHote($annonceId, $request->user()->getKey());
    }
}
