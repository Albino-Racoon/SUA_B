<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

require_once __DIR__ . '/../models/Profesor.php';
require_once __DIR__ . '/../models/Kviz.php';

// Definicija tipov
$profesorType = new ObjectType([
    'name' => 'Profesor',
    'fields' => [
        'id' => Type::int(),
        'ime' => Type::string(),
        'url' => Type::string(),
        'created_at' => Type::string(), // Datum kot skalar
        'updated_at' => Type::string(),
        'komentarji' => [
            'type' => Type::listOf(new ObjectType([
                'name' => 'Komentar',
                'fields' => [
                    'id' => Type::int(),
                    'komentar' => Type::string(),
                    'created_at' => Type::string()
                ]
            ])),
            'resolve' => function($profesor) {
                $profesorModel = new Profesor();
                $data = $profesorModel->getByIdWithComments($profesor['id']);
                return array_map(function($item) {
                    return [
                        'id' => $item['komentar_id'],
                        'komentar' => $item['komentar_tekst'],
                        'created_at' => $item['komentar_created_at']
                    ];
                }, $data);
            }
        ]
    ]
]);

$komentarType = new ObjectType([
    'name' => 'Komentar',
    'fields' => [
        'id' => Type::int(),
        'komentar' => Type::string(),
        'created_at' => Type::string(), // Datum kot skalar
        'profesor' => [
            'type' => $profesorType,
            'resolve' => function($komentar) {
                $profesorModel = new Profesor();
                return $profesorModel->getById($komentar['profesor_id']);
            }
        ]
    ]
]);

$kvizRezultatType = new ObjectType([
    'name' => 'KvizRezultat',
    'fields' => [
        'id' => Type::int(),
        'uporabnik_id' => Type::string(),
        'stevilo_pravilnih' => Type::int(),
        'stevilo_vprasanj' => Type::int(),
        'rezultat' => Type::float(),
        'created_at' => Type::string() // Datum kot skalar
    ]
]);

$statistikaType = new ObjectType([
    'name' => 'Statistika',
    'fields' => [
        'skupno_kvizov' => Type::int(),
        'povprecje' => Type::float(),
        'najboljsi_rezultat' => Type::float(),
        'najslabsi_rezultat' => Type::float(),
        'skupno_pravilnih' => Type::int(),
        'skupno_vprasanj' => Type::int()
    ]
]);

// Definicija poizvedb
$queryType = new ObjectType([
    'name' => 'Query',
    'fields' => [
        'profesorji' => [
            'type' => Type::listOf($profesorType),
            'args' => [
                'limit' => Type::int(),
                'offset' => Type::int(),
                'search' => Type::string()
            ],
            'resolve' => function($root, $args) {
                $profesorModel = new Profesor();
                
                if (isset($args['search']) && !empty($args['search'])) {
                    return $profesorModel->search($args['search']);
                }
                
                $stmt = $profesorModel->getAll(
                    $args['limit'] ?? null,
                    $args['offset'] ?? null
                );
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        ],
        'profesor' => [
            'type' => $profesorType,
            'args' => [
                'id' => Type::nonNull(Type::int())
            ],
            'resolve' => function($root, $args) {
                $profesorModel = new Profesor();
                return $profesorModel->getById($args['id']);
            }
        ],
        'komentarji' => [
            'type' => Type::listOf($komentarType),
            'args' => [
                'profesor_id' => Type::int(),
                'limit' => Type::int()
            ],
            'resolve' => function($root, $args) {
                $profesorModel = new Profesor();
                
                if (isset($args['profesor_id'])) {
                    $data = $profesorModel->getByIdWithComments($args['profesor_id']);
                    return array_map(function($item) {
                        return [
                            'id' => $item['komentar_id'],
                            'komentar' => $item['komentar_tekst'],
                            'created_at' => $item['komentar_created_at'],
                            'profesor_id' => $item['id']
                        ];
                    }, $data);
                }
                
                // Pridobi vse komentarje
                $query = "SELECT k.*, p.ime as profesor_ime FROM komentarji k 
                         JOIN profesorji p ON k.profesor_id = p.id 
                         ORDER BY k.created_at DESC";
                if (isset($args['limit'])) {
                    $query .= " LIMIT " . intval($args['limit']);
                }
                
                $database = new Database();
                $conn = $database->getConnection();
                $stmt = $conn->prepare($query);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        ],
        'kvizRezultati' => [
            'type' => Type::listOf($kvizRezultatType),
            'args' => [
                'uporabnik_id' => Type::string(),
                'limit' => Type::int()
            ],
            'resolve' => function($root, $args) {
                $kvizModel = new Kviz();
                
                if (isset($args['uporabnik_id'])) {
                    return $kvizModel->getResultsByUser($args['uporabnik_id']);
                }
                
                $results = $kvizModel->getAllResults();
                if (isset($args['limit'])) {
                    $results = array_slice($results, 0, $args['limit']);
                }
                return $results;
            }
        ],
        'statistika' => [
            'type' => $statistikaType,
            'resolve' => function($root, $args) {
                $kvizModel = new Kviz();
                return $kvizModel->getStatistics();
            }
        ]
    ]
]);

// Ustvari shemo
$schema = new Schema([
    'query' => $queryType
]);

return $schema;
