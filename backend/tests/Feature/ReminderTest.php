<?php

namespace Tests\Feature;

use App\Models\Annonces\Annonce;
use App\Models\Notifications\Notification;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Rappels check-in RO07 (48 h hôte, 24 h voyageur).
 */
class ReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function reservation(User $hote, User $voyageur, int $jours): Reservation
    {
        $annonce = Annonce::factory()->create(['id_hote' => $hote->getKey()]);

        return Reservation::factory()
            ->arrivantDans($jours)
            ->create([
                'id_annonce' => $annonce->getKey(),
                'id_hote' => $hote->getKey(),
                'id_voyageur' => $voyageur->getKey(),
            ]);
    }

    private function envoyerRappels(): void
    {
        Artisan::call('reminders:send', ['--date' => now()->toDateString()]);
    }

    public function test_un_rappel_est_envoye_a_l_hote_48h_avant(): void
    {
        $hote = User::factory()->hote()->create();
        $voyageur = User::factory()->voyageur()->create();
        $reservation = $this->reservation($hote, $voyageur, 2);

        $this->envoyerRappels();

        $this->assertDatabaseHas('notifications', [
            'id_utilisateur' => $hote->getKey(),
            'id_reservation' => $reservation->getKey(),
            'type_alerte' => 'rappel',
            'titre' => 'Rappel : arrivée dans 48 heures',
        ]);

        // Le voyageur n'est pas notifié à 48 h.
        $this->assertDatabaseMissing('notifications', [
            'id_utilisateur' => $voyageur->getKey(),
            'id_reservation' => $reservation->getKey(),
        ]);
    }

    public function test_un_rappel_est_envoye_au_voyageur_24h_avant(): void
    {
        $hote = User::factory()->hote()->create();
        $voyageur = User::factory()->voyageur()->create();
        $reservation = $this->reservation($hote, $voyageur, 1);

        $this->envoyerRappels();

        $this->assertDatabaseHas('notifications', [
            'id_utilisateur' => $voyageur->getKey(),
            'id_reservation' => $reservation->getKey(),
            'type_alerte' => 'rappel',
            'titre' => 'Rappel : arrivée dans 24 heures',
        ]);
    }

    public function test_les_reservations_non_confirmees_sont_ignorees(): void
    {
        $hote = User::factory()->hote()->create();
        $voyageur = User::factory()->voyageur()->create();

        $annonce = Annonce::factory()->create(['id_hote' => $hote->getKey()]);
        Reservation::factory()
            ->enAttente()
            ->arrivantDans(2)
            ->create([
                'id_annonce' => $annonce->getKey(),
                'id_hote' => $hote->getKey(),
                'id_voyageur' => $voyageur->getKey(),
            ]);

        $this->envoyerRappels();

        $this->assertSame(0, Notification::count());
    }

    public function test_les_rappels_sont_idempotents(): void
    {
        $hote = User::factory()->hote()->create();
        $voyageur = User::factory()->voyageur()->create();
        $this->reservation($hote, $voyageur, 2);
        $this->reservation($hote, $voyageur, 1);

        $this->envoyerRappels();
        $this->envoyerRappels();

        $this->assertSame(2, Notification::count());
    }

    public function test_une_reservation_arrivant_plus_tard_n_est_pas_notifiee(): void
    {
        $hote = User::factory()->hote()->create();
        $voyageur = User::factory()->voyageur()->create();
        $this->reservation($hote, $voyageur, 5);

        $this->envoyerRappels();

        $this->assertSame(0, Notification::count());
    }
}
