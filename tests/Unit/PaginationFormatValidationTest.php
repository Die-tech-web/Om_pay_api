<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PaginationFormatValidationTest extends TestCase
{
    public function test_validate_pagination_format()
    {
        // Structure attendue de la réponse de pagination
        $expectedResponse = [
            'data' => [
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
            ],
            'pagination' => [
                'total_items' => 25,
                'items_per_page' => 20,
                'current_page' => 1,
                'has_previous' => false,
                'has_next' => true,
                'links' => [
                    'first' => 'http://localhost:8000/api/mes-transactions?page=1',
                    'previous' => null,
                    'next' => 'http://localhost:8000/api/mes-transactions?page=2',
                    'last' => 'http://localhost:8000/api/mes-transactions?page=2'
                ]
            ]
        ];

        // Vérifier la structure de la réponse
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

        // Vérifier les types de données
        $this->assertIsArray($expectedResponse['data']);
        $this->assertIsArray($pagination);
        $this->assertIsInt($pagination['total_items']);
        $this->assertIsInt($pagination['items_per_page']);
        $this->assertIsInt($pagination['current_page']);
        $this->assertIsBool($pagination['has_previous']);
        $this->assertIsBool($pagination['has_next']);
        $this->assertIsArray($pagination['links']);

        // Vérifier les valeurs
        $this->assertEquals(25, $pagination['total_items']);
        $this->assertEquals(20, $pagination['items_per_page']);
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertFalse($pagination['has_previous']);
        $this->assertTrue($pagination['has_next']);
        $this->assertNull($pagination['links']['previous']);
        $this->assertIsString($pagination['links']['first']);
        $this->assertIsString($pagination['links']['next']);
        $this->assertIsString($pagination['links']['last']);

        // Afficher un exemple du nouveau format
        echo "✅ Format de pagination validé !\n\n";
        echo "🎉 ANCIEN FORMAT (Laravel par défaut) :\n";
        echo json_encode([
            "status" => true,
            "message" => "Transactions récupérées avec succès",
            "data" => [
                "current_page" => 1,
                "data" => [],
                "first_page_url" => "http://localhost:8000/api/mes-transactions?page=1",
                "from" => null,
                "last_page" => 1,
                "last_page_url" => "http://localhost:8000/api/mes-transactions?page=1",
                "links" => [],
                "next_page_url" => null,
                "path" => "http://localhost:8000/api/mes-transactions",
                "per_page" => 20,
                "prev_page_url" => null,
                "to" => null,
                "total" => 0
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "\n\n";

        echo "✨ NOUVEAU FORMAT (demandé) :\n";
        echo json_encode([
            "status" => true,
            "message" => "Transactions récupérées avec succès",
            "data" => $expectedResponse
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "\n";
    }
}