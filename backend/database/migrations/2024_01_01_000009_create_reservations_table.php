<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voyageur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('annonce_id')->constrained('annonces')->cascadeOnDelete();
            $table->date('date_arrivee');
            $table->date('date_depart');
            $table->integer('nombre_voyageurs')->default(1);
            $table->decimal('prix_total', 10, 2);
            $table->decimal('frais_service', 10, 2)->default(0);
            $table->string('statut')->default('en_attente');
            $table->text('motif_refus')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('reservations'); }
};
