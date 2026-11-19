<?php

namespace Tests\Unit;

use App\Enums\ModeReservation;
use App\Enums\StatutReservation;
use App\Models\Annonces\Annonce;
use App\Models\Annonces\PolitiqueAnnulation;
use App\Models\Paiements\Paiement;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use App\Services\Paiements\RemboursementService;
use Carbon\Carbon;
use Database\Seeders\PolitiquesAnnulationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RemboursementCalculTest extends TestCase
{
    use RefreshDatabase;

    protected RemboursementService $remboursementService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->remboursementService = app(RemboursementService::class);
        $this->seed(PolitiquesAnnulationSeeder::class);
    }

    public function test_remboursement_flexible_plus_de_24h_avant_arrivee(): void
    {
        $politique = PolitiqueAnnulation::where('type_politique', 'flexible')->first();
        $reservation = $this->creerReservationAvecPaiement($politique, now()->addDays(3), 1000.0);

        $apercu = $this->remboursementService->calculerRemboursement($reservation->id_reservation, 'voyageur');

        $this->assertEquals(100.0, $apercu['taux_applique']);
        $this->assertEquals(1000.0, $apercu['montant_rembourse']);
        $this->assertEquals(0.0, $apercu['montant_non_remb']);
    }

    public function test_remboursement_flexible_moins_de_24h_avant_arrivee(): void
    {
        $politique = PolitiqueAnnulation::where('type_politique', 'flexible')->first();
        $reservation = $this->creerReservationAvecPaiement($politique, now()->addHours(10), 1000.0);

        $apercu = $this->remboursementService->calculerRemboursement($reservation->id_reservation, 'voyageur');

        $this->assertEquals(0.0, $apercu['taux_applique']);
        $this->assertEquals(0.0, $apercu['montant_rembourse']);
        $this->assertEquals(1000.0, $apercu['montant_non_remb']);
    }

    public function test_remboursement_moderee_entre_24h_et_5_jours(): void
    {
        $politique = PolitiqueAnnulation::where('type_politique', 'moderee')->first();
        $reservation = $this->creerReservationAvecPaiement($politique, now()->addDays(3), 1000.0);

        $apercu = $this->remboursementService->calculerRemboursement($reservation->id_reservation, 'voyageur');

        $this->assertEquals(50.0, $apercu['taux_applique']);
        $this->assertEquals(500.0, $apercu['montant_rembourse']);
        $this->assertEquals(500.0, $apercu['montant_non_remb']);
    }

    public function test_remboursement_stricte_plus_de_14_jours(): void
    {
        $politique = PolitiqueAnnulation::where('type_politique', 'stricte')->first();
        $reservation = $this->creerReservationAvecPaiement($politique, now()->addDays(16), 2000.0);

        $apercu = $this->remboursementService->calculerRemboursement($reservation->id_reservation, 'voyageur');

        $this->assertEquals(100.0, $apercu['taux_applique']);
        $this->assertEquals(2000.0, $apercu['montant_rembourse']);
        $this->assertEquals(0.0, $apercu['montant_non_remb']);
    }

    public function test_annulation_par_hote_garantit_100_pourcent_remboursement(): void
    {
        $politique = PolitiqueAnnulation::where('type_politique', 'stricte')->first();
        // Moins de 24h avant l'arrivée avec politique stricte : normalement 0% pour le voyageur
        $reservation = $this->creerReservationAvecPaiement($politique, now()->addHours(5), 1000.0);

        $apercu = $this->remboursementService->calculerRemboursement($reservation->id_reservation, 'hote');

        $this->assertEquals(100.0, $apercu['taux_applique'], 'RG24 : Annulation par l hôte garantit 100%');
        $this->assertEquals(1000.0, $apercu['montant_rembourse']);
        $this->assertEquals(0.0, $apercu['montant_non_remb']);
    }

    private function creerReservationAvecPaiement(PolitiqueAnnulation $politique, Carbon $dateArrivee, float $montant): Reservation
    {
        $hote = User::factory()->hote()->create();
        $voyageur = User::factory()->voyageur()->create();

        $annonce = Annonce::factory()->create([
            'id_hote'      => $hote->getKey(),
            'id_politique' => $politique->id_politique,
        ]);

        $reservation = Reservation::create([
            'id_reservation'    => Str::uuid()->toString(),
            'id_annonce'        => $annonce->id_annonce,
            'id_voyageur'       => $voyageur->getKey(),
            'id_hote'           => $hote->getKey(),
            'id_politique'      => $politique->id_politique,
            'date_arrivee'      => $dateArrivee->format('Y-m-d'),
            'date_depart'       => $dateArrivee->copy()->addDays(3)->format('Y-m-d'),
            'nb_voyageurs'      => 2,
            'statut'            => StatutReservation::CONFIRMEE,
            'mode_reservation'  => ModeReservation::INSTANTANEE,
            'montant_total'     => $montant,
            'frais_service'     => round($montant * 0.12, 2),
        ]);

        Paiement::create([
            'id_paiement'      => Str::uuid()->toString(),
            'id_reservation'   => $reservation->id_reservation,
            'id_voyageur'      => $voyageur->getKey(),
            'montant'          => $montant,
            'methode_paiement' => 'carte_bancaire',
            'statut'           => 'reussi',
        ]);

        return $reservation->fresh();
    }
}
