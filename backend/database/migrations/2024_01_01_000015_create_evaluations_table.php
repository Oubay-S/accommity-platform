<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auteur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('annonce_id')->nullable()->constrained('annonces')->nullOnDelete();
            $table->integer('note_globale');
            $table->text('commentaire');
            $table->boolean('est_signale')->default(false);
            $table->boolean('est_modere')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('evaluations'); }
};
