<?php

namespace Tests\Feature;

use App\Models\Utilisateurs\User;
use App\Models\Utilisateurs\VerificationIdentite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Back-office — validation des identités hôtes (RG03).
 */
class AdminVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Mail::fake();
    }

    private function dossier(User $hote, string $statut = VerificationIdentite::STATUT_EN_COURS): VerificationIdentite
    {
        return match ($statut) {
            VerificationIdentite::STATUT_VALIDEE => VerificationIdentite::factory()->validee()->create([
                'id_utilisateur' => $hote->getKey(),
            ]),
            VerificationIdentite::STATUT_REJETEE => VerificationIdentite::factory()->rejetee()->create([
                'id_utilisateur' => $hote->getKey(),
            ]),
            default => VerificationIdentite::factory()->enCours()->create([
                'id_utilisateur' => $hote->getKey(),
            ]),
        };
    }

    public function test_un_administrateur_liste_les_dossiers_en_cours(): void
    {
        $admin = User::factory()->admin()->create();
        $hote = User::factory()->hote()->create();
        $this->dossier($hote);
        $this->dossier(User::factory()->hote()->create(), VerificationIdentite::STATUT_VALIDEE);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/verifications')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.statut', 'en_cours')
            ->assertJsonPath('data.items.0.utilisateur.email', $hote->email);
    }

    public function test_un_administrateur_valide_une_identite_et_notifie_l_hote(): void
    {
        $admin = User::factory()->admin()->create();
        $hote = User::factory()->hote()->create();
        $verification = $this->dossier($hote);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/verifications/{$verification->id_verification}/valider")
            ->assertOk()
            ->assertJsonPath('data.verification.statut', 'validee')
            ->assertJsonPath('data.verification.utilisateur.email', $hote->email);

        $this->assertTrue($hote->fresh()->estHoteVerifie());
        $this->assertDatabaseHas('notifications', [
            'id_utilisateur' => $hote->getKey(),
            'type_alerte' => 'systeme',
            'titre' => 'Identité vérifiée',
        ]);
    }

    public function test_un_administrateur_rejette_avec_un_motif(): void
    {
        $admin = User::factory()->admin()->create();
        $hote = User::factory()->hote()->create();
        $verification = $this->dossier($hote);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/verifications/{$verification->id_verification}/rejeter", [
                'motif' => 'Scan du verso illisible.',
            ])
            ->assertOk()
            ->assertJsonPath('data.verification.statut', 'rejetee')
            ->assertJsonPath('data.verification.motif_rejet', 'Scan du verso illisible.');

        $this->assertFalse($hote->fresh()->estHoteVerifie());
        $this->assertDatabaseHas('notifications', [
            'id_utilisateur' => $hote->getKey(),
            'titre' => 'Vérification d\'identité rejetée',
        ]);
    }

    public function test_le_rejet_exige_un_motif(): void
    {
        $admin = User::factory()->admin()->create();
        $verification = $this->dossier(User::factory()->hote()->create());

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/verifications/{$verification->id_verification}/rejeter", [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['motif']]);
    }

    public function test_un_non_administrateur_est_refuse(): void
    {
        $user = User::factory()->hote()->create();
        $verification = $this->dossier(User::factory()->hote()->create());

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/verifications')
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/admin/verifications/{$verification->id_verification}/valider")
            ->assertStatus(403);

        $this->assertSame(
            VerificationIdentite::STATUT_EN_COURS,
            $verification->fresh()->statut,
        );
    }

    public function test_le_document_prive_est_telechargeable_par_l_administrateur(): void
    {
        $admin = User::factory()->admin()->create();
        $hote = User::factory()->hote()->create();
        $verification = VerificationIdentite::factory()->enCours()->create([
            'id_utilisateur' => $hote->getKey(),
            'chemin_document' => 'verifications/cin.pdf',
        ]);
        Storage::disk('local')->put('verifications/cin.pdf', 'contenu-cin');

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/admin/verifications/{$verification->id_verification}/document");

        $response->assertOk();
        $this->assertSame('contenu-cin', $response->streamedContent());
    }

    public function test_le_document_absent_renvoie_404(): void
    {
        $admin = User::factory()->admin()->create();
        $verification = VerificationIdentite::factory()->enCours()->create([
            'id_utilisateur' => User::factory()->hote()->create()->getKey(),
            'chemin_document' => 'verifications/absent.pdf',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/verifications/{$verification->id_verification}/document")
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'DOCUMENT_INTROUVABLE');
    }

    public function test_valider_un_dossier_inexistant_renvoie_404(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/verifications/00000000-0000-4000-8000-000000000000/valider')
            ->assertStatus(404);
    }

    public function test_le_demandeur_voit_son_statut_sans_les_donnees_admin(): void
    {
        $hote = User::factory()->hote()->create();
        $this->dossier($hote);

        $this->actingAs($hote, 'sanctum')
            ->getJson('/api/profil/verification/statut')
            ->assertOk()
            ->assertJsonPath('data.verification.statut', 'en_cours')
            ->assertJsonMissingPath('data.verification.chemin_document')
            ->assertJsonMissingPath('data.verification.utilisateur');
    }
}
