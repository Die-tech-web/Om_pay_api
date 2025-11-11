<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->uuid('id_client');
            $table->string('numero_compte')->unique();
            $table->string('code_pin');
            $table->enum('type', ['client', 'marchand'])->default('client');
            $table->date('date_creation');
            $table->enum('statut', ['actif', 'bloque', 'ferme'])->default('actif');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('id_client')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
