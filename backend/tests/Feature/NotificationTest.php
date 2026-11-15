<?php

namespace Tests\Feature;

use App\Enums\TypeNotification;
use App\Mail\NotificationEmail;
use App\Models\Notifications\Notification;
use App\Models\Utilisateurs\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Boîte de notifications in-app (RO06).
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function service(): NotificationService
    {
        return app(NotificationService::class);
    }

    public function test_index_retourne_uniquement_les_notifications_de_l_utilisateur(): void
    {
        $user = User::factory()->create();
        $autre = User::factory()->create();

        Notification::factory()->count(3)->create(['id_utilisateur' => $user->getKey()]);
        Notification::factory()->create(['id_utilisateur' => $autre->getKey()]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 3)
            ->assertJsonPath('data.non_lues', 3)
            ->assertJsonCount(3, 'data.items');
    }

    public function test_index_filtre_les_non_lues_et_pagine(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(7)->create(['id_utilisateur' => $user->getKey()]);
        Notification::factory()->count(2)->lue()->create(['id_utilisateur' => $user->getKey()]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications?par_page=5')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 9)
            ->assertJsonPath('data.pagination.per_page', 5)
            ->assertJsonPath('data.pagination.last_page', 2)
            ->assertJsonPath('data.non_lues', 7)
            ->assertJsonCount(5, 'data.items');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications?non_lues=1')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 7);
    }

    public function test_marquer_une_notification_comme_lue(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['id_utilisateur' => $user->getKey()]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/notifications/{$notification->id_notification}/lue")
            ->assertOk()
            ->assertJsonPath('data.notification.est_lue', true);

        $this->assertTrue($notification->fresh()->est_lue);
    }

    public function test_impossible_de_lire_la_notification_d_un_autre(): void
    {
        $user = User::factory()->create();
        $autre = User::factory()->create();
        $notification = Notification::factory()->create(['id_utilisateur' => $autre->getKey()]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/notifications/{$notification->id_notification}/lue")
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'INTROUVABLE');

        $this->assertFalse($notification->fresh()->est_lue);
    }

    public function test_marquer_toutes_les_notifications_comme_lues(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(4)->create(['id_utilisateur' => $user->getKey()]);
        Notification::factory()->create(['id_utilisateur' => User::factory()->create()->getKey()]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/notifications/tout-lu')
            ->assertOk()
            ->assertJsonPath('data.marquees', 4);

        $this->assertSame(0, $user->notifications()->nonLues()->count());
    }

    public function test_compteur_de_notifications_non_lues(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->create(['id_utilisateur' => $user->getKey()]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications/non-lues')
            ->assertOk()
            ->assertJsonPath('data.non_lues', 3);
    }

    public function test_les_notifications_exigent_une_authentification(): void
    {
        $this->getJson('/api/notifications')
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'NON_AUTHENTIFIE');
    }

    public function test_le_service_cree_une_notification_typee(): void
    {
        $user = User::factory()->create();

        $notification = $this->service()->notifier(
            $user,
            TypeNotification::SYSTEME,
            'Bienvenue',
            'Votre compte est actif.',
            null,
            ['cle' => 'valeur'],
        );

        $this->assertSame(TypeNotification::SYSTEME, $notification->type_alerte);
        $this->assertFalse($notification->est_lue);
        $this->assertSame(['cle' => 'valeur'], $notification->data);

        $this->assertDatabaseHas('notifications', [
            'id_notification' => $notification->id_notification,
            'type_alerte' => 'systeme',
            'est_lue' => false,
        ]);
    }

    public function test_le_service_envoie_un_email_si_demande(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->service()->notifier(
            $user,
            TypeNotification::SYSTEME,
            'Sujet du mail',
            'Corps du mail',
            null,
            [],
            true,
        );

        Mail::assertSent(NotificationEmail::class, function (NotificationEmail $mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->sujet === 'Sujet du mail';
        });
    }
}
