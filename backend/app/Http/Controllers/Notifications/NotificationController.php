<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['notifications' => []]);
    }

    public function marquerCommeLue(string $id): JsonResponse
    {
        return response()->json(['message' => 'Notification lue']);
    }
}
