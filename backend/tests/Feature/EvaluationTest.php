<?php

namespace Tests\Feature;

use App\Enums\StatutReservation;
use App\Enums\TypeNotification;
use App\Models\Annonces\Annonce;
use App\Models\Evaluations\Evaluation;
use App\Models\Evaluations\NoteDetaillee;
use App\Models\Notifications\Notification;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\Profil;
use App\Models\Utilisateurs\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EvaluationTest extends TestCase
{
    use RefreshDatabase;

    private User $voyageur;

    private User $hote;

    private Annonce $annonce;

    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->voyageur = User::factory()->voyageur()->create();
        $this->hote = User::factory()->hote()->create();
        $this->annonce = Annonce::factory()->create(['id_hote' => $this->hote->getKey()]);
        $this->reservation = Reservation::factory()->create([
            'id_annonce' => $this->annonce->getKey(),
            'id_hote' => $this->hote->getKey(),
            'id_voyageur' => $this->voyageur->getKey(),
            'statut' => StatutReservation::TERMINEE,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'id_reservation' => $this->reservation->getKey(),
            'note' => 5,
            'commentaire' => 'Un séjour excellent, je recommande vivement.',
            'notes_detaillees' => [
                'proprete' => 5,
                'communication' => 5,
                'emplacement' => 4,
                'rapport_qualite_prix' => 5,
                'exactitude' => 5,
            ],
        ], $overrides);
    }

    public function test_un_voyageur_peut_evaluer_un_sejour_termine(): void
    {
        Sanctum::actingAs($this->voyageur);

        $this->postJson('/api/evaluations', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.evaluation.note', 5)
            ->assertJsonPath('data.evaluation.auteur.id_utilisateur', $this->voyageur->getKey())
            ->assertJsonPath('data.evaluation.notes_detaillees.proprete', 5)
            ->assertJsonPath('data.evaluation.notes_detaillees.emplacement', 4);

        $evaluation = Evaluation::query()->firstOrFail();

        $this->assertSame($this->reservation->getKey(), $evaluation->id_reservation);
        $this->assertSame($this->hote->getKey(), $evaluation->id_cible);
        $this->assertSame($this->annonce->getKey(), $evaluation->id_annonce);
        $this->assertSame('voyageur', $evaluation->type_auteur);

        $this->assertDatabaseHas('note_detaillees', [
            'id_evaluation' => $evaluation->getKey(),
            'proprete' => 5,
            'emplacement' => 4,
        ]);

        $profil = Profil::query()->where('id_utilisateur', $this->hote->getKey())->firstOrFail();
        $this->assertSame(5.0, (float) $profil->note_moyenne);

        $this->assertTrue(Notification::query()
            ->where('id_utilisateur', $this->hote->getKey())
            ->where('type_alerte', TypeNotification::AVIS->value)
            ->exists());
    }

    public function test_un_hote_peut_evaluer_un_sejour_termine_sans_notes_detaillees(): void
    {
        Sanctum::actingAs($this->hote);

        $this->postJson('/api/evaluations', $this->payload([
            'note' => 4,
            'commentaire' => 'Voyageur respectueux et ponctuel.',
            'notes_detaillees' => null,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.evaluation.notes_detaillees', null);

        $evaluation = Evaluation::query()->firstOrFail();

        $this->assertSame($this->voyageur->getKey(), $evaluation->id_cible);
        $this->assertSame('hote', $evaluation->type_auteur);
        $this->assertSame(0, NoteDetaillee::query()->count());

        $profil = Profil::query()->where('id_utilisateur', $this->voyageur->getKey())->firstOrFail();
        $this->assertSame(4.0, (float) $profil->note_moyenne);
    }

    public function test_un_avis_est_refuse_si_la_reservation_n_est_pas_terminee(): void
    {
        $this->reservation->update(['statut' => StatutReservation::CONFIRMEE]);

        Sanctum::actingAs($this->voyageur);

        $this->postJson('/api/evaluations', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'RESERVATION_NON_TERMINEE');
    }

    public function test_un_utilisateur_etranger_ne_peut_pas_evaluer(): void
    {
        Sanctum::actingAs(User::factory()->voyageur()->create());

        $this->postJson('/api/evaluations', $this->payload())
            ->assertForbidden()
            ->assertJsonPath('error_code', 'NON_PARTICIPANT');
    }

    public function test_un_avis_ne_peut_etre_depose_qu_une_seule_fois(): void
    {
        Sanctum::actingAs($this->voyageur);

        $this->postJson('/api/evaluations', $this->payload())->assertCreated();

        $this->postJson('/api/evaluations', $this->payload())
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'AVIS_DEJA_DEPOSE');
    }

    public function test_les_notes_detaillees_sont_obligatoires_pour_un_voyageur(): void
    {
        Sanctum::actingAs($this->voyageur);

        $this->postJson('/api/evaluations', $this->payload(['notes_detaillees' => null]))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'NOTES_DETAILLEES_REQUISES');
    }

    public function test_la_note_et_le_commentaire_sont_valides(): void
    {
        Sanctum::actingAs($this->voyageur);

        $this->postJson('/api/evaluations', $this->payload([
            'note' => 6,
            'commentaire' => 'Court',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['note', 'commentaire']);
    }

    public function test_la_liste_publique_ne_contient_que_les_avis_voyageurs(): void
    {
        $autreVoyageur = User::factory()->voyageur()->create();

        $avisVoyageur = Evaluation::factory()->create([
            'id_reservation' => $this->reservation->getKey(),
            'id_auteur' => $this->voyageur->getKey(),
            'id_cible' => $this->hote->getKey(),
            'id_annonce' => $this->annonce->getKey(),
            'type_auteur' => 'voyageur',
            'note' => 4,
        ]);
        $avisVoyageur->notesDetaillees()->create([
            'proprete' => 4,
            'communication' => 5,
            'emplacement' => 4,
            'rapport_qualite_prix' => 4,
            'exactitude' => 4,
        ]);

        Evaluation::factory()->create([
            'id_reservation' => $this->reservation->getKey(),
            'id_auteur' => $this->hote->getKey(),
            'id_cible' => $this->voyageur->getKey(),
            'id_annonce' => $this->annonce->getKey(),
            'type_auteur' => 'hote',
        ]);

        Evaluation::factory()->create([
            'id_annonce' => $this->annonce->getKey(),
            'id_auteur' => $autreVoyageur->getKey(),
            'id_cible' => $this->hote->getKey(),
            'type_auteur' => 'voyageur',
            'note' => 5,
        ]);

        $this->getJson('/api/annonces/'.$this->annonce->getKey().'/avis')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.statistiques.nb_avis', 2)
            ->assertJsonPath('data.statistiques.note_moyenne', 4.5)
            ->assertJsonPath('data.statistiques.repartition.4', 1)
            ->assertJsonPath('data.statistiques.repartition.5', 1)
            ->assertJsonMissingPath('data.items.0.est_signale');
    }

    public function test_les_avis_d_une_annonce_inconnue_retournent_404(): void
    {
        $this->getJson('/api/annonces/01900000-0000-7000-8000-000000000000/avis')
            ->assertNotFound()
            ->assertJsonPath('error_code', 'ANNONCE_INTROUVABLE');
    }

    public function test_les_avis_recus_et_donnes_sont_pagines(): void
    {
        Evaluation::factory()->create([
            'id_reservation' => $this->reservation->getKey(),
            'id_auteur' => $this->voyageur->getKey(),
            'id_cible' => $this->hote->getKey(),
            'id_annonce' => $this->annonce->getKey(),
            'type_auteur' => 'voyageur',
        ]);

        Sanctum::actingAs($this->hote);

        $this->getJson('/api/evaluations/recues')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.statistiques.nb_avis', 1);

        $this->getJson('/api/evaluations/donnees')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 0);
    }

    public function test_les_sejours_a_evaluer_excluent_les_avis_deja_deposes(): void
    {
        Sanctum::actingAs($this->voyageur);

        $this->getJson('/api/evaluations/a-evaluer')
            ->assertOk()
            ->assertJsonPath('data.items.0.id_reservation', $this->reservation->getKey())
            ->assertJsonPath('data.items.0.role', 'voyageur');

        $this->postJson('/api/evaluations', $this->payload())->assertCreated();

        $this->getJson('/api/evaluations/a-evaluer')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }
}
