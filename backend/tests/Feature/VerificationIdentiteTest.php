<?php

namespace Tests\Feature;

use App\Exceptions\ApiException;
use App\Models\Utilisateurs\User;
use App\Models\Utilisateurs\VerificationIdentite;
use App\Services\Utilisateurs\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Vérification d'identité des hôtes (RG03, Figure 68).
 */
class VerificationIdentiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Mail::fake();
    }

    private function service(): VerificationService
    {
        return app(VerificationService::class);
    }

    private function document(): UploadedFile
    {
        return UploadedFile::fake()->create('cin.pdf', 200)->mimeType('application/pdf');
    }

    private function soumettre(User $user)
    {
        return $this->actingAs($user, 'sanctum')->post('/api/profil/verification', [
            'type_piece' => 'cin',
            'document' => $this->document(),
        ], ['Accept' => 'application/json']);
    }

    public function test_un_hote_peut_soumettre_un_document(): void
    {
        $hote = User::factory()->hote()->create();

        $this->soumettre($hote)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.verification.statut', 'en_cours')
            ->assertJsonPath('data.verification.type_piece', 'cin');

        $verification = $hote->verifications()->firstOrFail();

        Storage::disk('local')->assertExists($verification->chemin_document);
        Storage::disk('public')->assertMissing($verification->chemin_document);
        $this->assertNull($verification->date_traitement);
        $this->assertNull($verification->motif_rejet);
    }

    public function test_un_voyageur_ne_peut_pas_soumettre(): void
    {
        $voyageur = User::factory()->voyageur()->create();

        $this->soumettre($voyageur)
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'HOTE_REQUIS');
    }

    public function test_une_seule_demande_en_cours_a_la_fois(): void
    {
        $hote = User::factory()->hote()->create();

        $this->soumettre($hote)->assertCreated();

        $this->soumettre($hote)
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'VERIFICATION_EN_COURS');

        $this->assertSame(1, $hote->verifications()->count());
    }

    public function test_le_statut_retourne_la_derniere_demande(): void
    {
        $hote = User::factory()->hote()->create();
        VerificationIdentite::factory()->rejetee()->create([
            'id_utilisateur' => $hote->getKey(),
        ]);

        $this->actingAs($hote, 'sanctum')
            ->getJson('/api/profil/verification/statut')
            ->assertOk()
            ->assertJsonPath('data.verification.statut', 'rejetee')
            ->assertJsonPath('data.verification.motif_rejet', 'Document illisible.');
    }

    public function test_le_statut_sans_demande_est_null(): void
    {
        $hote = User::factory()->hote()->create();

        $this->actingAs($hote, 'sanctum')
            ->getJson('/api/profil/verification/statut')
            ->assertOk()
            ->assertJsonPath('data.verification', null);
    }

    public function test_un_administrateur_valide_l_identite(): void
    {
        $admin = User::factory()->admin()->create();
        $hote = User::factory()->hote()->create();
        $verification = VerificationIdentite::factory()->enCours()->create([
            'id_utilisateur' => $hote->getKey(),
        ]);

        $this->assertFalse($hote->estHoteVerifie());

        $validee = $this->service()->valider($verification->id_verification, $admin);

        $this->assertSame('validee', $validee->statut);
        $this->assertNotNull($validee->id_admin_traitant);
        $this->assertNotNull($validee->date_traitement);
        $this->assertNull($validee->motif_rejet);
        $this->assertTrue($hote->fresh()->estHoteVerifie());

        // RG03 / RO06 : l'hôte reçoit une notification in-app.
        $this->assertDatabaseHas('notifications', [
            'id_utilisateur' => $hote->getKey(),
            'type_alerte' => 'systeme',
            'titre' => 'Identité vérifiée',
            'est_lue' => false,
        ]);
    }

    public function test_valider_exige_un_administrateur(): void
    {
        $hote = User::factory()->hote()->create();
        $verification = VerificationIdentite::factory()->enCours()->create([
            'id_utilisateur' => $hote->getKey(),
        ]);

        $this->expectException(ApiException::class);

        $this->service()->valider($verification->id_verification, User::factory()->create());
    }

    public function test_rejeter_exige_un_motif(): void
    {
        $admin = User::factory()->admin()->create();
        $hote = User::factory()->hote()->create();
        $verification = VerificationIdentite::factory()->enCours()->create([
            'id_utilisateur' => $hote->getKey(),
        ]);

        $this->expectException(ApiException::class);

        $this->service()->rejeter($verification->id_verification, '   ', $admin);
    }

    public function test_le_rejet_enregistre_le_motif(): void
    {
        $admin = User::factory()->admin()->create();
        $hote = User::factory()->hote()->create();
        $verification = VerificationIdentite::factory()->enCours()->create([
            'id_utilisateur' => $hote->getKey(),
        ]);

        $rejetee = $this->service()->rejeter(
            $verification->id_verification,
            'Photo floue, merci de renvoyer le document.',
            $admin,
        );

        $this->assertSame('rejetee', $rejetee->statut);
        $this->assertSame('Photo floue, merci de renvoyer le document.', $rejetee->motif_rejet);
        $this->assertFalse($hote->fresh()->estHoteVerifie());

        $this->assertDatabaseHas('notifications', [
            'id_utilisateur' => $hote->getKey(),
            'type_alerte' => 'systeme',
            'titre' => 'Vérification d\'identité rejetée',
        ]);
    }

    public function test_une_resoumission_est_possible_apres_un_rejet(): void
    {
        $hote = User::factory()->hote()->create();
        VerificationIdentite::factory()->rejetee()->create([
            'id_utilisateur' => $hote->getKey(),
        ]);

        $this->soumettre($hote)->assertCreated();

        $this->assertSame(2, $hote->verifications()->count());
        $this->assertSame(1, $hote->verifications()->enCours()->count());
    }

    public function test_une_resoumission_est_refusee_apres_validation(): void
    {
        $hote = User::factory()->hote()->create();
        VerificationIdentite::factory()->validee()->create([
            'id_utilisateur' => $hote->getKey(),
        ]);

        $this->soumettre($hote)
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'DEJA_VERIFIE');
    }

    public function test_le_document_est_telechargeable_par_le_service(): void
    {
        $hote = User::factory()->hote()->create();
        $verification = VerificationIdentite::factory()->enCours()->create([
            'id_utilisateur' => $hote->getKey(),
            'chemin_document' => 'verifications/cin.pdf',
        ]);
        Storage::disk('local')->put('verifications/cin.pdf', 'contenu');

        $response = $this->service()->telecharger($verification);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_la_verification_exige_une_authentification(): void
    {
        $this->postJson('/api/profil/verification', ['type_piece' => 'cin'])
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'NON_AUTHENTIFIE');
    }
}
