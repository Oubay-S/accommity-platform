<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->string('reference_transaction')->unique();
            $table->decimal('montant', 10, 2);
            $table->string('mode_paiement')->default('carte_bancaire');
            $table->string('statut')->default('succes');
            $table->timestamp('date_paiement')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('paiements'); }
};
