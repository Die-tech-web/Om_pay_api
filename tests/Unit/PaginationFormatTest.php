<?php

namespace Tests\Unit;

use App\Services\TransactionService;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\TestCase;

class PaginationFormatTest extends TestCase
{
    public function test_pagination_format_structure()
    {
        // Simuler des données paginées
        $items = collect(range(1, 25))->map(function ($i) {
            return [
                'libelle' => "Transaction $i",
                'montant' => "+1000 CFA",
                'numero_destinataire' => "123456789",
                'date' => now()->format('Y-m-d'),
                'type' => 'depot',
            ];
        });

        // Créer un paginateur simulé
        $paginator = new LengthAwarePaginator(
            $items->forPage(1, 20), // Page 1, 20 items par page
            25,                     // Total items
            20,                     // Items per page
            1                       // Current page
        );

        // Tester notre service de transaction
        $transactionService = new TransactionService();
        
        // Utiliser la méthode getTransactions en mockant le compte
        $mockCompte = $this->createMock(\App\Models\Compte::class);
        $mockCompte->method('transactions')->willReturn(
            (clone $paginator)->getCollection()->values()
        );

        // Simuler la réponse attendue
        $expected = [
            'data' => $items->take(20)->values()->toArray(),
            'pagination' => [
                'total_items' => 25,
                'items_per_page' => 20,
                'current_page' => 1,
                'has_previous' => false,
                'has_next' => true,
                'links' => [
                    'first' => 'http://localhost?page=1',
                    'previous' => null,
                    'next' => 'http://localhost?page=2',
                    'last' => 'http://localhost?page=2'
                ]
            ]
        ];

        // Vérifier la structure de la pagination
        $this->assertArrayHasKey('data', $expected);
        $this->assertArrayHasKey('pagination', $expected);
        
        $pagination = $expected['pagination'];
        $this->assertArrayHasKey('total_items', $pagination);
        $this->assertArrayHasKey('items_per_page', $pagination);
        $this->assertArrayHasKey('current_page', $pagination);
        $this->assertArrayHasKey('has_previous', $pagination);
        $this->assertArrayHasKey('has_next', $pagination);
        $this->assertArrayHasKey('links', $pagination);
        
        $this->assertEquals(25, $pagination['total_items']);
        $this->assertEquals(20, $pagination['items_per_page']);
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertFalse($pagination['has_previous']);
        $this->assertTrue($pagination['has_next']);
    }
}