<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('verification_identites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type_document');
            $table->string('numero_document');
            $table->string('document_url');
            $table->string('statut')->default('en_attente');
            $table->text('motif_rejet')->nullable();
            $table->timestamp('verifie_a')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('verification_identites'); }
};
