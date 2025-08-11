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
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/models/Profesor.php';
    require_once __DIR__ . '/models/Kviz.php';

    // Preveri povezavo z bazo
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Ni mogoče se povezati z bazo podatkov');
    }

    // Preberi zahtevo
    $input = json_decode(file_get_contents('php://input'), true);
    $query = $input['query'] ?? '';

    if (empty($query)) {
        throw new Exception('GraphQL poizvedba je obvezna');
    }

    // Preprost GraphQL parser
    $parsedFields = parseGraphQLQuery($query);
    
    // Debug informacije
    error_log("=== GraphQL Request Start ===");
    error_log("Raw input: " . file_get_contents('php://input'));
    error_log("Query: " . $query);
    error_log("Parsed fields: " . print_r($parsedFields, true));
    
    $data = [];
    foreach ($parsedFields as $field => $fieldInfo) {
        error_log("Processing field: $field with info: " . print_r($fieldInfo, true));
        $data[$field] = executeQuery($field, $fieldInfo['subfields'], $fieldInfo['parameters']);
        error_log("Result for $field: " . print_r($data[$field], true));
    }
    
    $result = ['data' => $data];
    
    // Debug informacije
    if (isset($_GET['debug'])) {
        $result['debug'] = [
            'original_query' => $query,
            'parsed_fields_structure' => $parsedFields,
            'database_connected' => $conn ? 'yes' : 'no'
        ];
    }
    
    error_log("Final result: " . print_r($result, true));
    error_log("=== GraphQL Request End ===");
    
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

function parseGraphQLQuery($query) {
    // Preprost parser za osnovne GraphQL poizvedbe
    $query = trim($query);
    
    // Odstrani "query" na začetku
    if (strpos($query, 'query') === 0) {
        $query = substr($query, 5);
    }
    
    // Odstrani zavit oklepaj
    $query = trim($query, '{}');
    
    // Debug
    error_log("Cleaned query: " . $query);
    
    // Razčleni poizvedbo - uporabi regex za boljše razčlenjevanje
    $fields = [];
    $parameters = [];
    
    // Regex za glavna polja z ali brez parametrov
    if (preg_match_all('/(\w+)(?:\s*\(([^)]*)\))?\s*{([^}]*)}/s', $query, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $fieldName = $match[1];
            $argsString = $match[2] ?? '';
            $subfieldsString = $match[3];
            
            error_log("Found field: $fieldName, args: $argsString, subfields: $subfieldsString");
            
            // Razčleni parametre
            $fieldParams = [];
            if (!empty($argsString)) {
                if (preg_match_all('/(\w+):\s*"?([^",\s]+)"?/', $argsString, $argMatches, PREG_SET_ORDER)) {
                    foreach ($argMatches as $argMatch) {
                        $key = $argMatch[1];
                        $value = $argMatch[2];
                        
                        // Poskusi pretvoriti v int/float
                        if (is_numeric($value)) {
                            $fieldParams[$key] = strpos($value, '.') !== false ? (float)$value : (int)$value;
                        } else {
                            $fieldParams[$key] = $value;
                        }
                    }
                }
            }
            
            // Razčleni podpolja
            $subfields = [];
            if (preg_match_all('/\b(\w+)\b/', $subfieldsString, $subfieldMatches)) {
                $subfields = $subfieldMatches[1];
            }
            
            $fields[$fieldName] = [
                'subfields' => $subfields,
                'parameters' => $fieldParams
            ];
            
            error_log("Processed field $fieldName: " . print_r($fields[$fieldName], true));
        }
    }
    
    error_log("Final parsed result: " . print_r($fields, true));
    return $fields;
}

function executeQuery($field, $subfields, $parameters = []) {
    switch ($field) {
        case 'profesorji':
            $searchTerm = $parameters['search'] ?? null;
            $limit = isset($parameters['limit']) ? intval($parameters['limit']) : 10;
            return getProfessors($subfields, $searchTerm, $limit);
        case 'statistika':
            return getStatistics($subfields);
        case 'komentarji':
            return getComments($subfields);
        case 'kvizRezultati':
            return getQuizResults($subfields);
        default:
            return null;
    }
}

function getProfessors($subfields, $searchTerm = null, $limit = 10) {
    try {
        $profesor = new Profesor();
        
        // Uporabi iskanje, če je podan search term
        if ($searchTerm && !empty($searchTerm)) {
            $profesorji = $profesor->search($searchTerm);
        } else {
            $stmt = $profesor->getAll($limit);
            $profesorji = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Če ni podanih podpolj, vrni vse
        if (empty($subfields)) {
            $subfields = ['id', 'ime', 'url', 'created_at'];
        }
        
        // Debug informacije
        error_log("getProfessors called with subfields: " . print_r($subfields, true));
        error_log("Search term: " . ($searchTerm ?: 'none'));
        error_log("Found " . count($profesorji) . " professors");
        
        $result = [];
        foreach ($profesorji as $prof) {
            $profData = [];
            
            if (in_array('id', $subfields)) {
                $profData['id'] = $prof['id'];
            }
            if (in_array('ime', $subfields)) {
                $profData['ime'] = $prof['ime'];
            }
            if (in_array('url', $subfields)) {
                $profData['url'] = $prof['url'];
            }
            if (in_array('created_at', $subfields)) {
                $profData['created_at'] = $prof['created_at'];
            }
            if (in_array('komentarji', $subfields)) {
                $profData['komentarji'] = getProfessorComments($prof['id']);
            }
            
            $result[] = $profData;
        }
        
        error_log("Returning " . count($result) . " professor records");
        return $result;
    } catch (Exception $e) {
        error_log("Error in getProfessors: " . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

function getProfessorComments($professorId) {
    try {
        $profesor = new Profesor();
        $data = $profesor->getByIdWithComments($professorId);
        
        error_log("Getting comments for professor ID: " . $professorId);
        error_log("Raw data: " . print_r($data, true));
        
        $comments = [];
        foreach ($data as $item) {
            if (isset($item['komentar_id']) && $item['komentar_id']) {
                $comments[] = [
                    'id' => $item['komentar_id'],
                    'komentar' => $item['komentar_tekst'] ?? 'Neznan komentar',
                    'created_at' => $item['komentar_created_at'] ?? date('Y-m-d H:i:s')
                ];
            }
        }
        
        error_log("Found " . count($comments) . " comments for professor " . $professorId);
        return $comments;
    } catch (Exception $e) {
        error_log("Error getting comments for professor " . $professorId . ": " . $e->getMessage());
        return [];
    }
}

function getStatistics($subfields) {
    $kviz = new Kviz();
    $stats = $kviz->getStatistics();
    
    $result = [];
    if (in_array('skupno_kvizov', $subfields)) {
        $result['skupno_kvizov'] = $stats['skupno_kvizov'] ?? 0;
    }
    if (in_array('povprecje', $subfields)) {
        $result['povprecje'] = $stats['povprecje'] ?? 0;
    }
    if (in_array('najboljsi_rezultat', $subfields)) {
        $result['najboljsi_rezultat'] = $stats['najboljsi_rezultat'] ?? 0;
    }
    if (in_array('najslabsi_rezultat', $subfields)) {
        $result['najslabsi_rezultat'] = $stats['najslabsi_rezultat'] ?? 0;
    }
    
    return $result;
}

function getComments($subfields) {
    $profesor = new Profesor();
    $stmt = $profesor->getAll(20); // Limit na 20
    $profesorji = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $allComments = [];
    foreach ($profesorji as $prof) {
        $comments = getProfessorComments($prof['id']);
        foreach ($comments as $comment) {
            $commentData = [];
            
            if (in_array('id', $subfields)) {
                $commentData['id'] = $comment['id'];
            }
            if (in_array('komentar', $subfields)) {
                $commentData['komentar'] = $comment['komentar'];
            }
            if (in_array('created_at', $subfields)) {
                $commentData['created_at'] = $comment['created_at'];
            }
            if (in_array('profesor', $subfields)) {
                $commentData['profesor'] = [
                    'id' => $prof['id'],
                    'ime' => $prof['ime']
                ];
            }
            
            $allComments[] = $commentData;
        }
    }
    
    return $allComments;
}

function getQuizResults($subfields) {
    $kviz = new Kviz();
    $results = $kviz->getAllResults();
    
    $result = [];
    foreach ($results as $quiz) {
        $quizData = [];
        
        if (in_array('id', $subfields)) {
            $quizData['id'] = $quiz['id'];
        }
        if (in_array('uporabnik_id', $subfields)) {
            $quizData['uporabnik_id'] = $quiz['uporabnik_id'];
        }
        if (in_array('stevilo_pravilnih', $subfields)) {
            $quizData['stevilo_pravilnih'] = $quiz['stevilo_pravilnih'];
        }
        if (in_array('stevilo_vprasanj', $subfields)) {
            $quizData['stevilo_vprasanj'] = $quiz['stevilo_vprasanj'];
        }
        if (in_array('rezultat', $subfields)) {
            $quizData['rezultat'] = $quiz['rezultat'];
        }
        if (in_array('created_at', $subfields)) {
            $quizData['created_at'] = $quiz['created_at'];
        }
        
        $result[] = $quizData;
    }
    
    return $result;
}
?>
