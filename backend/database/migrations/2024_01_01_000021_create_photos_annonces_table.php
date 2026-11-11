<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('photos_annonces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annonce_id')->constrained('annonces')->cascadeOnDelete();
            $table->string('url');
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('photos_annonces'); }
};
