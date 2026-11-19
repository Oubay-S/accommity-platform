<?php

namespace App\Http\Controllers\Paiements;

use App\Http\Controllers\Controller;
use App\Http\Requests\Paiements\PaiementRequest;
use App\Http\Resources\Paiements\PaiementResource;
use App\Http\Resources\Reservations\ReservationResource;
use App\Services\Paiements\PaiementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Passerelle de paiement simulée et séquestre (RG20, RG21, RG22)
 */
class PaiementController extends Controller
{
    public function __construct(protected PaiementService $paiementService) {}

    /**
     * Effectue le règlement sécurisé par carte bancaire (RG20).
     */
    public function payer(PaiementRequest $request, string $reservationId): JsonResponse
    {
        $result = $this->paiementService->traiterPaiementSimule(
            $reservationId,
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Paiement effectué avec succès.',
            'data'    => [
                'id_paiement'    => $result['paiement']->id_paiement,
                'statut'         => $result['paiement']->statut,
                'numero_facture' => $result['recu']->numero_facture,
                'id_recu'        => $result['recu']->id_recu,
            ],
        ], 201);
    }

    /**
     * Consultation du reçu officiel avec détails comptables et réservation (RG22).
     */
    public function recu(Request $request, string $paiementId): JsonResponse
    {
        $paiement = $this->paiementService->getRecuDetail($paiementId, $request->user());
        $recu = $paiement->recu;
        $reservation = $paiement->reservation;
        $voyageur = $paiement->voyageur ?? $reservation?->voyageur;
        $annonce = $reservation?->annonce;

        $montantTotal = (float) ($reservation?->montant_total ?? $paiement->montant);
        $fraisService = (float) ($reservation?->frais_service ?? 0);
        $montantHote  = round($montantTotal - $fraisService, 2);

        $typeLogement = $annonce?->type_logement;
        if ($typeLogement instanceof \BackedEnum) {
            $typeLogement = $typeLogement->value;
        }

        $data = [
            'id_recu'         => $recu?->id_recu ?? '',
            'id_paiement'     => $paiement->id_paiement,
            'numero_facture'  => $recu?->numero_facture ?? '',
            'chemin_pdf'      => $recu?->chemin_pdf,
            'date_generation' => $recu?->date_generation ? Carbon::parse($recu->date_generation)->toIso8601String() : null,
            'paiement'        => new PaiementResource($paiement),
            'reservation'     => $reservation ? new ReservationResource($reservation) : null,
            'montant_total'   => $montantTotal,
            'frais_service'   => $fraisService,
            'montant_hote'    => $montantHote,
            'voyageur'        => [
                'id_utilisateur' => $voyageur?->id_utilisateur ?? '',
                'nom'            => $voyageur?->nom ?? '',
                'prenom'         => $voyageur?->prenom ?? '',
                'email'          => $voyageur?->email ?? '',
            ],
            'annonce'         => [
                'id_annonce'     => $annonce?->id_annonce ?? '',
                'titre'          => $annonce?->titre ?? '',
                'adresse'        => $annonce?->adresse ?? '',
                'ville'          => $annonce?->ville ?? '',
                'type_logement'  => (string) $typeLogement,
            ],
        ];

        return response()->json([
            'success' => true,
            'message' => 'Détails du reçu récupérés.',
            'data'    => $data,
        ]);
    }

    /**
     * Historique paginé des paiements du voyageur connecté.
     */
    public function historique(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));
        $paginated = $this->paiementService->getHistorique($request->user(), $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Historique des transactions récupéré.',
            'data'    => [
                'items'      => PaiementResource::collection($paginated->items()),
                'pagination' => [
                    'total'        => $paginated->total(),
                    'per_page'     => $paginated->perPage(),
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                ],
            ],
        ]);
    }
}
