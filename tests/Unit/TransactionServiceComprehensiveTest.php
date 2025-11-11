<?php

namespace Tests\Unit;

use App\Services\TransactionService;
use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class TransactionServiceComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionService = new TransactionService();
    }

    public function test_depot_transaction()
    {
        // Créer un utilisateur et un compte
        $user = User::factory()->create([
            'nom' => 'Jean',
            'prenom' => 'Dupont',
            'telephone' => '771234567'
        ]);
        
        $compte = Compte::create([
            'id_client' => $user->id,
            'numero_compte' => '1234567890',
            'type' => 'client',
            'statut' => 'actif'
        ]);

        // Effectuer un dépôt
        $result = $this->transactionService->createDepot($compte, 50000, 'agence');

        // Vérifier la transaction créée
        $this->assertArrayHasKey('libelle', $result);
        $this->assertArrayHasKey('montant', $result);
        $this->assertArrayHasKey('destinataire', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('reference', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('statut', $result);

        // Vérifier le montant formaté
        $this->assertEquals('+50 000 CFA', $result['montant']);
        $this->assertEquals('Dépôt', $result['destinataire']['type_operation']);
        $this->assertEquals('depot', $result['type']);
        $this->assertEquals('validee', $result['statut']);

        // Vérifier que le solde a été mis à jour
        $nouveauSolde = $compte->getSoldeAttribute();
        $this->assertEquals(50000, $nouveauSolde);

        // Vérifier la transaction en base
        $transaction = Transaction::where('compte_id', $compte->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('depot', $transaction->type);
        $this->assertEquals(50000, $transaction->montant);
        $this->assertEquals('Dépôt en agence', $transaction->libelle);
    }

    public function test_retrait_transaction()
    {
        // Créer un utilisateur et un compte
        $user = User::factory()->create([
            'nom' => 'Jean',
            'prenom' => 'Dupont',
            'telephone' => '771234567'
        ]);
        
        $compte = Compte::create([
            'id_client' => $user->id,
            'numero_compte' => '1234567890',
            'type' => 'client',
            'statut' => 'actif'
        ]);

        // D'abord faire un dépôt
        $this->transactionService->createDepot($compte, 100000, 'agence');

        // Effectuer un retrait
        $result = $this->transactionService->createRetrait($compte, 30000, 'distributeur');

        // Vérifier la transaction créée
        $this->assertArrayHasKey('libelle', $result);
        $this->assertArrayHasKey('montant', $result);
        $this->assertArrayHasKey('destinataire', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('reference', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('statut', $result);

        // Vérifier le montant formaté
        $this->assertEquals('-30 000 CFA', $result['montant']);
        $this->assertEquals('Retrait', $result['destinataire']['type_operation']);
        $this->assertEquals('retrait', $result['type']);
        $this->assertEquals('validee', $result['statut']);

        // Vérifier que le solde a été mis à jour
        $nouveauSolde = $compte->getSoldeAttribute();
        $this->assertEquals(70000, $nouveauSolde);
    }

    public function test_retrait_solde_insuffisant()
    {
        // Créer un utilisateur et un compte avec un solde de 0
        $user = User::factory()->create([
            'nom' => 'Jean',
            'prenom' => 'Dupont',
            'telephone' => '771234567'
        ]);
        
        $compte = Compte::create([
            'id_client' => $user->id,
            'numero_compte' => '1234567890',
            'type' => 'client',
            'statut' => 'actif'
        ]);

        // Essayer de faire un retrait avec solde insuffisant
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Solde insuffisant');

        $this->transactionService->createRetrait($compte, 10000, 'distributeur');
    }

    public function test_get_transactions_avec_destinataire()
    {
        // Créer deux utilisateurs
        $user1 = User::factory()->create([
            'nom' => 'Jean',
            'prenom' => 'Dupont',
            'telephone' => '771234567'
        ]);
        
        $user2 = User::factory()->create([
            'nom' => 'Marie',
            'prenom' => 'Martin',
            'telephone' => '771234568'
        ]);
        
        // Créer leurs comptes
        $compte1 = Compte::create([
            'id_client' => $user1->id,
            'numero_compte' => '1234567890',
            'type' => 'client',
            'statut' => 'actif'
        ]);

        $compte2 = Compte::create([
            'id_client' => $user2->id,
            'numero_compte' => '1234567891',
            'type' => 'client',
            'statut' => 'actif'
        ]);

        // Dépôt initial pour les deux comptes
        $this->transactionService->createDepot($compte1, 100000, 'agence');
        $this->transactionService->createDepot($compte2, 50000, 'agence');

        // Transfert de compte1 vers compte2
        $data = [
            'numero_telephone' => '771234568',
            'montant_transaction' => 20000
        ];
        $this->transactionService->createTransaction($compte1, $data);

        // Récupérer les transactions du compte1
        $transactions = $this->transactionService->getTransactions($compte1);

        // Vérifier la structure
        $this->assertArrayHasKey('data', $transactions);
        $this->assertArrayHasKey('pagination', $transactions);

        // Vérifier les transactions
        $data = $transactions['data'];
        $this->assertNotEmpty($data);

        // La première transaction devrait être le transfert
        $transaction = $data[0];
        $this->assertEquals('transfert', $transaction['type']);
        $this->assertEquals('Transfert d\'argent', $transaction['libelle']);

        // Vérifier les informations du destinataire
        $destinataire = $transaction['destinataire'];
        $this->assertTrue($destinataire['est_client']);
        $this->assertEquals('Marie Martin', $destinataire['nom']);
        $this->assertEquals('771234568', $destinataire['numero']);

        // Vérifier les montants formatés
        $this->assertStringContainsString('CFA', $transaction['montant']);
        $this->assertStringContainsString('-', $transaction['montant']); // Débit

        // Vérifier la pagination
        $pagination = $transactions['pagination'];
        $this->assertEquals(2, $pagination['total_items']); // Dépôt + Transfert
        $this->assertEquals(20, $pagination['items_per_page']);
        $this->assertEquals(1, $pagination['current_page']);
    }

    public function test_formatage_montants()
    {
        // Créer un utilisateur et un compte
        $user = User::factory()->create([
            'nom' => 'Jean',
            'prenom' => 'Dupont',
            'telephone' => '771234567'
        ]);
        
        $compte = Compte::create([
            'id_client' => $user->id,
            'numero_compte' => '1234567890',
            'type' => 'client',
            'statut' => 'actif'
        ]);

        // Effectuer différentes transactions
        $this->transactionService->createDepot($compte, 1000000, 'agence'); // 1 million
        $this->transactionService->createRetrait($compte, 150000, 'distributeur');

        // Récupérer les transactions
        $transactions = $this->transactionService->getTransactions($compte);
        $data = $transactions['data'];

        // Vérifier le formatage des montants
        $this->assertEquals('+1 000 000 CFA', $data[0]['montant']); // Dépôt
        $this->assertEquals('-150 000 CFA', $data[1]['montant']); // Retrait
    }
}