<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('politique_annulations', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description');
            $table->integer('delai_heures');
            $table->integer('pourcentage_remboursement');
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('politique_annulations'); }
};
