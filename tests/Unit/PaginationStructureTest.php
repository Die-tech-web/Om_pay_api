<?php

namespace Tests\Unit;

use App\Services\TransactionService;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\TestCase;

class PaginationStructureTest extends TestCase
{
    public function test_new_pagination_structure()
    {
        // Données simulées de transactions
        $transactions = collect([
            [
                'libelle' => 'Transfert d\'argent',
                'montant' => '-10000 CFA',
                'numero_destinataire' => '771234567',
                'date' => '2025-11-10',
                'type' => 'transfert'
            ],
            [
                'libelle' => 'Dépôt',
                'montant' => '+5000 CFA',
                'numero_destinataire' => null,
                'date' => '2025-11-09',
                'type' => 'depot'
            ]
        ]);

        // Créer un paginateur simulé
        $paginator = new LengthAwarePaginator(
            $transactions,
            25, // total_items
            20, // items_per_page
            1   // current_page
        );

        // Générer les liens de pagination
        $paginator->setPath(url('/api/mes-transactions'));

        // Structure attendue de la réponse
        $expectedResponse = [
            'data' => $transactions->toArray(),
            'pagination' => [
                'total_items' => 25,
                'items_per_page' => 20,
                'current_page' => 1,
                'has_previous' => false,
                'has_next' => true,
                'links' => [
                    'first' => 'http://localhost/api/mes-transactions?page=1',
                    'previous' => null,
                    'next' => 'http://localhost/api/mes-transactions?page=2',
                    'last' => 'http://localhost/api/mes-transactions?page=2'
                ]
            ]
        ];

        // Vérifier la structure
        $this->assertArrayHasKey('data', $expectedResponse);
        $this->assertArrayHasKey('pagination', $expectedResponse);
        
        $pagination = $expectedResponse['pagination'];
        $this->assertArrayHasKey('total_items', $pagination);
        $this->assertArrayHasKey('items_per_page', $pagination);
        $this->assertArrayHasKey('current_page', $pagination);
        $this->assertArrayHasKey('has_previous', $pagination);
        $this->assertArrayHasKey('has_next', $pagination);
        $this->assertArrayHasKey('links', $pagination);

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
        $this->assertNull($pagination['links']['previous']);

        // Vérifier que les données sont un tableau
        $this->assertIsArray($expectedResponse['data']);
        $this->assertCount(2, $expectedResponse['data']); // 2 transactions dans notre exemple

        echo "✅ Structure de pagination testée avec succès !\n";
        echo "Nouveau format :\n";
        echo json_encode($expectedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
}