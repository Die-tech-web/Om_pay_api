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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->uuid('compte_id');
            $table->enum('type', ['depot', 'retrait', 'paiement', 'transfert', 'frais']);
            $table->decimal('montant', 15, 2);
            $table->string('libelle')->nullable();
            $table->string('description')->nullable();
            $table->string('numero_destinataire')->nullable();
            $table->string('code_marchand')->nullable();
            $table->string('reference')->nullable();
            $table->datetime('date_transaction');
            $table->enum('statut', ['en_attente', 'validee', 'annulee'])->default('en_attente');
            $table->timestamps();

            $table->foreign('compte_id')->references('id')->on('comptes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
