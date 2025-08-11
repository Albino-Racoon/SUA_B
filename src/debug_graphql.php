<?php
// Debug stran za GraphQL napake
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug GraphQL</h1>";

try {
    echo "<h2>1. Preverjanje povezave z bazo</h2>";
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<div style='color: green;'>✅ Povezava z bazo uspešna</div>";
    } else {
        echo "<div style='color: red;'>❌ Povezava z bazo neuspešna</div>";
    }
    
    echo "<h2>2. Preverjanje modelov</h2>";
    require_once 'models/Profesor.php';
    require_once 'models/Kviz.php';
    
    $profesor = new Profesor();
    echo "<div style='color: green;'>✅ Model Profesor uspešno naložen</div>";
    
    $kviz = new Kviz();
    echo "<div style='color: green;'>✅ Model Kviz uspešno naložen</div>";
    
    echo "<h2>3. Test poizvedbe</h2>";
    $stmt = $profesor->getAll(3);
    $profesorji = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<div style='color: green;'>✅ Pridobivanje profesorjev uspešno</div>";
    echo "<pre>Najdenih profesorjev: " . count($profesorji) . "</pre>";
    
    echo "<h2>4. Test iskanja</h2>";
    $searchResults = $profesor->search("tina");
    echo "<div style='color: green;'>✅ Iskanje uspešno</div>";
    echo "<pre>Rezultati iskanja: " . print_r($searchResults, true) . "</pre>";
    
    echo "<h2>5. Test komentarjev</h2>";
    if (!empty($profesorji)) {
        $firstProf = $profesorji[0];
        $comments = $profesor->getByIdWithComments($firstProf['id']);
        echo "<div style='color: green;'>✅ Pridobivanje komentarjev uspešno</div>";
        echo "<pre>Komentarji za {$firstProf['ime']}: " . print_r($comments, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Napaka: " . $e->getMessage() . "</div>";
    echo "<div style='color: red;'>Stack trace: " . $e->getTraceAsString() . "</div>";
}
?>
