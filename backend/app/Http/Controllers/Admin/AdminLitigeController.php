<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Administration\LitigeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLitigeController extends Controller
{
    public function __construct(protected LitigeService $litigeService) {}

    /**
     * GET /api/admin/litiges
     * Liste paginée des tickets de litige.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'statut' => $request->query('statut', 'tous'),
            'recherche' => $request->query('recherche'),
        ];
        $perPage = (int) $request->query('par_page', 15);

        $litiges = $this->litigeService->getLitiges($filters, $perPage);

        return ApiResponse::success([
            'items' => $litiges->items(),
            'pagination' => [
                'current_page' => $litiges->currentPage(),
                'last_page' => $litiges->lastPage(),
                'per_page' => $litiges->perPage(),
                'total' => $litiges->total(),
            ],
        ], 'Liste des litiges récupérée.');
    }

    /**
     * PUT /api/admin/litiges/{id}/resoudre
     * Clôture un litige avec décision d'arbitrage.
     */
    public function resoudre(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'decision' => 'required|string|min:5|max:2000',
        ], [
            'decision.required' => 'La décision d\'arbitrage est obligatoire pour clôturer le litige.',
            'decision.min' => 'La décision doit comporter au moins 5 caractères.',
        ]);

        try {
            $adminUserId = $request->user()->id_utilisateur;
            $litige = $this->litigeService->resoudreTicket($id, $validated['decision'], $adminUserId);

            return ApiResponse::success($litige, 'Le litige a été résolu et clôturé avec succès.');
        } catch (\Exception $e) {
            return ApiResponse::error('Litige introuvable ou erreur de traitement.', 404);
        }
    }
}
