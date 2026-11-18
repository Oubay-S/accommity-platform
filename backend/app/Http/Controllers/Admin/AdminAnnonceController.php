<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Administration\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAnnonceController extends Controller
{
    public function __construct(protected AdminService $adminService) {}

    /**
     * GET /api/admin/annonces
     * Liste paginée des annonces pour la modération.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'statut' => $request->query('statut', 'tous'),
            'recherche' => $request->query('recherche'),
        ];
        $perPage = (int) $request->query('par_page', 15);

        $annonces = $this->adminService->getAnnonces($filters, $perPage);

        return ApiResponse::success([
            'items' => $annonces->items(),
            'pagination' => [
                'current_page' => $annonces->currentPage(),
                'last_page' => $annonces->lastPage(),
                'per_page' => $annonces->perPage(),
                'total' => $annonces->total(),
            ],
        ], 'Liste des annonces récupérée.');
    }

    /**
     * PUT /api/admin/annonces/{id}/approuver
     * Valide et publie une annonce en attente.
     */
    public function approuver(string $id): JsonResponse
    {
        try {
            $annonce = $this->adminService->approuverAnnonce($id);
            return ApiResponse::success($annonce, 'Annonce approuvée et publiée avec succès.');
        } catch (\Exception $e) {
            return ApiResponse::error('Annonce introuvable ou erreur de validation.', 404);
        }
    }

    /**
     * PUT /api/admin/annonces/{id}/suspendre
     * Suspend ou rejette une annonce avec motif.
     */
    public function suspendre(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'motif' => 'required|string|min:5|max:1000',
            'statut' => 'nullable|string|in:suspendue,rejetee',
        ], [
            'motif.required' => 'Le motif de modération est obligatoire.',
            'motif.min' => 'Le motif doit comporter au moins 5 caractères.',
        ]);

        try {
            $nouveauStatut = $validated['statut'] ?? 'suspendue';
            $annonce = $this->adminService->suspendreAnnonce($id, $validated['motif'], $nouveauStatut);

            return ApiResponse::success($annonce, "L'annonce a été mise à jour avec le statut {$nouveauStatut}.");
        } catch (\Exception $e) {
            return ApiResponse::error('Annonce introuvable ou erreur lors de la suspension.', 404);
        }
    }
}
