<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipements', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('icone')->nullable();
            $table->timestamps();
        });

        Schema::create('annonce_equipement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annonce_id')->constrained('annonces')->cascadeOnDelete();
            $table->foreignId('equipement_id')->constrained('equipements')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annonce_equipement');
        Schema::dropIfExists('equipements');
    }
};
