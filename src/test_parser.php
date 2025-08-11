<?php
// Test stran za GraphQL parser
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test GraphQL Parser</h1>";

// Kopiraj funkcijo parseGraphQLQuery tukaj
function parseGraphQLQuery($query) {
    // Preprost parser za osnovne GraphQL poizvedbe
    $query = trim($query);
    
    // Odstrani "query" na začetku
    if (strpos($query, 'query') === 0) {
        $query = substr($query, 5);
    }
    
    // Odstrani zavit oklepaj
    $query = trim($query, '{}');
    
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Po čiščenju:</strong> " . htmlspecialchars($query) . "<br>";
    
    // Razčleni poizvedbo
    $lines = explode("\n", $query);
    $fields = [];
    $currentField = '';
    $inField = false;
    $parameters = [];
    
    echo "<strong>Vrstice:</strong><br>";
    foreach ($lines as $i => $line) {
        $line = trim($line);
        echo "Vrstica $i: '" . htmlspecialchars($line) . "'<br>";
        
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        // Preveri, ali je to glavno polje z parametri
        if (preg_match('/^(\w+)\s*\(([^)]*)\)\s*{?$/', $line, $matches)) {
            $currentField = $matches[1];
            $fields[$currentField] = [];
            $inField = true;
            
            echo "  -> Glavno polje z parametri: $currentField<br>";
            
            // Razčleni parametre
            $paramString = $matches[2];
            if (!empty($paramString)) {
                $params = explode(',', $paramString);
                foreach ($params as $param) {
                    $param = trim($param);
                    if (preg_match('/(\w+):\s*"?([^"]*)"?/', $param, $paramMatches)) {
                        $parameters[$currentField][$paramMatches[1]] = $paramMatches[2];
                        echo "    -> Parameter: {$paramMatches[1]} = {$paramMatches[2]}<br>";
                    }
                }
            }
        }
        // Preveri, ali je to glavno polje brez parametrov
        elseif (preg_match('/^(\w+)\s*{?$/', $line, $matches)) {
            $currentField = $matches[1];
            $fields[$currentField] = [];
            $inField = true;
            echo "  -> Glavno polje brez parametrov: $currentField<br>";
        }
        // Preveri, ali je to podpolje
        elseif (preg_match('/^(\w+)$/', $line, $matches) && $currentField && $inField) {
            $fields[$currentField][] = $matches[1];
            echo "  -> Podpolje: {$matches[1]}<br>";
        }
        // Preveri, ali se konča polje
        elseif ($line === '}' && $inField) {
            $inField = false;
            echo "  -> Konec polja<br>";
        }
    }
    
    echo "</div>";
    
    // Vrni strukturo polj z parametri
    $result = [];
    foreach ($fields as $field => $subfields) {
        $result[$field] = [
            'subfields' => $subfields,
            'parameters' => $parameters[$field] ?? []
        ];
    }
    
    return $result;
}

$testQueries = [
    'query { profesorji(limit: 2) { id ime } }',
    'query { profesorji { id ime } }',
    'query { profesorji(search: "tina") { id ime komentarji { komentar } } }'
];

foreach ($testQueries as $i => $query) {
    echo "<h3>Test $i: " . htmlspecialchars($query) . "</h3>";
    
    try {
        $result = parseGraphQLQuery($query);
        echo "<pre>Rezultat: " . print_r($result, true) . "</pre>";
    } catch (Exception $e) {
        echo "<div style='color: red;'>Napaka: " . $e->getMessage() . "</div>";
    }
    
    echo "<hr>";
}
?>
