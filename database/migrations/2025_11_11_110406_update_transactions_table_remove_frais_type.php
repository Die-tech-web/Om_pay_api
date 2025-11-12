<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT transactions_type_check");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check CHECK (type IN ('depot', 'retrait', 'paiement', 'transfert', 'frais'))");
    }

    /**
     * Reverse the migrations.hello maintenant dans mon code tout marche , je veux une amelioration dans la creation de transactions lors de la reponse il faut ajouhello maintenant dans mon code tout marche , je veux une amelioration dans la creation de transactions lors de la reponse il faut ajouter un champs expediteur  dans la reponse donc il va recuperer le nom et prenom de lexpediteur ; voici un example il faut ajouter le champs de l'expediteur dans la reponse et dons si tout marche ca va s'afficher dans swagger aussiter un champs expediteur  dans la reponse donc il va recuperer le nom et prenom de lexpediteur ; voici un example il faut ajouter le champs de l'expediteur dans la reponse et dons si tout marche ca va s'afficher dans swagger aussi
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT transactions_type_check");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check CHECK (type IN ('depot', 'retrait', 'paiement', 'transfert', 'frais'))");
    }
};
