<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Lire les fichiers YAML
$authYaml = file_get_contents(__DIR__ . '/../resources/docs/auth.yml');
$comptesYaml = file_get_contents(__DIR__ . '/../resources/docs/comptes.yml');
$transactionsYaml = file_get_contents(__DIR__ . '/../resources/docs/transactions.yml');

// Parser les YAML en arrays
$authData = Symfony\Component\Yaml\Yaml::parse($authYaml);
$comptesData = Symfony\Component\Yaml\Yaml::parse($comptesYaml);
$transactionsData = Symfony\Component\Yaml\Yaml::parse($transactionsYaml);

// Fusionner les paths
$mergedPaths = array_merge(
    $authData['paths'] ?? [],
    $comptesData['paths'] ?? [],
    $transactionsData['paths'] ?? []
);

// Créer le document final
$finalDoc = [
    'openapi' => '3.0.3',
    'info' => [
        'title' => 'OM Pay API',
        'description' => 'API complète pour le système de paiement OM Pay',
        'version' => '1.0.0',
    ],
    'servers' => [
        [
            'url' => 'http://localhost:8000/api',
            'description' => 'Serveur de développement',
        ],
    ],
    'paths' => $mergedPaths,
    'components' => [
        'securitySchemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
            ],
        ],
    ],
];

// Sauvegarder le JSON
$jsonContent = json_encode($finalDoc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents(__DIR__ . '/../storage/api-docs/api-docs.json', $jsonContent);

// Sauvegarder le YAML
$yamlContent = Symfony\Component\Yaml\Yaml::dump($finalDoc, 8, 2);
file_put_contents(__DIR__ . '/../storage/api-docs/api-docs.yaml', $yamlContent);

echo "Documentation Swagger régénérée avec succès !\n";
echo "- JSON: storage/api-docs/api-docs.json\n";
echo "- YAML: storage/api-docs/api-docs.yaml\n";