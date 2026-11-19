<?php

namespace Tests\Feature;

use App\Enums\ModeReservation;
use App\Enums\StatutAnnonce;
use App\Enums\StatutReservation;
use App\Models\Annonces\Annonce;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use Database\Seeders\PolitiquesAnnulationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests fonctionnels du module Paiements & Reçus (RG20, RG21, RG22).
 */
class PaiementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PolitiquesAnnulationSeeder::class);
    }

    public function test_paiement_carte_valide_succes_rg20_rg21_rg22(): void
    {
        $hote = User::factory()->hote()->create();
        $voyageur = User::factory()->voyageur()->create();

        $annonce = Annonce::factory()->create([
            'id_hote'          => $hote->getKey(),
            'statut'           => StatutAnnonce::PUBLIEE,
            'mode_reservation' => ModeReservation::INSTANTANEE,
            'tarif_nuit'       => 500,
        ]);

        $reservation = Reservation::create([
            'id_reservation'   => Str::uuid()->toString(),
            'id_annonce'       => $annonce->id_annonce,
            'id_voyageur'      => $voyageur->getKey(),
            'id_hote'          => $hote->getKey(),
            'id_politique'     => $annonce->id_politique,
            'date_arrivee'     => now()->addDays(5)->format('Y-m-d'),
            'date_depart'      => now()->addDays(7)->format('Y-m-d'),
            'nb_voyageurs'     => 2,
            'statut'           => StatutReservation::CONFIRMEE,
            'mode_reservation' => ModeReservation::INSTANTANEE,
            'montant_total'    => 1120.0,
            'frais_service'    => 120.0,
        ]);

        $response = $this->actingAs($voyageur, 'sanctum')
            ->postJson("/api/paiements/{$reservation->id_reservation}", [
                'numero_carte'    => '4242 4242 4242 4242',
                'date_expiration' => '12/28',
                'cvv'             => '123',
                'nom_titulaire'   => 'Taha Asrar',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.statut', 'reussi')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id_paiement', 'statut', 'numero_facture', 'id_recu'],
            ]);

        $paiementId = $response->json('data.id_paiement');

        $this->assertDatabaseHas('paiements', [
            'id_paiement'    => $paiementId,
            'id_reservation' => $reservation->id_reservation,
            'statut'         => 'reussi',
        ]);

        $this->assertDatabaseHas('recus', [
            'id_paiement' => $paiementId,
        ]);

        // Vérification de la planification du versement en séquestre (RG21)
        $this->assertDatabaseHas('versements', [
            'id_reservation' => $reservation->id_reservation,
            'statut'         => 'en_attente',
        ]);
    }

    public function test_paiement_carte_invalide_refus(): void
    {
        $hote = User::factory()->hote()->create();
        $voyageur = User::factory()->voyageur()->create();

        $annonce = Annonce::factory()->create(['id_hote' => $hote->getKey()]);

        $reservation = Reservation::create([
            'id_reservation' => Str::uuid()->toString(),
            'id_annonce'     => $annonce->id_annonce,
            'id_voyageur'    => $voyageur->getKey(),
            'id_hote'        => $hote->getKey(),
            'date_arrivee'   => now()->addDays(5)->format('Y-m-d'),
            'date_depart'    => now()->addDays(7)->format('Y-m-d'),
            'nb_voyageurs'   => 1,
            'statut'         => StatutReservation::CONFIRMEE,
            'mode_reservation' => ModeReservation::INSTANTANEE,
            'montant_total'  => 600.0,
            'frais_service'  => 60.0,
        ]);

        $response = $this->actingAs($voyageur, 'sanctum')
            ->postJson("/api/paiements/{$reservation->id_reservation}", [
                'numero_carte'    => '4000 0000 0000 1111',
                'date_expiration' => '05/27',
                'cvv'             => '999',
            ]);

        $response->assertStatus(402)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'PAIEMENT_REFUSE');

        $this->assertDatabaseHas('paiements', [
            'id_reservation' => $reservation->id_reservation,
            'statut'         => 'echoue',
        ]);
    }

    public function test_consultation_recu_autorisee_pour_voyageur(): void
    {
        $hote = User::factory()->hote()->create();
        $voyageur = User::factory()->voyageur()->create();

        $annonce = Annonce::factory()->create([
            'id_hote' => $hote->getKey(),
            'titre'   => 'Superbe Riad Marrakech',
        ]);

        $reservation = Reservation::create([
            'id_reservation' => Str::uuid()->toString(),
            'id_annonce'     => $annonce->id_annonce,
            'id_voyageur'    => $voyageur->getKey(),
            'id_hote'        => $hote->getKey(),
            'date_arrivee'   => now()->addDays(2)->format('Y-m-d'),
            'date_depart'    => now()->addDays(5)->format('Y-m-d'),
            'nb_voyageurs'   => 2,
            'statut'         => StatutReservation::CONFIRMEE,
            'mode_reservation' => ModeReservation::INSTANTANEE,
            'montant_total'  => 1500.0,
            'frais_service'  => 180.0,
        ]);

        // Pay reservation
        $resPay = $this->actingAs($voyageur, 'sanctum')
            ->postJson("/api/paiements/{$reservation->id_reservation}", [
                'numero_carte'    => '4242 4242 4242 4242',
                'date_expiration' => '12/28',
                'cvv'             => '123',
            ]);

        $paiementId = $resPay->json('data.id_paiement');

        // Consult receipt
        $response = $this->actingAs($voyageur, 'sanctum')
            ->getJson("/api/paiements/recu/{$paiementId}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.montant_total', 1500)
            ->assertJsonPath('data.frais_service', 180)
            ->assertJsonPath('data.annonce.titre', 'Superbe Riad Marrakech')
            ->assertJsonPath('data.voyageur.id_utilisateur', $voyageur->getKey());
    }

    public function test_consultation_recu_interdite_pour_utilisateur_tiers(): void
    {
        $hote = User::factory()->hote()->create();
        $voyageur = User::factory()->voyageur()->create();
        $tiers = User::factory()->voyageur()->create();

        $annonce = Annonce::factory()->create(['id_hote' => $hote->getKey()]);

        $reservation = Reservation::create([
            'id_reservation' => Str::uuid()->toString(),
            'id_annonce'     => $annonce->id_annonce,
            'id_voyageur'    => $voyageur->getKey(),
            'id_hote'        => $hote->getKey(),
            'date_arrivee'   => now()->addDays(2)->format('Y-m-d'),
            'date_depart'    => now()->addDays(5)->format('Y-m-d'),
            'nb_voyageurs'   => 2,
            'statut'         => StatutReservation::CONFIRMEE,
            'mode_reservation' => ModeReservation::INSTANTANEE,
            'montant_total'  => 1500.0,
            'frais_service'  => 180.0,
        ]);

        $resPay = $this->actingAs($voyageur, 'sanctum')
            ->postJson("/api/paiements/{$reservation->id_reservation}", [
                'numero_carte'    => '4242 4242 4242 4242',
                'date_expiration' => '12/28',
                'cvv'             => '123',
            ]);

        $paiementId = $resPay->json('data.id_paiement');

        // Third-party user attempts to access receipt
        $response = $this->actingAs($tiers, 'sanctum')
            ->getJson("/api/paiements/recu/{$paiementId}");

        $response->assertForbidden();
    }

    public function test_historique_transactions_voyageur(): void
    {
        $voyageur = User::factory()->voyageur()->create();
        $hote = User::factory()->hote()->create();
        $annonce = Annonce::factory()->create(['id_hote' => $hote->getKey()]);

        $reservation = Reservation::create([
            'id_reservation' => Str::uuid()->toString(),
            'id_annonce'     => $annonce->id_annonce,
            'id_voyageur'    => $voyageur->getKey(),
            'id_hote'        => $hote->getKey(),
            'date_arrivee'   => now()->addDays(2)->format('Y-m-d'),
            'date_depart'    => now()->addDays(5)->format('Y-m-d'),
            'nb_voyageurs'   => 2,
            'statut'         => StatutReservation::CONFIRMEE,
            'mode_reservation' => ModeReservation::INSTANTANEE,
            'montant_total'  => 800.0,
            'frais_service'  => 96.0,
        ]);

        $this->actingAs($voyageur, 'sanctum')
            ->postJson("/api/paiements/{$reservation->id_reservation}", [
                'numero_carte'    => '4242 4242 4242 4242',
                'date_expiration' => '12/28',
                'cvv'             => '123',
            ]);

        $response = $this->actingAs($voyageur, 'sanctum')
            ->getJson('/api/paiements/historique');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.montant', 800);
    }
}
