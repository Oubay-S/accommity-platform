<?php

namespace App\Http\Controllers\Evaluations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluations\StoreEvaluationRequest;
use App\Services\Evaluations\EvaluationService;
use Illuminate\Http\JsonResponse;

/**
 * Évaluations bilatérales et avis (RG26, RG27, RG28, RG29)
 */
class EvaluationController extends Controller
{
    public function __construct(protected EvaluationService $evaluationService) {}

    public function store(StoreEvaluationRequest $request): JsonResponse
    {
        return response()->json(['message' => 'Avis enregistré']);
    }

    public function annonceAvis(string $annonceId): JsonResponse
    {
        return response()->json(['avis' => []]);
    }

    public function signaler(string $avisId): JsonResponse
    {
        return response()->json(['message' => 'Avis signalé']);
    }
}
