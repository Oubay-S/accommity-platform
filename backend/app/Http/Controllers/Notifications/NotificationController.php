<?php

namespace App\Http\Controllers\Notifications;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Notifications\NotificationResource;
use App\Http\Responses\ApiResponse;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Boîte de notifications in-app de l'utilisateur connecté (RO06, RO07).
 */
class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    /** GET /api/notifications?non_lues=1&par_page=15 */
    public function index(Request $request): JsonResponse
    {
        $utilisateur = $request->user();
        $parPage = max(1, min($request->integer('par_page', 15), 50));

        $query = $utilisateur->notifications()
            ->orderByDesc('date_creation')
            // date_creation est à la seconde : l'UUID v7 sert de départage chronologique.
            ->orderByDesc('id_notification');

        if ($request->boolean('non_lues')) {
            $query->nonLues();
        }

        $paginator = $query->paginate($parPage);

        return ApiResponse::success([
            'items' => NotificationResource::collection($paginator->getCollection())->resolve(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            'non_lues' => $utilisateur->notifications()->nonLues()->count(),
        ], 'Notifications récupérées.');
    }

    /** GET /api/notifications/non-lues — badge de la cloche */
    public function compterNonLues(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'non_lues' => $request->user()->notifications()->nonLues()->count(),
        ], 'Nombre de notifications non lues.');
    }

    /** PUT /api/notifications/{id}/lue */
    public function marquerCommeLue(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->first();

        if ($notification === null) {
            throw new ApiException('Notification introuvable.', 404, 'INTROUVABLE');
        }

        $notification->marquerCommeLue();

        return ApiResponse::success(
            ['notification' => (new NotificationResource($notification->refresh()))->resolve()],
            'Notification marquée comme lue.',
        );
    }

    /** PUT /api/notifications/tout-lu */
    public function marquerToutesCommeLues(Request $request): JsonResponse
    {
        $marquees = $this->notificationService->marquerToutesCommeLues($request->user());

        return ApiResponse::success(
            ['marquees' => $marquees],
            'Toutes les notifications ont été marquées comme lues.',
        );
    }
}
