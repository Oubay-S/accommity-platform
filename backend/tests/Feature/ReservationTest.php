<?php

namespace Tests\Feature;

use App\Enums\ModeReservation;
use App\Enums\StatutAnnonce;
use App\Enums\StatutReservation;
use App\Models\Annonces\Annonce;
use App\Models\Reservations\Minuterie;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests fonctionnels du moteur de réservations (RG12, RG13, RG14, RG15, RG17).
 */
class ReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_reservation_instantanee_confirmee_directement(): void
    {
        $hote = User::factory()->hote()->create();
        $voyageur = User::factory()->voyageur()->create();

        $annonce = Annonce::factory()->create([
            'id_hote' => $hote->getKey(),
            'mode_reservation' => ModeReservation::INSTANTANEE,
            'statut' => StatutAnnonce::PUBLIEE,
            'tarif_nuit' => 1000,
            'capacite' => 4,
        ]);

        $arrivee = now()->addDays(5)->format('Y-m-d');
        $depart = now()->addDays(8)->format('Y-m-d'); // 3 nuits = 3000 + 12% = 3360

        $response = $this->actingAs($voyageur, 'sanctum')
            ->postJson('/api/reservations', [
                'id_annonce' => $annonce->id_annonce,
                'date_arrivee' => $arrivee,
                'date_depart' => $depart,
                'nb_voyageurs' => 2,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.statut', 'confirmee')
            ->assertJsonPath('data.montant_total', 3360)
            ->assertJsonPath('data.frais_service', 360);

        $this->assertDatabaseHas('reservations', [
            'id_annonce' => $annonce->id_annonce,
            'id_voyageur' => $voyageur->getKey(),
            'statut' => 'confirmee',
        ]);
    }

    public function test_creation_reservation_sur_demande_en_attente_avec_minuterie(): void
    {
        $hote = User::factory()->hote()->create();
        $voyageur = User::factory()->voyageur()->create();

        $annonce = Annonce::factory()->create([
            'id_hote' => $hote->getKey(),
            'mode_reservation' => ModeReservation::SUR_DEMANDE,
            'statut' => StatutAnnonce::PUBLIEE,
            'tarif_nuit' => 500,
            'capacite' => 2,
        ]);

        $arrivee = now()->addDays(10)->format('Y-m-d');
        $depart = now()->addDays(12)->format('Y-m-d');

        $response = $this->actingAs($voyageur, 'sanctum')
            ->postJson('/api/reservations', [
                'id_annonce' => $annonce->id_annonce,
                'date_arrivee' => $arrivee,
                'date_depart' => $depart,
                'nb_voyageurs' => 1,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.statut', 'en_attente');

        $reservationId = $response->json('data.id_reservation');

        $this->assertDatabaseHas('minuteries', [
            'id_reservation' => $reservationId,
            'type_minuterie' => 'expiration_demande',
            'est_active' => true,
        ]);
    }

    public function test_anti_chevauchement_dates_rejete_avec_409(): void
    {
        $hote = User::factory()->hote()->create();
        $voyageur1 = User::factory()->voyageur()->create();
        $voyageur2 = User::factory()->voyageur()->create();

        $annonce = Annonce::factory()->create([
            'id_hote' => $hote->getKey(),
            'mode_reservation' => ModeReservation::INSTANTANEE,
            'statut' => StatutAnnonce::PUBLIEE,
        ]);

        // Première réservation confirmée du 10 au 15
        Reservation::factory()->create([
            'id_annonce' => $annonce->id_annonce,
            'id_voyageur' => $voyageur1->getKey(),
            'id_hote' => $hote->getKey(),
            'date_arrivee' => now()->addDays(10)->toDateString(),
            'date_depart' => now()->addDays(15)->toDateString(),
            'statut' => StatutReservation::CONFIRMEE,
        ]);

        // Deuxième réservation chevauchant du 12 au 18
        $response = $this->actingAs($voyageur2, 'sanctum')
            ->postJson('/api/reservations', [
                'id_annonce' => $annonce->id_annonce,
                'date_arrivee' => now()->addDays(12)->toDateString(),
                'date_depart' => now()->addDays(18)->toDateString(),
                'nb_voyageurs' => 2,
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'DATES_INDISPONIBLES');
    }

    public function test_interdiction_auto_reservation(): void
    {
        $hote = User::factory()->hote()->create();

        $annonce = Annonce::factory()->create([
            'id_hote' => $hote->getKey(),
            'statut' => StatutAnnonce::PUBLIEE,
        ]);

        $response = $this->actingAs($hote, 'sanctum')
            ->postJson('/api/reservations', [
                'id_annonce' => $annonce->id_annonce,
                'date_arrivee' => now()->addDays(1)->toDateString(),
                'date_depart' => now()->addDays(3)->toDateString(),
                'nb_voyageurs' => 1,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('error_code', 'AUTO_RESERVATION');
    }

    public function test_hote_accepte_demande_avec_succes(): void
    {
        $hote = User::factory()->hoteVerifie()->create();
        $voyageur = User::factory()->voyageur()->create();

        $reservation = Reservation::factory()->enAttente()->create([
            'id_hote' => $hote->getKey(),
            'id_voyageur' => $voyageur->getKey(),
            'mode_reservation' => ModeReservation::SUR_DEMANDE,
        ]);

        Minuterie::create([
            'id_minuterie' => Str::uuid()->toString(),
            'id_reservation' => $reservation->id_reservation,
            'type_minuterie' => 'expiration_demande',
            'date_echeance' => now()->addDay(),
            'est_active' => true,
        ]);

        $response = $this->actingAs($hote, 'sanctum')
            ->postJson("/api/hote/reservations/{$reservation->id_reservation}/repondre", [
                'decision' => 'accepter',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.statut', 'confirmee');

        $this->assertDatabaseHas('reservations', [
            'id_reservation' => $reservation->id_reservation,
            'statut' => 'confirmee',
        ]);

        $this->assertDatabaseHas('minuteries', [
            'id_reservation' => $reservation->id_reservation,
            'est_active' => false,
        ]);
    }

    public function test_hote_refuse_demande_avec_motif_obligatoire(): void
    {
        $hote = User::factory()->hoteVerifie()->create();
        $voyageur = User::factory()->voyageur()->create();

        $reservation = Reservation::factory()->enAttente()->create([
            'id_hote' => $hote->getKey(),
            'id_voyageur' => $voyageur->getKey(),
        ]);

        // Refus sans motif -> échec 422
        $this->actingAs($hote, 'sanctum')
            ->postJson("/api/hote/reservations/{$reservation->id_reservation}/repondre", [
                'decision' => 'refuser',
            ])
            ->assertStatus(422);

        // Refus avec motif -> succès
        $this->actingAs($hote, 'sanctum')
            ->postJson("/api/hote/reservations/{$reservation->id_reservation}/repondre", [
                'decision' => 'refuser',
                'motif' => 'Indisponibilité imprévue pour travaux.',
            ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'refusee');

        $this->assertDatabaseHas('reservations', [
            'id_reservation' => $reservation->id_reservation,
            'statut' => 'refusee',
            'motif_refus' => 'Indisponibilité imprévue pour travaux.',
        ]);
    }

    public function test_annulation_reservation_voyageur(): void
    {
        $hote = User::factory()->hote()->create();
        $voyageur = User::factory()->voyageur()->create();

        $reservation = Reservation::factory()->create([
            'id_hote' => $hote->getKey(),
            'id_voyageur' => $voyageur->getKey(),
            'statut' => StatutReservation::CONFIRMEE,
            'date_arrivee' => now()->addDays(10)->toDateString(),
            'date_depart' => now()->addDays(14)->toDateString(),
        ]);

        $response = $this->actingAs($voyageur, 'sanctum')
            ->postJson("/api/reservations/{$reservation->id_reservation}/annuler", [
                'motif' => 'Imprévu de calendrier personnel.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('reservations', [
            'id_reservation' => $reservation->id_reservation,
            'statut' => 'annulee',
            'acteur_annulation' => 'voyageur',
            'motif_annulation' => 'Imprévu de calendrier personnel.',
        ]);
    }

    public function test_commande_expiration_demandes_apres_24h(): void
    {
        $reservation = Reservation::factory()->enAttente()->create();

        // Minuterie expirée dans le passé
        Minuterie::create([
            'id_minuterie' => Str::uuid()->toString(),
            'id_reservation' => $reservation->id_reservation,
            'type_minuterie' => 'expiration_demande',
            'date_echeance' => now()->subHour(),
            'est_active' => true,
        ]);

        $this->artisan('reservations:expire')
            ->expectsOutput('1 demande(s) de réservation expirée(s) traitée(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('reservations', [
            'id_reservation' => $reservation->id_reservation,
            'statut' => 'expiree',
        ]);
    }
}
