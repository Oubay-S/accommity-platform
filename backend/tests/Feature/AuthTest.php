<?php

namespace Tests\Feature;

use App\Models\Utilisateurs\User;
use App\Models\Utilisateurs\VerificationIdentite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Authentification API (RG01, RG02, RG05, RO06).
 *
 * Format de réponse imposé : enveloppe ApiResponse<T>.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nom' => 'Aouichi',
            'prenom' => 'Omar',
            'email' => 'omar@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'telephone' => '0612345678',
            'role' => 'voyageur',
        ], $overrides);
    }

    public function test_inscription_cree_un_utilisateur_et_son_profil(): void
    {
        $response = $this->postJson('/api/auth/register', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id_utilisateur', 'nom', 'prenom', 'email', 'role', 'est_hote', 'est_voyageur', 'est_hote_verifie', 'profil'],
                    'token',
                    'token_type',
                ],
            ])
            ->assertJsonPath('data.user.role', 'voyageur')
            ->assertJsonPath('data.user.est_voyageur', true)
            ->assertJsonPath('data.user.est_hote', false);

        $this->assertNotEmpty($response->json('data.token'));

        $this->assertDatabaseHas('utilisateurs', [
            'email' => 'omar@example.com',
            'est_voyageur' => true,
            'est_hote' => false,
            'est_actif' => true,
        ]);

        $this->assertDatabaseHas('profils', [
            'id_utilisateur' => $response->json('data.user.id_utilisateur'),
        ]);

        $user = User::where('email', 'omar@example.com')->firstOrFail();
        $this->assertNotSame('Password123', $user->mot_de_passe);
        $this->assertTrue(Hash::check('Password123', $user->getAuthPassword()));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_inscription_hote_active_le_drapeau_hote(): void
    {
        $response = $this->postJson('/api/auth/register', $this->payload([
            'email' => 'hote@example.com',
            'role' => 'hote',
            'telephone' => '0623456789',
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.user.role', 'hote')
            ->assertJsonPath('data.user.est_hote', true)
            ->assertJsonPath('data.user.est_voyageur', false);

        $this->assertDatabaseHas('utilisateurs', [
            'email' => 'hote@example.com',
            'est_hote' => true,
            'est_voyageur' => false,
        ]);
    }

    public function test_inscription_hote_exige_un_telephone(): void
    {
        $this->postJson('/api/auth/register', $this->payload([
            'role' => 'hote',
            'telephone' => null,
        ]))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ECHOUEE')
            ->assertJsonStructure(['errors' => ['telephone']]);
    }

    public function test_inscription_refuse_un_email_deja_utilise(): void
    {
        User::factory()->create(['email' => 'omar@example.com']);

        $this->postJson('/api/auth/register', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_connexion_retourne_un_token_sanctum(): void
    {
        User::factory()->create([
            'email' => 'omar@example.com',
            'mot_de_passe' => 'Password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'omar@example.com',
            'password' => 'Password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'omar@example.com')
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_connexion_refuse_un_mauvais_mot_de_passe(): void
    {
        User::factory()->create([
            'email' => 'omar@example.com',
            'mot_de_passe' => 'Password123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'omar@example.com',
            'password' => 'MauvaisMotDePasse',
        ])
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'IDENTIFIANTS_INVALIDES');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_connexion_refuse_un_compte_desactive(): void
    {
        User::factory()->inactif()->create([
            'email' => 'omar@example.com',
            'mot_de_passe' => 'Password123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'omar@example.com',
            'password' => 'Password123',
        ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'COMPTE_INACTIF');
    }

    public function test_un_administrateur_obtient_le_role_admin(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'mot_de_passe' => 'Password123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'Password123',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonPath('data.user.est_admin', true);
    }

    public function test_me_retourne_le_profil_et_la_verification(): void
    {
        $user = User::factory()->hote()->create();
        $profil = \App\Models\Utilisateurs\Profil::factory()->create([
            'id_utilisateur' => $user->getKey(),
        ]);
        VerificationIdentite::factory()->validee()->create([
            'id_utilisateur' => $user->getKey(),
        ]);

        $token = $user->createToken('angular')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.id_utilisateur', $user->getKey())
            ->assertJsonPath('data.user.est_hote', true)
            ->assertJsonPath('data.user.est_hote_verifie', true)
            ->assertJsonPath('data.user.verification_identite.statut', 'validee')
            ->assertJsonPath('data.user.profil.id_profil', $profil->id_profil);
    }

    public function test_me_exige_un_token(): void
    {
        $this->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'NON_AUTHENTIFIE');
    }

    public function test_deconnexion_revoque_le_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('angular')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Le guard Sanctum met l'utilisateur en cache pour l'instance d'application
        // du test : on le vide pour simuler une nouvelle requête HTTP.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_la_connexion_est_limitee_en_tentatives(): void
    {
        User::factory()->create([
            'email' => 'omar@example.com',
            'mot_de_passe' => 'Password123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'omar@example.com',
                'password' => 'MauvaisMotDePasse',
            ])->assertStatus(401);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'omar@example.com',
            'password' => 'MauvaisMotDePasse',
        ])
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'TROP_DE_TENTATIVES');
    }
}
