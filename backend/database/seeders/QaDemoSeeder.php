<?php

namespace Database\Seeders;

use App\Enums\ModeReservation;
use App\Enums\StatutAnnonce;
use App\Enums\StatutReservation;
use App\Models\Annonces\Annonce;
use App\Models\Evaluations\Evaluation;
use App\Models\Evaluations\NoteDetaillee;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\Profil;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration pour la recette manuelle et les tests E2E.
 * Ce seeder est idempotent et ne s'exécute qu'en environnement local.
 */
class QaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $annonce = Annonce::query()
            ->where('statut', StatutAnnonce::PUBLIEE->value)
            ->orderBy('id_annonce')
            ->first();

        if ($annonce === null) {
            return;
        }

        $voyageur = User::firstOrCreate(
            ['email' => 'voyageur.demo@accommity.ma'],
            [
                'nom' => 'El Amrani',
                'prenom' => 'Yasmine',
                'mot_de_passe' => 'Password123!',
                'telephone' => '0698765432',
                'est_hote' => false,
                'est_voyageur' => true,
                'est_actif' => true,
            ]
        );

        Profil::firstOrCreate(
            ['id_utilisateur' => $voyageur->getKey()],
            ['description' => 'Voyageuse de démonstration pour la recette Accommity.']
        );

        $sejourTermine = Reservation::firstOrCreate(
            [
                'id_annonce' => $annonce->getKey(),
                'id_voyageur' => $voyageur->getKey(),
                'statut' => StatutReservation::TERMINEE->value,
            ],
            [
                'id_hote' => $annonce->id_hote,
                'id_politique' => $annonce->id_politique,
                'date_arrivee' => now()->subDays(14)->toDateString(),
                'date_depart' => now()->subDays(10)->toDateString(),
                'nb_voyageurs' => 2,
                'mode_reservation' => ModeReservation::INSTANTANEE->value,
                'montant_total' => 2000,
                'frais_service' => 240,
                'date_confirmation' => now()->subDays(20),
            ]
        );

        $evaluation = Evaluation::firstOrCreate(
            [
                'id_reservation' => $sejourTermine->getKey(),
                'id_auteur' => $voyageur->getKey(),
            ],
            [
                'id_cible' => $annonce->id_hote,
                'id_annonce' => $annonce->getKey(),
                'type_auteur' => 'voyageur',
                'note' => 5,
                'commentaire' => 'Accueil chaleureux, riad magnifique et parfaitement conforme à l’annonce. Je recommande sans hésiter.',
                'est_signale' => false,
            ]
        );

        if ($evaluation->wasRecentlyCreated) {
            NoteDetaillee::query()->create([
                'id_evaluation' => $evaluation->getKey(),
                'proprete' => 5,
                'communication' => 5,
                'emplacement' => 4,
                'rapport_qualite_prix' => 5,
                'exactitude' => 5,
            ]);
        }

        $moyenneHote = (float) Evaluation::query()
            ->where('id_cible', $annonce->id_hote)
            ->avg('note');

        Profil::updateOrCreate(
            ['id_utilisateur' => $annonce->id_hote],
            ['note_moyenne' => round($moyenneHote, 2)]
        );

        Reservation::firstOrCreate(
            [
                'id_annonce' => $annonce->getKey(),
                'id_voyageur' => $voyageur->getKey(),
                'statut' => StatutReservation::EN_ATTENTE->value,
            ],
            [
                'id_hote' => $annonce->id_hote,
                'id_politique' => $annonce->id_politique,
                'date_arrivee' => now()->addDays(7)->toDateString(),
                'date_depart' => now()->addDays(10)->toDateString(),
                'nb_voyageurs' => 2,
                'mode_reservation' => ModeReservation::SUR_DEMANDE->value,
                'montant_total' => 2500,
                'frais_service' => 300,
            ]
        );
    }
}
