<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagination_format()
    {
        // Créer un utilisateur et un compte
        $user = User::factory()->create([
            'nom' => 'Test User',
            'telephone' => '123456789'
        ]);
        
        $compte = Compte::create([
            'id_client' => $user->id,
            'numero_compte' => '1234567890',
            'solde' => 100000
        ]);

        // Créer 25 transactions pour tester la pagination
        Transaction::factory()->count(25)->create([
            'compte_id' => $compte->id,
            'type' => 'depot',
            'montant' => 1000,
            'libelle' => 'Dépôt de test',
            'statut' => 'validee',
            'date_transaction' => now()
        ]);

        $transactionService = new TransactionService();
        $result = $transactionService->getTransactions($compte);

        // Vérifier la structure de la réponse
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);

        // Vérifier la structure de pagination
        $pagination = $result['pagination'];
        $this->assertArrayHasKey('total_items', $pagination);
        $this->assertArrayHasKey('items_per_page', $pagination);
        $this->assertArrayHasKey('current_page', $pagination);
        $this->assertArrayHasKey('has_previous', $pagination);
        $this->assertArrayHasKey('has_next', $pagination);
        $this->assertArrayHasKey('links', $pagination);

        // Vérifier les liens
        $links = $pagination['links'];
        $this->assertArrayHasKey('first', $links);
        $this->assertArrayHasKey('previous', $links);
        $this->assertArrayHasKey('next', $links);
        $this->assertArrayHasKey('last', $links);

        // Vérifier les valeurs
        $this->assertEquals(25, $pagination['total_items']);
        $this->assertEquals(20, $pagination['items_per_page']);
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertFalse($pagination['has_previous']);
        $this->assertTrue($pagination['has_next']);

        // Vérifier que les données sont un tableau
        $this->assertIsArray($result['data']);
        $this->assertCount(20, $result['data']); // Première page avec 20 éléments
    }
}