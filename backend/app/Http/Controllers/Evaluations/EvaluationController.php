<?php

namespace App\Http\Controllers\Evaluations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluations\SignalerEvaluationRequest;
use App\Http\Requests\Evaluations\StoreEvaluationRequest;
use App\Http\Resources\Evaluations\EvaluationResource;
use App\Http\Responses\ApiResponse;
use App\Services\Evaluations\EvaluationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Évaluations bilatérales et avis (RG27, RG28).
 */
class EvaluationController extends Controller
{
    public function __construct(protected EvaluationService $evaluationService) {}

    /** POST /api/evaluations */
    public function store(StoreEvaluationRequest $request): JsonResponse
    {
        $evaluation = $this->evaluationService->creer($request->user(), $request->validated());

        return ApiResponse::success(
            ['evaluation' => (new EvaluationResource($evaluation))->resolve()],
            'Avis publié.',
            201,
        );
    }

    /** GET /api/annonces/{id}/avis?note=&page=&par_page= */
    public function annonceAvis(Request $request, string $annonceId): JsonResponse
    {
        $parPage = max(1, min($request->integer('par_page', 10), 50));
        $note = $request->filled('note') ? max(1, min($request->integer('note'), 5)) : null;

        return ApiResponse::success(
            $this->evaluationService->listerPourAnnonce($annonceId, $note, $parPage),
            'Avis récupérés.',
        );
    }

    /** GET /api/evaluations/a-evaluer */
    public function aEvaluer(Request $request): JsonResponse
    {
        return ApiResponse::success(
            ['items' => $this->evaluationService->listerAEvaluer($request->user())],
            'Séjours à évaluer récupérés.',
        );
    }

    /** GET /api/evaluations/recues */
    public function recues(Request $request): JsonResponse
    {
        $parPage = max(1, min($request->integer('par_page', 10), 50));

        return ApiResponse::success(
            $this->evaluationService->listerRecues($request->user(), $parPage),
            'Avis reçus récupérés.',
        );
    }

    /** GET /api/evaluations/donnees */
    public function donnees(Request $request): JsonResponse
    {
        $parPage = max(1, min($request->integer('par_page', 10), 50));

        return ApiResponse::success(
            $this->evaluationService->listerDonnees($request->user(), $parPage),
            'Avis déposés récupérés.',
        );
    }

    /** POST /api/evaluations/{id}/signaler */
    public function signaler(SignalerEvaluationRequest $request, string $avisId): JsonResponse
    {
        $ticket = $this->evaluationService->signaler(
            $request->user(),
            $avisId,
            $request->string('motif')->toString(),
        );

        return ApiResponse::success(
            [
                'ticket' => [
                    'id_ticket' => $ticket->getKey(),
                    'statut' => $ticket->statut,
                    'date_creation' => Carbon::parse($ticket->date_creation)->toIso8601String(),
                ],
            ],
            'Avis signalé. Un ticket de litige a été ouvert.',
            201,
        );
    }
}
