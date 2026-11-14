<?php

namespace Tests\Feature;

use App\Models\Utilisateurs\Profil;
use App\Models\Utilisateurs\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Profil utilisateur (RG04).
 */
class ProfilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function photo(string $name = 'avatar.png'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 100)->mimeType('image/png');
    }

    public function test_show_cree_le_profil_s_il_n_existe_pas(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->profil);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/profil')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.profil.description', null)
            ->assertJsonPath('data.profil.photo_url', null);

        $this->assertDatabaseHas('profils', ['id_utilisateur' => $user->getKey()]);
    }

    public function test_mise_a_jour_de_la_description(): void
    {
        $user = User::factory()->create();
        Profil::factory()->create(['id_utilisateur' => $user->getKey()]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profil', ['description' => 'Voyageur passionné de surf.'])
            ->assertOk()
            ->assertJsonPath('data.profil.description', 'Voyageur passionné de surf.');

        $this->assertDatabaseHas('profils', [
            'id_utilisateur' => $user->getKey(),
            'description' => 'Voyageur passionné de surf.',
        ]);
    }

    public function test_la_note_moyenne_n_est_pas_modifiable(): void
    {
        $user = User::factory()->create();
        Profil::factory()->create([
            'id_utilisateur' => $user->getKey(),
            'note_moyenne' => 4.5,
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profil', [
                'description' => 'Nouvelle description.',
                'note_moyenne' => 1,
            ])
            ->assertOk();

        $this->assertSame(4.5, (float) $user->profil()->firstOrFail()->note_moyenne);
    }

    public function test_upload_de_photo_publique(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->post('/api/profil/photo', ['photo' => $this->photo()], ['Accept' => 'application/json']);

        $response->assertOk()->assertJsonPath('success', true);

        $url = $response->json('data.profil.photo_url');
        $this->assertIsString($url);
        $this->assertStringContainsString('profils/', $url);

        $profil = $user->profil()->firstOrFail();
        $this->assertNotNull($profil->photo_url);
        Storage::disk('public')->assertExists($profil->photo_url);
    }

    public function test_upload_refuse_un_fichier_non_image(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->post('/api/profil/photo', [
                'photo' => UploadedFile::fake()->create('document.pdf', 100)->mimeType('application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ECHOUEE')
            ->assertJsonStructure(['errors' => ['photo']]);
    }

    public function test_une_nouvelle_photo_supprime_l_ancienne(): void
    {
        $user = User::factory()->create();
        $profil = Profil::factory()->create([
            'id_utilisateur' => $user->getKey(),
            'photo_url' => 'profils/ancienne.png',
        ]);
        Storage::disk('public')->put('profils/ancienne.png', 'contenu');

        $this->actingAs($user, 'sanctum')
            ->post('/api/profil/photo', ['photo' => $this->photo()], ['Accept' => 'application/json'])
            ->assertOk();

        Storage::disk('public')->assertMissing('profils/ancienne.png');
        $this->assertNotSame('profils/ancienne.png', $profil->fresh()->photo_url);
    }

    public function test_le_profil_exige_une_authentification(): void
    {
        $this->getJson('/api/profil')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'NON_AUTHENTIFIE');
    }

    public function test_mise_a_jour_de_l_identite(): void
    {
        $user = User::factory()->create([
            'nom' => 'Ancien',
            'prenom' => 'Nom',
            'email' => 'ancien@example.com',
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profil', [
                'nom' => 'Nouveau',
                'prenom' => 'Prénom',
                'email' => 'Nouveau@Example.com',
                'telephone' => '0611111111',
            ])
            ->assertOk()
            ->assertJsonPath('data.user.nom', 'Nouveau')
            ->assertJsonPath('data.user.prenom', 'Prénom')
            ->assertJsonPath('data.user.email', 'nouveau@example.com')
            ->assertJsonPath('data.user.telephone', '0611111111');

        $this->assertDatabaseHas('utilisateurs', [
            'id_utilisateur' => $user->getKey(),
            'nom' => 'Nouveau',
            'email' => 'nouveau@example.com',
        ]);
    }

    public function test_l_email_doit_rester_unique(): void
    {
        User::factory()->create(['email' => 'pris@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profil', ['email' => 'pris@example.com'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_l_utilisateur_peut_conserver_son_email_actuel(): void
    {
        $user = User::factory()->create(['email' => 'moi@example.com']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profil', ['email' => 'moi@example.com'])
            ->assertOk();
    }

    public function test_changement_de_mot_de_passe(): void
    {
        $user = User::factory()->create(['mot_de_passe' => 'Password123']);
        $token = $user->createToken('current')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/profil/password', [
                'current_password' => 'Password123',
                'password' => 'NouveauPass123',
                'password_confirmation' => 'NouveauPass123',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check('NouveauPass123', $fresh->getAuthPassword()));
        $this->assertFalse(Hash::check('Password123', $fresh->getAuthPassword()));
    }

    public function test_changement_de_mot_de_passe_refuse_un_mauvais_mot_de_passe_actuel(): void
    {
        $user = User::factory()->create(['mot_de_passe' => 'Password123']);
        $token = $user->createToken('current')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/profil/password', [
                'current_password' => 'Mauvais',
                'password' => 'NouveauPass123',
                'password_confirmation' => 'NouveauPass123',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'MOT_DE_PASSE_ACTUEL_INVALIDE');
    }

    public function test_le_changement_de_mot_de_passe_revoque_les_autres_sessions(): void
    {
        $user = User::factory()->create(['mot_de_passe' => 'Password123']);
        $current = $user->createToken('current')->plainTextToken;
        $user->createToken('autre-session');

        $this->assertSame(2, $user->tokens()->count());

        $this->withToken($current)
            ->putJson('/api/profil/password', [
                'current_password' => 'Password123',
                'password' => 'NouveauPass123',
                'password_confirmation' => 'NouveauPass123',
            ])
            ->assertOk();

        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame('current', $user->tokens()->first()->name);
    }
}
