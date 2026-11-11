<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dates_bloquees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annonce_id')->constrained('annonces')->cascadeOnDelete();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('motif')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('dates_bloquees'); }
};
