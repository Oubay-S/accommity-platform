<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paiement_id')->constrained('paiements')->cascadeOnDelete();
            $table->string('numero_recu')->unique();
            $table->string('pdf_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('recus'); }
};
