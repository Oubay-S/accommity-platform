<?php

namespace Tests\Feature;

use App\Enums\StatutAnnonce;
use App\Models\Annonces\Annonce;
use App\Models\Annonces\Calendrier;
use App\Models\Annonces\CategorieGeographique;
use App\Models\Evaluations\Evaluation;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnnonceTest extends TestCase
{
    use RefreshDatabase;

    public function test_seules_les_annonces_publiees_sont_retournees(): void
    {
        $publiee = Annonce::factory()->create(['titre' => 'Riad Publié']);
        Annonce::factory()->suspendue()->create(['titre' => 'Riad Suspendu']);

        $response = $this->getJson('/api/annonces');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id_annonce', $publiee->getKey());
    }

    public function test_recherche_par_ville_et_region_insensible_a_la_casse(): void
    {
        $catMarrakech = CategorieGeographique::factory()->create([
            'ville' => 'Marrakech',
            'region' => 'Marrakech-Safi',
        ]);
        $catTanger = CategorieGeographique::factory()->create([
            'ville' => 'Tanger',
            'region' => 'Tanger-Tétouan-Al Hoceïma',
        ]);

        $annonceMarrakech = Annonce::factory()->create(['id_categorie' => $catMarrakech->getKey()]);
        $annonceTanger = Annonce::factory()->create(['id_categorie' => $catTanger->getKey()]);

        $this->getJson('/api/annonces?ville=marra')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id_annonce', $annonceMarrakech->getKey());

        $this->getJson('/api/annonces?ville=hoce')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id_annonce', $annonceTanger->getKey());
    }

    public function test_filtre_capacite_voyageurs(): void
    {
        $grande = Annonce::factory()->create(['capacite' => 6]);
        Annonce::factory()->create(['capacite' => 4]);

        $this->getJson('/api/annonces?voyageurs=5')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id_annonce', $grande->getKey());
    }

    public function test_filtre_dates_exclut_reservations_confirmees_chevauchantes_seulement(): void
    {
        $arrivee = now()->addDays(10)->toDateString();
        $depart = now()->addDays(15)->toDateString();

        // 1. Réservation confirmée qui chevauche -> exclue
        $annonceOccupee = Annonce::factory()->create();
        Reservation::factory()->create([
            'id_annonce' => $annonceOccupee->getKey(),
            'id_hote' => $annonceOccupee->id_hote,
            'date_arrivee' => now()->addDays(12)->toDateString(),
            'date_depart' => now()->addDays(17)->toDateString(),
        ]);

        // 2. Réservation en_attente qui chevauche -> incluse
        $annonceEnAttente = Annonce::factory()->create();
        Reservation::factory()->enAttente()->create([
            'id_annonce' => $annonceEnAttente->getKey(),
            'id_hote' => $annonceEnAttente->id_hote,
            'date_arrivee' => $arrivee,
            'date_depart' => $depart,
        ]);

        // 3. Réservation confirmée adjacente (départ = arrivée demandée) -> incluse
        $annonceAdjacente = Annonce::factory()->create();
        Reservation::factory()->create([
            'id_annonce' => $annonceAdjacente->getKey(),
            'id_hote' => $annonceAdjacente->id_hote,
            'date_arrivee' => now()->addDays(5)->toDateString(),
            'date_depart' => $arrivee,
        ]);

        $response = $this->getJson("/api/annonces?date_arrivee={$arrivee}&date_depart={$depart}");

        $response->assertOk()->assertJsonCount(2, 'data.items');
        $ids = collect($response->json('data.items'))->pluck('id_annonce')->all();
        $this->assertNotContains($annonceOccupee->getKey(), $ids);
        $this->assertContains($annonceEnAttente->getKey(), $ids);
        $this->assertContains($annonceAdjacente->getKey(), $ids);
    }

    public function test_filtre_prix_et_equipements(): void
    {
        $cible = Annonce::factory()->create([
            'tarif_nuit' => 900,
            'equipements' => 'wifi, piscine, parking',
        ]);
        Annonce::factory()->create(['tarif_nuit' => 300, 'equipements' => 'wifi, piscine']);
        Annonce::factory()->create(['tarif_nuit' => 900, 'equipements' => 'wifi']);

        $this->getJson('/api/annonces?prix_min=500&prix_max=1200&equipements=wifi,piscine')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id_annonce', $cible->getKey());

        $this->getJson('/api/annonces?prix_max=500')
            ->assertOk()
            ->assertJsonCount(1, 'data.items');
    }

    public function test_structure_pagination_et_plafond_per_page(): void
    {
        Annonce::factory()->count(3)->create();

        $this->getJson('/api/annonces?per_page=2')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items',
                    'pagination' => ['total', 'per_page', 'current_page', 'last_page'],
                ],
            ])
            ->assertJsonPath('data.pagination.total', 3)
            ->assertJsonPath('data.pagination.per_page', 2)
            ->assertJsonPath('data.pagination.last_page', 2);

        $this->getJson('/api/annonces?per_page=100')
            ->assertStatus(422);
    }

    public function test_en_vedette_retourne_au_plus_6_annonces_publiees(): void
    {
        Annonce::factory()->count(8)->create();
        Annonce::factory()->suspendue()->create();

        $response = $this->getJson('/api/annonces/en-vedette');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(6, 'data');
    }

    public function test_detail_annonce_et_erreurs_404(): void
    {
        $publiee = Annonce::factory()->create();
        $suspendue = Annonce::factory()->suspendue()->create();

        $this->getJson('/api/annonces/' . $publiee->getKey())
            ->assertOk()
            ->assertJsonPath('data.id_annonce', $publiee->getKey());

        $this->getJson('/api/annonces/' . $suspendue->getKey())
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'ANNONCE_INTROUVABLE');

        $this->getJson('/api/annonces/' . Str::uuid()->toString())
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'ANNONCE_INTROUVABLE');

        $this->getJson('/api/annonces/pas-un-uuid')
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'ANNONCE_INTROUVABLE');
    }

    public function test_disponibilites_liste_nuits_reservees_et_blocages_manuels(): void
    {
        $annonce = Annonce::factory()->create();

        // Réservation confirmée du 10 au 13 -> nuits 10, 11, 12 (13 exclu)
        $d1 = now()->addDays(10)->toDateString();
        $d2 = now()->addDays(11)->toDateString();
        $d3 = now()->addDays(12)->toDateString();
        $d4 = now()->addDays(13)->toDateString();

        Reservation::factory()->create([
            'id_annonce' => $annonce->getKey(),
            'id_hote' => $annonce->id_hote,
            'date_arrivee' => $d1,
            'date_depart' => $d4,
        ]);

        // Blocage manuel du 20 au 21 inclus -> 20, 21
        $b1 = now()->addDays(20)->toDateString();
        $b2 = now()->addDays(21)->toDateString();

        Calendrier::create([
            'id_annonce' => $annonce->getKey(),
            'date_debut' => $b1,
            'date_fin' => $b2,
            'est_disponible' => false,
            'type_blockage' => 'bloque_manuel',
        ]);

        $response = $this->getJson('/api/annonces/' . $annonce->getKey() . '/disponibilites');

        $response->assertOk()
            ->assertJsonPath('data.dates_indisponibles', [$d1, $d2, $d3, $b1, $b2]);
    }

    public function test_note_moyenne_et_nb_avis_comptent_uniquement_avis_voyageurs(): void
    {
        $annonce = Annonce::factory()->create();
        $voyageur = User::factory()->voyageur()->create();

        $res1 = Reservation::factory()->create([
            'id_annonce' => $annonce->getKey(),
            'id_hote' => $annonce->id_hote,
            'id_voyageur' => $voyageur->getKey(),
        ]);
        $res2 = Reservation::factory()->create([
            'id_annonce' => $annonce->getKey(),
            'id_hote' => $annonce->id_hote,
            'id_voyageur' => $voyageur->getKey(),
        ]);

        // Avis voyageur: 4 et 5 -> moyenne 4.5, nb_avis = 2
        Evaluation::create([
            'id_reservation' => $res1->getKey(),
            'id_auteur' => $voyageur->getKey(),
            'id_cible' => $annonce->id_hote,
            'id_annonce' => $annonce->getKey(),
            'type_auteur' => 'voyageur',
            'note' => 4,
            'commentaire' => 'Très bien',
        ]);
        Evaluation::create([
            'id_reservation' => $res2->getKey(),
            'id_auteur' => $voyageur->getKey(),
            'id_cible' => $annonce->id_hote,
            'id_annonce' => $annonce->getKey(),
            'type_auteur' => 'voyageur',
            'note' => 5,
            'commentaire' => 'Parfait',
        ]);

        // Avis hôte: 1 -> doit être ignoré
        Evaluation::create([
            'id_reservation' => $res1->getKey(),
            'id_auteur' => $annonce->id_hote,
            'id_cible' => $voyageur->getKey(),
            'id_annonce' => $annonce->getKey(),
            'type_auteur' => 'hote',
            'note' => 1,
            'commentaire' => 'Avis hôte',
        ]);

        $this->getJson('/api/annonces/' . $annonce->getKey())
            ->assertOk()
            ->assertJsonPath('data.note_moyenne', 4.5)
            ->assertJsonPath('data.nb_avis', 2);
    }

    public function test_validation_dates_invalides_retourne_422(): void
    {
        $arrivee = now()->addDays(10)->toDateString();
        $depart = now()->addDays(10)->toDateString();

        $this->getJson("/api/annonces?date_arrivee={$arrivee}&date_depart={$depart}")
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors']);
    }
}
