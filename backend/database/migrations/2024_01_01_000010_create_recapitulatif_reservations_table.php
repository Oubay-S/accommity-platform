<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recapitulatif_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->decimal('sous_total', 10, 2);
            $table->decimal('frais_service', 10, 2);
            $table->decimal('total', 10, 2);
            $table->json('details_calcul')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('recapitulatif_reservations'); }
};
