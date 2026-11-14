<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Métadonnées de notification (package PK_Notifications).
 *
 *  - id_reservation : corrélation avec la réservation concernée, utilisée
 *    pour dédupliquer les rappels RO07 (un seul rappel par échéance).
 *  - data           : métadonnées de deep-link pour Angular
 *    (ex. {"reservation_id": "...", "annonce_id": "..."}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->uuid('id_reservation')->nullable();
            $table->jsonb('data')->nullable();
            $table->index('id_reservation');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            Schema::table('notifications', function (Blueprint $table) {
                $table->foreign('id_reservation')
                    ->references('id_reservation')
                    ->on('reservations')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropForeign(['id_reservation']);
            });
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['id_reservation']);
            $table->dropColumn(['id_reservation', 'data']);
        });
    }
};
