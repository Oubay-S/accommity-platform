<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('remboursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paiement_id')->constrained('paiements')->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->string('motif');
            $table->string('statut')->default('effectue');
            $table->timestamp('date_remboursement')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('remboursements'); }
};
