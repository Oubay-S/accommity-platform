<?php

namespace Tests\Feature;

use App\Models\Annonces\Annonce;
use App\Models\Annonces\Calendrier;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use App\Models\Utilisateurs\VerificationIdentite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CalendrierHoteTest extends TestCase
{
    use RefreshDatabase;

    private User $hote;
    private Annonce $annonce;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hote = User::factory()->hote()->create();
        VerificationIdentite::factory()->validee()->create(['id_utilisateur' => $this->hote->getKey()]);
        $this->annonce = Annonce::factory()->create(['id_hote' => $this->hote->getKey()]);

        Sanctum::actingAs($this->hote);
    }

    private function url(string $action): string
    {
        return '/api/hote/annonces/'.$this->annonce->getKey().'/'.$action;
    }

    public function test_bloquer_une_plage_cree_un_blocage_visible_sur_le_calendrier(): void
    {
        $debut = now()->addDays(5)->toDateString();
        $fin = now()->addDays(7)->toDateString();

        $this->postJson($this->url('bloquer'), [
            'date_debut' => $debut,
            'date_fin' => $fin,
        ])
            ->assertOk()
            ->assertJsonPath('data.date_debut', $debut)
            ->assertJsonPath('data.date_fin', $fin);

        $blocage = Calendrier::query()
            ->where('id_annonce', $this->annonce->getKey())
            ->firstOrFail();

        $this->assertSame($debut, $blocage->date_debut->format('Y-m-d'));
        $this->assertSame($fin, $blocage->date_fin->format('Y-m-d'));
        $this->assertSame('bloque_manuel', $blocage->type_blockage);
        $this->assertFalse($blocage->est_disponible);

        $this->getJson($this->url('calendrier'))
            ->assertOk()
            ->assertJsonPath('data.blocages.0.date_debut', $debut)
            ->assertJsonPath('data.blocages.0.date_fin', $fin);
    }

    public function test_blocage_sur_une_reservation_active_retourne_409(): void
    {
        $arrivee = now()->addDays(10);
        $depart = now()->addDays(13);

        Reservation::factory()->create([
            'id_annonce' => $this->annonce->getKey(),
            'id_hote' => $this->hote->getKey(),
            'date_arrivee' => $arrivee->toDateString(),
            'date_depart' => $depart->toDateString(),
        ]);

        $this->postJson($this->url('bloquer'), [
            'date_debut' => $arrivee->copy()->addDay()->toDateString(),
            'date_fin' => $arrivee->copy()->addDay()->toDateString(),
        ])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'DATES_RESERVEES');
    }

    public function test_le_calendrier_expose_les_nuits_reservees_et_les_blocages(): void
    {
        $d1 = now()->addDays(10);
        $d2 = now()->addDays(13);

        Reservation::factory()->create([
            'id_annonce' => $this->annonce->getKey(),
            'id_hote' => $this->hote->getKey(),
            'date_arrivee' => $d1->toDateString(),
            'date_depart' => $d2->toDateString(),
        ]);

        $b1 = now()->addDays(20)->toDateString();
        $b2 = now()->addDays(21)->toDateString();

        Calendrier::create([
            'id_annonce' => $this->annonce->getKey(),
            'date_debut' => $b1,
            'date_fin' => $b2,
            'est_disponible' => false,
            'type_blockage' => 'bloque_manuel',
        ]);

        $response = $this->getJson($this->url('calendrier'));

        $response->assertOk();
        $this->assertContains($d1->toDateString(), $response->json('data.dates_reservees'));
        $this->assertContains($d1->copy()->addDay()->toDateString(), $response->json('data.dates_reservees'));
        $this->assertContains($d1->copy()->addDays(2)->toDateString(), $response->json('data.dates_reservees'));
        $this->assertNotContains($d2->toDateString(), $response->json('data.dates_reservees'));
        $this->assertCount(1, $response->json('data.blocages'));
    }

    public function test_debloquer_une_partie_conserve_les_plages_restantes(): void
    {
        $debut = now()->addDays(5)->toDateString();
        $fin = now()->addDays(10)->toDateString();

        Calendrier::create([
            'id_annonce' => $this->annonce->getKey(),
            'date_debut' => $debut,
            'date_fin' => $fin,
            'est_disponible' => false,
            'type_blockage' => 'bloque_manuel',
        ]);

        $this->postJson($this->url('debloquer'), [
            'date_debut' => now()->addDays(7)->toDateString(),
            'date_fin' => now()->addDays(8)->toDateString(),
        ])
            ->assertOk()
            ->assertJsonPath('data.blocages_supprimes', 1);

        $blocages = Calendrier::query()
            ->where('id_annonce', $this->annonce->getKey())
            ->orderBy('date_debut')
            ->get();

        $this->assertCount(2, $blocages);
        $this->assertSame($debut, $blocages[0]->date_debut->format('Y-m-d'));
        $this->assertSame(now()->addDays(6)->toDateString(), $blocages[0]->date_fin->format('Y-m-d'));
        $this->assertSame(now()->addDays(9)->toDateString(), $blocages[1]->date_debut->format('Y-m-d'));
        $this->assertSame($fin, $blocages[1]->date_fin->format('Y-m-d'));
    }

    public function test_debloquer_ne_touche_pas_aux_reservations(): void
    {
        $arrivee = now()->addDays(10);
        $depart = now()->addDays(12);

        Reservation::factory()->create([
            'id_annonce' => $this->annonce->getKey(),
            'id_hote' => $this->hote->getKey(),
            'date_arrivee' => $arrivee->toDateString(),
            'date_depart' => $depart->toDateString(),
        ]);

        $this->postJson($this->url('debloquer'), [
            'date_debut' => $arrivee->toDateString(),
            'date_fin' => $depart->toDateString(),
        ])
            ->assertOk()
            ->assertJsonPath('data.blocages_supprimes', 0);

        $this->assertDatabaseCount('calendriers', 0);
    }

    public function test_dates_invalides_retournent_422(): void
    {
        $this->postJson($this->url('bloquer'), [
            'date_debut' => now()->addDays(8)->toDateString(),
            'date_fin' => now()->addDays(4)->toDateString(),
        ])->assertStatus(422);

        $this->postJson($this->url('bloquer'), [
            'date_debut' => now()->subDay()->toDateString(),
            'date_fin' => now()->addDay()->toDateString(),
        ])->assertStatus(422);

        $this->postJson($this->url('bloquer'), [
            'date_debut' => '12/12/2026',
            'date_fin' => '13/12/2026',
        ])->assertStatus(422);
    }

    public function test_un_hote_ne_peut_pas_gerer_le_calendrier_d_un_autre(): void
    {
        $autre = Annonce::factory()->create();

        $this->getJson('/api/hote/annonces/'.$autre->getKey().'/calendrier')
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'ANNONCE_INTROUVABLE');
    }

    public function test_le_calendrier_public_reste_base_sur_les_donnees_reelles(): void
    {
        $debut = now()->addDays(5)->toDateString();
        $fin = now()->addDays(6)->toDateString();

        $this->postJson($this->url('bloquer'), [
            'date_debut' => $debut,
            'date_fin' => $fin,
        ])->assertOk();

        $this->annonce->update(['statut' => 'publiee']);

        $this->getJson('/api/annonces/'.$this->annonce->getKey().'/disponibilites')
            ->assertOk()
            ->assertJsonPath('data.dates_indisponibles', [$debut, $fin]);
    }
}
