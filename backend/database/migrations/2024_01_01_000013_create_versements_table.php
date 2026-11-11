<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('versements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hote_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->string('statut')->default('en_attente');
            $table->timestamp('date_versement')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('versements'); }
};
