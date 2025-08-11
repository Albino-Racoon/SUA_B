<?php
/**
 * Model za kviz funkcionalnost
 */

require_once __DIR__ . '/../config/database.php';

class Kviz {
    private $conn;
    private $table_name = "kviz_rezultati";
    
    public $id;
    public $uporabnik_id;
    public $stevilo_pravilnih;
    public $stevilo_vprasanj;
    public $rezultat;
    public $created_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Shrani rezultat kviza
    public function saveResult() {
        $query = "INSERT INTO " . $this->table_name . " (uporabnik_id, stevilo_pravilnih, stevilo_vprasanj, rezultat) VALUES (:uporabnik_id, :stevilo_pravilnih, :stevilo_vprasanj, :rezultat)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":uporabnik_id", $this->uporabnik_id);
        $stmt->bindParam(":stevilo_pravilnih", $this->stevilo_pravilnih);
        $stmt->bindParam(":stevilo_vprasanj", $this->stevilo_vprasanj);
        $stmt->bindParam(":rezultat", $this->rezultat);
        
        return $stmt->execute();
    }
    
    // Pridobi vse rezultate
    public function getAllResults() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Pridobi rezultate za določenega uporabnika
    public function getResultsByUser($uporabnik_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE uporabnik_id = :uporabnik_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uporabnik_id", $uporabnik_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Pridobi statistiko
    public function getStatistics() {
        $query = "SELECT 
                    COUNT(*) as skupno_kvizov,
                    AVG(rezultat) as povprecje,
                    MAX(rezultat) as najboljsi_rezultat,
                    MIN(rezultat) as najslabsi_rezultat,
                    SUM(stevilo_pravilnih) as skupno_pravilnih,
                    SUM(stevilo_vprasanj) as skupno_vprasanj
                  FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Generiraj kviz vprašanja
    public function generateQuiz($stevilo_profesorjev = 10, $stevilo_vprasanj = 10, $selected_professors = []) {
        require_once __DIR__ . '/Profesor.php';
        $profesor = new Profesor();
        
        // Pridobi naključne profesorje (omejene na izbrane, če so podani)
        $profesorji = $profesor->getRandomForQuiz($stevilo_profesorjev, $selected_professors);
        
        // Pridobi ID-je izbranih profesorjev za komentarje
        $professor_ids = array_column($profesorji, 'id');
        
        // Pridobi naključne komentarje (omejene na izbrane profesorje)
        $komentarji = $profesor->getRandomComments($stevilo_vprasanj, $professor_ids);
        
        $quiz_data = [
            'profesorji' => $profesorji,
            'vprasanja' => []
        ];
        
        // Pridobi vsa imena profesorjev za anonimizacijo
        $vsa_imena = array_column($profesorji, 'ime');
        $anonymization = $this->anonymizeNames($vsa_imena);
        $name_mapping = $anonymization['mapping'];
        
        // Ustvari vprašanja
        foreach ($komentarji as $komentar) {
            // Anonimiziraj komentar (zamenjaj imena z "Profesor X")
            $anonymized_komentar = $this->anonymizeComment($komentar['komentar'], $name_mapping);
            
            $vprasanje = [
                'komentar_id' => $komentar['id'],
                'komentar' => $anonymized_komentar,
                'mozni_odgovori' => [],
                'pravilen_odgovor' => $komentar['profesor_ime'], // Uporabi pravo ime v odgovorih
                'original_ime' => $komentar['profesor_ime']
            ];
            
            // Dodaj pravilen odgovor med možne odgovore (pravo ime)
            $mozni_odgovori = [$komentar['profesor_ime']];
            
            // Dodaj še 3 napačne odgovore iz ostalih profesorjev (prava imena)
            $ostali_profesorji = array_filter($profesorji, function($p) use ($komentar) {
                return $p['ime'] !== $komentar['profesor_ime'];
            });
            
            if (count($ostali_profesorji) >= 3) {
                $nakljucni_ostali = array_rand($ostali_profesorji, 3);
                foreach ($nakljucni_ostali as $index) {
                    $mozni_odgovori[] = $ostali_profesorji[$index]['ime'];
                }
            } else {
                // Če ni dovolj ostalih profesorjev, dodaj vse
                foreach ($ostali_profesorji as $ostali) {
                    $mozni_odgovori[] = $ostali['ime'];
                }
            }
            
            // Premešaj možne odgovore
            shuffle($mozni_odgovori);
            
            $vprasanje['mozni_odgovori'] = $mozni_odgovori;
            $quiz_data['vprasanja'][] = $vprasanje;
        }
        
        return $quiz_data;
    }
    
    // Anonimiziraj imena profesorjev
    private function anonymizeNames($names) {
        $anonymized = [];
        $name_mapping = [];
        
        foreach ($names as $name) {
            if (!isset($name_mapping[$name])) {
                // Ustvari anonimno ime
                $anonymized_name = "Profesor " . (count($name_mapping) + 1);
                $name_mapping[$name] = $anonymized_name;
            }
            $anonymized[] = $name_mapping[$name];
        }
        
        return ['anonymized' => $anonymized, 'mapping' => $name_mapping];
    }
    
    // Anonimiziraj komentar (zamenjaj imena z "Profesor X")
    private function anonymizeComment($komentar, $name_mapping) {
        $anonymized_komentar = $komentar;
        
        // Zamenjaj vsa imena v komentarju z anonimiziranimi
        foreach ($name_mapping as $original_name => $anonymized_name) {
            // Uporabi različne variacije imen (ime, priimek, polno ime)
            $name_parts = explode(' ', trim($original_name));
            
            // Zamenjaj polno ime (različne variacije velikosti črk)
            $anonymized_komentar = str_ireplace($original_name, $anonymized_name, $anonymized_komentar);
            
            // Zamenjaj samo ime (različne variacije velikosti črk)
            if (count($name_parts) > 0) {
                $anonymized_komentar = str_ireplace($name_parts[0], $anonymized_name, $anonymized_komentar);
            }
            
            // Zamenjaj samo priimek (različne variacije velikosti črk)
            if (count($name_parts) > 1) {
                $anonymized_komentar = str_ireplace($name_parts[1], $anonymized_name, $anonymized_komentar);
            }
        }
        
        return $anonymized_komentar;
    }
    
    // Preveri odgovore kviza
    public function checkAnswers($quiz_data, $user_answers) {
        $rezultati = [
            'pravilni' => 0,
            'napacni' => 0,
            'skupno' => count($quiz_data['vprasanja']),
            'odgovori' => []
        ];
        
        foreach ($quiz_data['vprasanja'] as $index => $vprasanje) {
            $user_answer = $user_answers[$index] ?? null;
            $pravilen_odgovor = $vprasanje['pravilen_odgovor'];
            
            // Preveri, ali je odgovor pravilen
            $je_pravilen = ($user_answer === $pravilen_odgovor);
            
            if ($je_pravilen) {
                $rezultati['pravilni']++;
            } else {
                $rezultati['napacni']++;
            }
            
            $rezultati['odgovori'][] = [
                'vprasanje' => $vprasanje['komentar'],
                'user_answer' => $user_answer,
                'pravilen_odgovor' => $pravilen_odgovor,
                'je_pravilen' => $je_pravilen
            ];
        }
        
        $rezultati['rezultat_procent'] = round(($rezultati['pravilni'] / $rezultati['skupno']) * 100, 2);
        
        return $rezultati;
    }
}
?>
