<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $transactionService;
    private User $user1;
    private User $user2;
    private Compte $compte1;
    private Compte $compte2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionService = new TransactionService();

        // Créer des utilisateurs de test
        $this->user1 = User::create([
            'nom' => 'User 1',
            'prenom' => 'Test',
            'email' => 'user1@test.com',
            'telephone' => '123456789',
            'role' => 'client',
        ]);

        $this->user2 = User::create([
            'nom' => 'User 2',
            'prenom' => 'Test',
            'email' => 'user2@test.com',
            'telephone' => '987654321',
            'role' => 'client',
        ]);

        // Créer des comptes de test
        $this->compte1 = Compte::create([
            'id_client' => $this->user1->id,
            'numero_compte' => 'ACC001',
            'code_pin' => '1234',
            'type' => 'client',
            'date_creation' => now(),
            'statut' => 'actif',
        ]);

        $this->compte2 = Compte::create([
            'id_client' => $this->user2->id,
            'numero_compte' => 'ACC002',
            'code_pin' => '1234',
            'type' => 'client',
            'date_creation' => now(),
            'statut' => 'actif',
        ]);

        // Ajouter un dépôt initial au compte 1
        $this->compte1->transactions()->create([
            'type' => 'depot',
            'montant' => 50000,
            'libelle' => 'Dépôt initial',
            'date_transaction' => now(),
            'statut' => 'validee',
        ]);
    }

    /** @test */
    public function test_calcul_solde_apres_depot()
    {
        $solde = $this->compte1->getSoldeAttribute();
        $this->assertEquals(50000.00, $solde);
    }

    /** @test */
    public function test_transfer_avec_frais()
    {
        // Effectuer un transfert de 10000 avec frais
        $transaction = $this->transactionService->createTransaction($this->compte1, [
            'type' => 'transfert',
            'montant_transaction' => 10000,
            'numero_telephone' => '987654321', // Téléphone du user2
        ]);

        // Vérifier que le solde du compte 1 a diminué (montant + frais)
        $soldeCompte1 = $this->compte1->fresh()->getSoldeAttribute();
        $frais = max(10000 * 0.01, 100); // 100 FCFA (minimum)
        $this->assertEquals(50000 - 10000 - $frais, $soldeCompte1);

        // Vérifier que le compte 2 a reçu le montant
        $soldeCompte2 = $this->compte2->fresh()->getSoldeAttribute();
        $this->assertEquals(10000, $soldeCompte2);

        // Vérifier les transactions créées
        $transactionsCompte1 = $this->compte1->fresh()->transactions;
        $this->assertCount(3, $transactionsCompte1); // dépôt initial + transfert + frais

        $transactionsCompte2 = $this->compte2->fresh()->transactions;
        $this->assertCount(1, $transactionsCompte2); // dépôt de transfert
    }

    /** @test */
    public function test_echec_transfer_solde_insuffisant()
    {
        // Créer un compte avec peu d'argent
        $compte = Compte::create([
            'id_client' => $this->user1->id,
            'numero_compte' => 'ACC003',
            'code_pin' => '1234',
            'type' => 'client',
            'date_creation' => now(),
            'statut' => 'actif',
        ]);

        // Ajouter juste 50 FCFA
        $compte->transactions()->create([
            'type' => 'depot',
            'montant' => 50,
            'libelle' => 'Petit dépôt',
            'date_transaction' => now(),
            'statut' => 'validee',
        ]);

        // Essayer de transférer 10000 (qui coûtera 10000 + 100 = 10100)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Solde insuffisant pour effectuer ce transfert');

        $this->transactionService->createTransaction($compte, [
            'type' => 'transfert',
            'montant_transaction' => 10000,
            'numero_telephone' => '987654321',
        ]);
    }
}