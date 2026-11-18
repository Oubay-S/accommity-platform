<?php

namespace Tests\Feature;

use App\Models\Administration\TicketLitige;
use App\Models\Evaluations\Evaluation;
use App\Models\Utilisateurs\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EvaluationSignalementTest extends TestCase
{
    use RefreshDatabase;

    private function evaluation(): Evaluation
    {
        return Evaluation::factory()->create();
    }

    public function test_un_utilisateur_peut_signaler_un_avis_et_un_ticket_est_cree(): void
    {
        $evaluation = $this->evaluation();
        $declarant = User::factory()->create();

        Sanctum::actingAs($declarant);

        $this->postJson('/api/evaluations/'.$evaluation->getKey().'/signaler', [
            'motif' => 'Ce commentaire contient des propos inappropriés.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.ticket.statut', 'en_cours');

        $this->assertDatabaseHas('ticket_litiges', [
            'id_evaluation' => $evaluation->getKey(),
            'id_declarant' => $declarant->getKey(),
            'id_reservation' => $evaluation->id_reservation,
            'statut' => 'en_cours',
        ]);

        $evaluation->refresh();
        $this->assertTrue($evaluation->est_signale);
        $this->assertSame('Ce commentaire contient des propos inappropriés.', $evaluation->motif_signalement);
    }

    public function test_on_ne_peut_pas_signaler_son_propre_avis(): void
    {
        $evaluation = $this->evaluation();

        Sanctum::actingAs(User::query()->findOrFail($evaluation->id_auteur));

        $this->postJson('/api/evaluations/'.$evaluation->getKey().'/signaler', [
            'motif' => 'Ce commentaire contient des propos inappropriés.',
        ])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'AUTO_SIGNALEMENT');
    }

    public function test_un_meme_utilisateur_ne_peut_pas_signaler_deux_fois_le_meme_avis(): void
    {
        $evaluation = $this->evaluation();
        $declarant = User::factory()->create();

        TicketLitige::query()->create([
            'id_evaluation' => $evaluation->getKey(),
            'id_declarant' => $declarant->getKey(),
            'id_reservation' => $evaluation->id_reservation,
            'motif' => 'Premier signalement.',
            'statut' => 'en_cours',
        ]);

        Sanctum::actingAs($declarant);

        $this->postJson('/api/evaluations/'.$evaluation->getKey().'/signaler', [
            'motif' => 'Deuxième signalement identique.',
        ])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'AVIS_DEJA_SIGNE');
    }

    public function test_le_signalement_d_un_avis_inconnu_retourne_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/evaluations/01900000-0000-7000-8000-000000000000/signaler', [
            'motif' => 'Ce commentaire contient des propos inappropriés.',
        ])
            ->assertNotFound()
            ->assertJsonPath('error_code', 'AVIS_INTROUVABLE');
    }

    public function test_le_motif_du_signalement_est_obligatoire_et_suffisamment_long(): void
    {
        $evaluation = $this->evaluation();

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/evaluations/'.$evaluation->getKey().'/signaler', [
            'motif' => 'Court',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['motif']);
    }

    public function test_le_signalement_exige_une_authentification(): void
    {
        $evaluation = $this->evaluation();

        $this->postJson('/api/evaluations/'.$evaluation->getKey().'/signaler', [
            'motif' => 'Ce commentaire contient des propos inappropriés.',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'NON_AUTHENTIFIE');
    }
}
