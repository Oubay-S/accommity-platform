<?php

namespace Tests\Feature;

use App\Enums\StatutAnnonce;
use App\Models\Annonces\Annonce;
use App\Models\Annonces\CategorieGeographique;
use App\Models\Annonces\PolitiqueAnnulation;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use App\Models\Utilisateurs\VerificationIdentite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnnonceHoteTest extends TestCase
{
    use RefreshDatabase;

    private User $hote;
    private CategorieGeographique $categorie;
    private PolitiqueAnnulation $politique;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->hote = User::factory()->hote()->create();
        VerificationIdentite::factory()->validee()->create(['id_utilisateur' => $this->hote->getKey()]);

        $this->categorie = CategorieGeographique::factory()->create();
        $this->politique = PolitiqueAnnulation::factory()->create();
    }

    private function payload(array $surcharges = []): array
    {
        return array_merge([
            'titre' => 'Riad de charme',
            'description' => 'Un riad au cœur de la médina.',
            'type_logement' => 'riad',
            'adresse' => '12 rue des épices',
            'id_categorie' => $this->categorie->getKey(),
            'id_politique' => $this->politique->getKey(),
            'capacite' => 4,
            'tarif_nuit' => 800,
            'mode_reservation' => 'instantanee',
            'equipements' => ['wifi', 'piscine'],
            'reglement_interieur' => 'Non fumeur.',
            'photos' => [UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg')],
        ], $surcharges);
    }

    public function test_un_hote_non_verifie_ne_peut_pas_creer_une_annonce(): void
    {
        $hote = User::factory()->hote()->create();
        Sanctum::actingAs($hote);

        $this->post('/api/hote/annonces', $this->payload(), ['Accept' => 'application/json'])
            ->assertStatus(403);
    }

    public function test_un_voyageur_ne_peut_pas_creer_une_annonce(): void
    {
        Sanctum::actingAs(User::factory()->voyageur()->create());

        $this->post('/api/hote/annonces', $this->payload(), ['Accept' => 'application/json'])
            ->assertStatus(403);
    }

    public function test_creation_valide_cree_une_annonce_en_attente_et_stocke_les_photos(): void
    {
        Sanctum::actingAs($this->hote);

        $response = $this->post('/api/hote/annonces', $this->payload());

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.statut', StatutAnnonce::EN_ATTENTE->value)
            ->assertJsonPath('data.id_hote', $this->hote->getKey())
            ->assertJsonPath('data.tarif_nuit', '800.00');

        $annonce = Annonce::query()->firstOrFail();
        $this->assertSame($this->hote->getKey(), $annonce->id_hote);
        $this->assertSame(StatutAnnonce::EN_ATTENTE, $annonce->statut);
        $this->assertCount(1, $annonce->photo_url);
        $this->assertStringContainsString('/storage/annonces/', $annonce->photo_url[0]);

        $chemin = Str::after($annonce->photo_url[0], '/storage/');
        Storage::disk('public')->assertExists($chemin);
    }

    public function test_creation_sans_photo_retourne_422(): void
    {
        Sanctum::actingAs($this->hote);

        $payload = $this->payload();
        unset($payload['photos']);

        $this->post('/api/hote/annonces', $payload, ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ECHOUEE');
    }

    public function test_creation_avec_champ_invalide_retourne_422(): void
    {
        Sanctum::actingAs($this->hote);

        $this->post('/api/hote/annonces', $this->payload(['capacite' => 0]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'errors' => ['capacite']]);
    }

    public function test_mes_annonces_ne_renvoie_que_celles_de_l_hote(): void
    {
        $autreHote = User::factory()->hote()->create();
        $mienne = Annonce::factory()->create(['id_hote' => $this->hote->getKey()]);
        Annonce::factory()->create(['id_hote' => $autreHote->getKey()]);

        Sanctum::actingAs($this->hote);

        $this->getJson('/api/hote/annonces')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.id_annonce', $mienne->getKey())
            ->assertJsonPath('data.statistiques.total', 1);
    }

    public function test_filtre_statut_et_recherche(): void
    {
        Annonce::factory()->create(['id_hote' => $this->hote->getKey(), 'titre' => 'Villa Atlas']);
        Annonce::factory()->suspendue()->create(['id_hote' => $this->hote->getKey(), 'titre' => 'Riad Medina']);

        Sanctum::actingAs($this->hote);

        $this->getJson('/api/hote/annonces?statut=suspendue')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.titre', 'Riad Medina');

        $this->getJson('/api/hote/annonces?recherche=atlas')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.titre', 'Villa Atlas');
    }

    public function test_le_detail_hote_fonctionne_pour_une_annonce_non_publiee(): void
    {
        $annonce = Annonce::factory()->create([
            'id_hote' => $this->hote->getKey(),
            'statut' => StatutAnnonce::EN_ATTENTE,
        ]);

        Sanctum::actingAs($this->hote);

        $this->getJson('/api/hote/annonces/'.$annonce->getKey())
            ->assertOk()
            ->assertJsonPath('data.id_annonce', $annonce->getKey());
    }

    public function test_un_hote_ne_peut_pas_voir_l_annonce_d_un_autre(): void
    {
        $annonce = Annonce::factory()->create();

        Sanctum::actingAs($this->hote);

        $this->getJson('/api/hote/annonces/'.$annonce->getKey())
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'ANNONCE_INTROUVABLE');
    }

    public function test_modification_de_sa_propre_annonce(): void
    {
        $annonce = Annonce::factory()->create(['id_hote' => $this->hote->getKey()]);

        Sanctum::actingAs($this->hote);

        $this->putJson('/api/hote/annonces/'.$annonce->getKey(), [
            'titre' => 'Nouveau titre',
            'tarif_nuit' => 950,
        ])
            ->assertOk()
            ->assertJsonPath('data.titre', 'Nouveau titre')
            ->assertJsonPath('data.tarif_nuit', '950.00');
    }

    public function test_un_hote_ne_peut_pas_modifier_l_annonce_d_un_autre(): void
    {
        $annonce = Annonce::factory()->create();

        Sanctum::actingAs($this->hote);

        $this->putJson('/api/hote/annonces/'.$annonce->getKey(), ['titre' => 'Pirate'])
            ->assertStatus(403);
    }

    public function test_suppression_sans_reservation(): void
    {
        $annonce = Annonce::factory()->create(['id_hote' => $this->hote->getKey()]);

        Sanctum::actingAs($this->hote);

        $this->deleteJson('/api/hote/annonces/'.$annonce->getKey())
            ->assertOk();

        $this->assertDatabaseMissing('annonces', ['id_annonce' => $annonce->getKey()]);
    }

    public function test_suppression_impossible_avec_reservation_active(): void
    {
        $annonce = Annonce::factory()->create(['id_hote' => $this->hote->getKey()]);
        Reservation::factory()->create([
            'id_annonce' => $annonce->getKey(),
            'id_hote' => $this->hote->getKey(),
        ]);

        Sanctum::actingAs($this->hote);

        $this->deleteJson('/api/hote/annonces/'.$annonce->getKey())
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'ANNONCE_AYANT_RESERVATIONS');

        $this->assertDatabaseHas('annonces', ['id_annonce' => $annonce->getKey()]);
    }

    public function test_uuid_invalide_retourne_404(): void
    {
        Sanctum::actingAs($this->hote);

        $this->putJson('/api/hote/annonces/pas-un-uuid', ['titre' => 'Test'])
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'ANNONCE_INTROUVABLE');
    }

    public function test_ajout_et_suppression_de_photos(): void
    {
        $annonce = Annonce::factory()->create([
            'id_hote' => $this->hote->getKey(),
            'photo_url' => [],
        ]);

        Sanctum::actingAs($this->hote);

        $response = $this->post(
            '/api/hote/annonces/'.$annonce->getKey().'/photos',
            ['photos' => [UploadedFile::fake()->create('nouvelle.jpg', 100, 'image/jpeg')]],
        );

        $response->assertOk();
        $url = $response->json('data.photos.0');
        $this->assertNotEmpty($url);

        $this->deleteJson('/api/hote/annonces/'.$annonce->getKey().'/photos', ['url' => $url])
            ->assertOk()
            ->assertJsonCount(0, 'data.photos');
    }

    public function test_un_hote_ne_peut_pas_ajouter_de_photo_a_l_annonce_d_un_autre(): void
    {
        $annonce = Annonce::factory()->create();

        Sanctum::actingAs($this->hote);

        $this->post('/api/hote/annonces/'.$annonce->getKey().'/photos', [
            'photos' => [UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg')],
        ], ['Accept' => 'application/json'])->assertStatus(403);
    }

    public function test_supprimer_une_photo_inconnue_retourne_404(): void
    {
        $annonce = Annonce::factory()->create(['id_hote' => $this->hote->getKey()]);

        Sanctum::actingAs($this->hote);

        $this->deleteJson('/api/hote/annonces/'.$annonce->getKey().'/photos', [
            'url' => 'http://localhost/storage/annonces/inconnue.jpg',
        ])->assertStatus(404)->assertJsonPath('error_code', 'PHOTO_INTROUVABLE');
    }

    public function test_references_publiques(): void
    {
        $this->getJson('/api/annonces/references')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'categories' => [['id_categorie', 'ville', 'region', 'pays']],
                    'politiques' => [['id_politique', 'type_politique', 'description']],
                    'types_logement',
                ],
            ]);
    }
}
