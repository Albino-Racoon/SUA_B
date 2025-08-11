<?php
// Direktno testiranje iskanja profesorjev
require_once 'models/Profesor.php';

try {
    $profesor = new Profesor();
    
    echo "<h2>Test iskanja profesorjev</h2>\n";
    
    // Test 1: Vsi profesorji
    echo "<h3>1. Vsi profesorji (limit 5):</h3>\n";
    $stmt = $profesor->getAll(5);
    $profesorji = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($profesorji, true) . "</pre>\n";
    
    // Test 2: Iskanje po "tina"
    echo "<h3>2. Iskanje po 'tina':</h3>\n";
    $searchResults = $profesor->search("tina");
    echo "<pre>" . print_r($searchResults, true) . "</pre>\n";
    
    // Test 3: Profesor z ID 1 in komentarji
    echo "<h3>3. Profesor z ID 1 in komentarji:</h3>\n";
    if (!empty($profesorji)) {
        $firstProf = $profesorji[0];
        $profWithComments = $profesor->getByIdWithComments($firstProf['id']);
        echo "<pre>" . print_r($profWithComments, true) . "</pre>\n";
    }
    
    // Test 4: Statistika komentarjev
    echo "<h3>4. Statistika komentarjev:</h3>\n";
    $totalComments = 0;
    foreach ($profesorji as $prof) {
        $comments = $profesor->getByIdWithComments($prof['id']);
        $commentCount = count(array_filter($comments, function($item) {
            return isset($item['komentar_id']) && $item['komentar_id'];
        }));
        $totalComments += $commentCount;
        echo "Profesor {$prof['ime']}: {$commentCount} komentarjev\n";
    }
    echo "Skupno komentarjev: {$totalComments}\n";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Napaka: " . $e->getMessage() . "</div>";
}
?>
