<?php
// Prepreči izpis PHP napak v JSON odgovoru
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Preveri, ali obstaja vendor/autoload.php
    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        throw new Exception('Composer autoload.php ni najden. Izvedite "composer install" v korenski mapi projekta.');
    }
    
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/config/database.php';

    use GraphQL\GraphQL;
    use GraphQL\Server\StandardServer;

    // Naloži shemo
    $schema = require_once __DIR__ . '/graphql/schema.php';
    
    // Ustvari GraphQL strežnik
    $server = new StandardServer([
        'schema' => $schema
    ]);
    
    // Obdelaj zahtevo
    $result = $server->executeRequest();
    
    // Vrni rezultat
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'errors' => [
            [
                'message' => 'Internal server error: ' . $e->getMessage(),
                'extensions' => [
                    'category' => 'internal'
                ]
            ]
        ]
    ]);
}
