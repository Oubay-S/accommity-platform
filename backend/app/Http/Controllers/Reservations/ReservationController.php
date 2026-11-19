<?php

namespace App\Http\Controllers\Reservations;

use App\Enums\ModeReservation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\AnnulerReservationRequest;
use App\Http\Requests\Reservations\RepondreReservationRequest;
use App\Http\Requests\Reservations\StoreReservationRequest;
use App\Http\Resources\Reservations\ReservationResource;
use App\Services\Reservations\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestion du workflow de réservation (RG12, RG13, RG14, RG15, RG17).
 */
class ReservationController extends Controller
{
    public function __construct(protected ReservationService $reservationService) {}

    /**
     * Soumettre une demande de réservation ou effectuer une réservation instantanée.
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = $this->reservationService->creerReservation(
            $request->validated(),
            $request->user()
        );

        $isInstant = $reservation->mode_reservation === ModeReservation::INSTANTANEE;

        return response()->json([
            'success' => true,
            'message' => $isInstant
                ? 'Réservation confirmée avec succès.'
                : 'Demande de réservation enregistrée. L\'hôte a 24h pour répondre.',
            'data' => new ReservationResource($reservation),
        ], 201);
    }

    /**
     * Consulter le détail d'une réservation.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $reservation = $this->reservationService->getReservation($id);

        if (! $reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Réservation introuvable.',
                'error_code' => 'RESERVATION_INTROUVABLE',
            ], 404);
        }

        $user = $request->user();
        $estVoyageur = $reservation->id_voyageur === $user->getKey();
        $estHote = $reservation->id_hote === $user->getKey();
        $estAdmin = method_exists($user, 'estAdmin') ? $user->estAdmin() : false;

        if (! $estVoyageur && ! $estHote && ! $estAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à cette réservation.',
                'error_code' => 'ACCES_REFUSE',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new ReservationResource($reservation),
        ]);
    }

    /**
     * Liste des voyages réservés par l'utilisateur connecté (voyageur).
     */
    public function mesVoyages(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));
        $statut = $request->query('statut');

        $paginated = $this->reservationService->getParVoyageur(
            $request->user()->getKey(),
            $perPage,
            $statut
        );

        return response()->json([
            'success' => true,
            'message' => 'Liste des voyages récupérée.',
            'data' => [
                'items' => ReservationResource::collection($paginated->items()),
                'pagination' => [
                    'total' => $paginated->total(),
                    'per_page' => $paginated->perPage(),
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Liste des demandes de réservation reçues par l'hôte connecté.
     */
    public function demandesHote(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));
        $statut = $request->query('statut');

        $paginated = $this->reservationService->getDemandesHote(
            $request->user()->getKey(),
            $perPage,
            $statut
        );

        return response()->json([
            'success' => true,
            'message' => 'Demandes de réservations récupérées.',
            'data' => [
                'items' => ReservationResource::collection($paginated->items()),
                'pagination' => [
                    'total' => $paginated->total(),
                    'per_page' => $paginated->perPage(),
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Réponse de l'hôte à une demande en attente (accepter / refuser sous 24h).
     */
    public function repondre(RepondreReservationRequest $request, string $id): JsonResponse
    {
        $acceptee = $request->isAcceptee();
        $motif = $request->getMotif();

        $reservation = $this->reservationService->repondreDemande(
            $id,
            $request->user(),
            $acceptee,
            $motif
        );

        return response()->json([
            'success' => true,
            'message' => $acceptee
                ? 'Demande de réservation acceptée avec succès.'
                : 'Demande de réservation refusée.',
            'data' => new ReservationResource($reservation),
        ]);
    }

    /**
     * Annulation d'une réservation avec calcul du barème de remboursement.
     */
    public function annuler(AnnulerReservationRequest $request, string $id): JsonResponse
    {
        $apercu = $this->reservationService->annulerReservation(
            $id,
            $request->user(),
            $request->validated('motif')
        );

        return response()->json([
            'success' => true,
            'message' => 'Réservation annulée avec succès.',
            'data' => $apercu,
        ]);
    }
}
