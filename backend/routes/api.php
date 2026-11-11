<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Utilisateurs\UserController;
use App\Http\Controllers\Utilisateurs\ProfilController;
use App\Http\Controllers\Utilisateurs\VerificationIdentiteController;
use App\Http\Controllers\Annonces\AnnonceController;
use App\Http\Controllers\Annonces\CalendrierController;
use App\Http\Controllers\Reservations\ReservationController;
use App\Http\Controllers\Paiements\PaiementController;
use App\Http\Controllers\Paiements\RemboursementController;
use App\Http\Controllers\Evaluations\EvaluationController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminAnnonceController;
use App\Http\Controllers\Admin\AdminAvisController;
use App\Http\Controllers\Admin\AdminLitigeController;

/*
|--------------------------------------------------------------------------
| Accommity RESTful API Routes (pour SPA Angular)
|--------------------------------------------------------------------------
*/

// ── 1. Authentification Publique (RG01, RG02) ──
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// ── 2. Annonces Publiques & Recherche (RG10, RG11) ──
Route::get('/annonces', [AnnonceController::class, 'index']);
Route::get('/annonces/{id}', [AnnonceController::class, 'show']);
Route::get('/annonces/{id}/disponibilites', [CalendrierController::class, 'show']);
Route::get('/annonces/{id}/avis', [EvaluationController::class, 'annonceAvis']);

// ── 3. Routes Protégées (Utilisateurs Connectés) ──
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Profil & Vérification d'identité (RG03)
    Route::prefix('profil')->group(function () {
        Route::get('/', [ProfilController::class, 'show']);
        Route::put('/', [ProfilController::class, 'update']);
        Route::post('/photo', [ProfilController::class, 'uploadPhoto']);
        Route::post('/verification', [VerificationIdentiteController::class, 'submit']);
        Route::get('/verification/statut', [VerificationIdentiteController::class, 'status']);
    });

    // Réservations Voyageur (RG12, RG14, RG17)
    Route::prefix('reservations')->group(function () {
        Route::post('/', [ReservationController::class, 'store']);
        Route::get('/mes-voyages', [ReservationController::class, 'mesVoyages']);
        Route::get('/{id}', [ReservationController::class, 'show']);
        Route::post('/{id}/annuler', [ReservationController::class, 'annuler']);
        Route::get('/{id}/apercu-remboursement', [RemboursementController::class, 'apercu']);
    });

    // Paiements Sécurisés Simulés (RG20)
    Route::prefix('paiements')->group(function () {
        Route::post('/{reservationId}', [PaiementController::class, 'payer']);
        Route::get('/recu/{paiementId}', [PaiementController::class, 'recu']);
        Route::get('/historique', [PaiementController::class, 'historique']);
    });

    // Avis & Évaluations (RG26, RG27)
    Route::post('/evaluations', [EvaluationController::class, 'store']);
    Route::post('/evaluations/{id}/signaler', [EvaluationController::class, 'signaler']);

    // Notifications (in-app)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/lue', [NotificationController::class, 'marquerCommeLue']);

    // ── 4. Espace Hôte Vérifié (RG03, RG06, RG07, RG08, RG13) ──
    Route::middleware('role.hote')->prefix('hote')->group(function () {
        Route::get('/annonces', [AnnonceController::class, 'mesAnnonces']);
        Route::post('/annonces', [AnnonceController::class, 'store']);
        Route::put('/annonces/{id}', [AnnonceController::class, 'update']);
        Route::delete('/annonces/{id}', [AnnonceController::class, 'destroy']);
        Route::post('/annonces/{id}/bloquer', [CalendrierController::class, 'bloquer']);
        Route::post('/annonces/{id}/debloquer', [CalendrierController::class, 'debloquer']);

        // Demandes de réservations reçues
        Route::get('/demandes', [ReservationController::class, 'demandesHote']);
        Route::post('/reservations/{id}/repondre', [ReservationController::class, 'repondre']);
    });

    // ── 5. Espace Administration & Modération ──
    Route::middleware('role.admin')->prefix('admin')->group(function () {
        Route::get('/stats', [AdminDashboardController::class, 'stats']);

        // Gestion utilisateurs & identités
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::put('/users/{id}/toggle-actif', [AdminUserController::class, 'toggleActif']);
        Route::get('/verifications', [AdminUserController::class, 'verifications']);
        Route::put('/verifications/{id}/valider', [AdminUserController::class, 'validerVerification']);
        Route::put('/verifications/{id}/rejeter', [AdminUserController::class, 'rejeterVerification']);

        // Modération annonces
        Route::get('/annonces', [AdminAnnonceController::class, 'index']);
        Route::put('/annonces/{id}/approuver', [AdminAnnonceController::class, 'approuver']);
        Route::put('/annonces/{id}/suspendre', [AdminAnnonceController::class, 'suspendre']);

        // Modération avis
        Route::get('/avis/signales', [AdminAvisController::class, 'signales']);
        Route::delete('/avis/{id}', [AdminAvisController::class, 'supprimer']);

        // Gestion des litiges
        Route::get('/litiges', [AdminLitigeController::class, 'index']);
        Route::put('/litiges/{id}/resoudre', [AdminLitigeController::class, 'resoudre']);
    });
});
