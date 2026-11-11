<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('annonces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hote_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('categorie_id')->nullable()->constrained('categories_geographiques')->nullOnDelete();
            $table->foreignId('politique_id')->nullable()->constrained('politique_annulations')->nullOnDelete();
            $table->string('titre');
            $table->text('description');
            $table->string('type_logement');
            $table->string('adresse');
            $table->string('ville')->index();
            $table->string('code_postal')->nullable();
            $table->decimal('prix_par_nuit', 10, 2)->index();
            $table->integer('capacite_voyageurs')->default(1);
            $table->integer('nombre_chambres')->default(1);
            $table->integer('nombre_lits')->default(1);
            $table->integer('nombre_salles_bain')->default(1);
            $table->string('mode_reservation')->default('sur_demande');
            $table->string('statut')->default('en_attente');
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('annonces'); }
};
