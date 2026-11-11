<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calendriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annonce_id')->constrained('annonces')->cascadeOnDelete();
            $table->date('date')->index();
            $table->boolean('est_disponible')->default(true);
            $table->decimal('prix_specifique', 10, 2)->nullable();
            $table->timestamps();
            $table->unique(['annonce_id', 'date']);
        });
    }

    public function down(): void { Schema::dropIfExists('calendriers'); }
};
