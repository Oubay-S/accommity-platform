<?php

namespace Tests\Feature;

use App\Enums\TypeNotification;
use App\Models\Administration\Administrateur;
use App\Models\Notifications\Notification;
use App\Models\Utilisateurs\Profil;
use App\Models\Utilisateurs\User;
use App\Models\Utilisateurs\VerificationIdentite;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Verrouille l'alignement Modèle Eloquent ↔ schéma PostgreSQL v1.
 *
 * Ces tests échouent si quelqu'un réintroduit une convention Laravel par
 * défaut (`users`, `password`, `remember_token`, majuscules dans les enums…)
 * ou casse les factories.
 */
class SchemaAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_modeles_pointent_vers_le_schema_v1(): void
    {
        $user = User::factory()->create();

        $this->assertSame('utilisateurs', $user->getTable());
        $this->assertSame('id_utilisateur', $user->getKeyName());
        $this->assertFalse($user->getIncrementing());
        $this->assertTrue(Str::isUuid($user->getKey()));
        $this->assertSame('mot_de_passe', $user->getAuthPasswordName());
        $this->assertEmpty($user->getRememberTokenName());
        $this->assertArrayNotHasKey('mot_de_passe', $user->toArray());

        $this->assertSame('profils', (new Profil)->getTable());
        $this->assertSame('id_profil', (new Profil)->getKeyName());

        $this->assertSame('verification_identites', (new VerificationIdentite)->getTable());
        $this->assertSame('id_verification', (new VerificationIdentite)->getKeyName());

        $this->assertSame('administrateurs', (new Administrateur)->getTable());
        $this->assertSame('id_admin', (new Administrateur)->getKeyName());

        $this->assertSame('notifications', (new Notification)->getTable());
        $this->assertSame('id_notification', (new Notification)->getKeyName());
    }

    public function test_les_factories_respectent_la_contrainte_de_role(): void
    {
        foreach ([
            User::factory()->create(),
            User::factory()->hote()->create(),
            User::factory()->hoteEtVoyageur()->create(),
            User::factory()->inactif()->create(),
        ] as $user) {
            $this->assertTrue(
                $user->est_hote || $user->est_voyageur,
                'CHECK (est_hote OR est_voyageur) violé par la factory.'
            );
        }
    }

    public function test_la_base_refuse_un_utilisateur_sans_role(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La contrainte CHECK n\'est créée que sur PostgreSQL.');
        }

        $this->expectException(QueryException::class);

        User::factory()->create([
            'est_hote' => false,
            'est_voyageur' => false,
        ]);
    }

    public function test_un_administrateur_est_deduit_de_la_table_administrateurs(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->estAdmin());

        Administrateur::factory()->create(['id_utilisateur' => $user->getKey()]);

        $this->assertTrue($user->estAdmin());
        $this->assertDatabaseHas('administrateurs', [
            'id_utilisateur' => $user->getKey(),
        ]);
    }

    public function test_une_verification_validee_rend_l_hote_verifie(): void
    {
        $hote = User::factory()->hote()->create();

        VerificationIdentite::factory()->rejetee()->create([
            'id_utilisateur' => $hote->getKey(),
        ]);

        $this->assertFalse($hote->estHoteVerifie());

        VerificationIdentite::factory()->validee()->create([
            'id_utilisateur' => $hote->getKey(),
        ]);

        $this->assertTrue($hote->fresh()->estHoteVerifie());
    }

    public function test_le_profil_est_unique_par_utilisateur(): void
    {
        $user = User::factory()->create();

        Profil::factory()->create(['id_utilisateur' => $user->getKey()]);

        $this->expectException(QueryException::class);

        Profil::factory()->create(['id_utilisateur' => $user->getKey()]);
    }

    public function test_la_notification_est_typee_et_peut_etre_marquee_lue(): void
    {
        $user = User::factory()->create();

        $notification = Notification::factory()
            ->deType(TypeNotification::SYSTEME)
            ->create(['id_utilisateur' => $user->getKey()]);

        $this->assertInstanceOf(TypeNotification::class, $notification->type_alerte);
        $this->assertSame(TypeNotification::SYSTEME, $notification->type_alerte);
        $this->assertDatabaseHas('notifications', [
            'id_notification' => $notification->getKey(),
            'type_alerte' => 'systeme',
            'est_lue' => false,
        ]);

        $notification->marquerCommeLue();

        $this->assertTrue($notification->fresh()->est_lue);
    }

    public function test_la_base_refuse_un_type_de_notification_invalide(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La contrainte CHECK de type_alerte n\'existe que sur PostgreSQL.');
        }

        $user = User::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('notifications')->insert([
            'id_notification' => (string) Str::uuid(),
            'id_utilisateur' => $user->getKey(),
            'titre' => 'Test',
            'contenu' => 'Type invalide',
            'type_alerte' => 'type_inexistant',
            'est_lue' => false,
            'date_creation' => now(),
        ]);
    }
}
