<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_litiges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plaignant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('defendeur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->string('objet');
            $table->text('description');
            $table->string('statut')->default('ouvert');
            $table->text('resolution')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('ticket_litiges'); }
};
