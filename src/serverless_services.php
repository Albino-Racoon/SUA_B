<?php
// Omogoči error reporting za debugiranje
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ne prikazuj napak uporabniku
ini_set('log_errors', 1);

try {
    require_once 'config/database.php';
    require_once 'models/Profesor.php';
    require_once 'models/Kviz.php';

// Ustvari povezavo z bazo
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Napaka pri povezavi z bazo podatkov");
}

$profesor = new Profesor($pdo);
$kviz = new Kviz($pdo);

// Pridobi podatke
$profesorji_stmt = $profesor->getAllWithComments();
$profesorji_data = $profesorji_stmt->fetchAll(PDO::FETCH_ASSOC);
$statistika = $kviz->getStatistics();

// Preveri, ali je AJAX zahtevek za komentarje
if (isset($_POST['action']) && $_POST['action'] === 'get_comments') {
    $professor_id = $_POST['professor_id'];
    $comments = $profesor->getByIdWithComments($professor_id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'comments' => $comments
    ]);
    exit;
}

    // Preveri, ali je AJAX zahtevek za dodajanje komentarja
    if (isset($_POST['action']) && $_POST['action'] === 'add_comment') {
        $professor_id = $_POST['professor_id'];
        $comment_text = $_POST['comment_text'];
        
        // Dodaj komentar v bazo
        $query = "INSERT INTO komentarji (profesor_id, komentar, created_at) VALUES (:professor_id, :comment_text, NOW())";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":professor_id", $professor_id);
        $stmt->bindParam(":comment_text", $comment_text);
        
        if ($stmt->execute()) {
            // Pridobi novododani komentar
            $new_comment_id = $pdo->lastInsertId();
            $new_comment = [
                'komentar_id' => $new_comment_id,
                'komentar_tekst' => $comment_text,
                'komentar_created_at' => date('Y-m-d H:i:s')
            ];
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Komentar uspešno dodan',
                'comment' => $new_comment
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Napaka pri dodajanju komentarja'
            ]);
        }
        exit;
    }
    
    // Preveri, ali je AJAX zahtevek za brisanje komentarja
    if (isset($_POST['action']) && $_POST['action'] === 'delete_comment') {
        $comment_id = $_POST['comment_id'];
        
        // Izbriši komentar iz baze
        $query = "DELETE FROM komentarji WHERE id = :comment_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":comment_id", $comment_id);
        
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Komentar uspešno izbrisan'
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Napaka pri brisanju komentarja'
            ]);
        }
        exit;
    }
    
    // Preveri, ali je AJAX zahtevek za spreminjanje komentarja
    if (isset($_POST['action']) && $_POST['action'] === 'edit_comment') {
        $comment_id = $_POST['comment_id'];
        $comment_text = $_POST['comment_text'];
        
        // Posodobi komentar v bazi - samo besedilo
        $query = "UPDATE komentarji SET komentar = :comment_text WHERE id = :comment_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":comment_id", $comment_id);
        $stmt->bindParam(":comment_text", $comment_text);
        
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Komentar uspešno posodobljen',
                'comment' => [
                    'id' => $comment_id,
                    'komentar_tekst' => $comment_text
                ]
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Napaka pri posodabljanju komentarja'
            ]);
        }
        exit;
    }
    
    // Preveri, ali je AJAX zahtevek za dodajanje profesorja
    if (isset($_POST['action']) && $_POST['action'] === 'add_professor') {
        $professor_name = $_POST['professor_name'];
        
        // Dodaj profesorja v bazo - uporabi samo ime in URL
        $query = "INSERT INTO profesorji (ime, url) VALUES (:ime, :url)";
        $stmt = $pdo->prepare($query);
        
        $ime = trim($professor_name);
        $url = '#'; // Privzeti URL
        
        $stmt->bindParam(":ime", $ime);
        $stmt->bindParam(":url", $url);
        
        if ($stmt->execute()) {
            $new_professor_id = $pdo->lastInsertId();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Profesor uspešno dodan',
                'professor' => [
                    'id' => $new_professor_id,
                    'ime' => $ime,
                    'full_name' => trim($professor_name)
                ]
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Napaka pri dodajanju profesorja'
            ]);
        }
        exit;
    }
    
    // Preveri, ali je AJAX zahtevek za brisanje profesorja
    if (isset($_POST['action']) && $_POST['action'] === 'delete_professor') {
        $professor_id = $_POST['professor_id'];
        
        // Najprej izbriši vse komentarje za tega profesorja
        $delete_comments_query = "DELETE FROM komentarji WHERE profesor_id = :professor_id";
        $stmt_comments = $pdo->prepare($delete_comments_query);
        $stmt_comments->bindParam(":professor_id", $professor_id);
        $stmt_comments->execute();
        
        // Nato izbriši profesorja
        $delete_professor_query = "DELETE FROM profesorji WHERE id = :professor_id";
        $stmt_professor = $pdo->prepare($delete_professor_query);
        $stmt_professor->bindParam(":professor_id", $professor_id);
        
        if ($stmt_professor->execute()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Profesor in vsi njegovi komentarji uspešno izbrisani'
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Napaka pri brisanju profesorja'
            ]);
        }
        exit;
    }
    
    // Preveri, ali je AJAX zahtevek za spreminjanje profesorja
    if (isset($_POST['action']) && $_POST['action'] === 'edit_professor') {
        $professor_id = $_POST['professor_id'];
        $professor_name = $_POST['professor_name'];
        
        // Posodobi profesorja v bazi - samo ime
        $ime = trim($professor_name);
        
        $query = "UPDATE profesorji SET ime = :ime WHERE id = :professor_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":professor_id", $professor_id);
        $stmt->bindParam(":ime", $ime);
        
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Profesor uspešno posodobljen',
                'professor' => [
                    'id' => $professor_id,
                    'ime' => $ime,
                    'full_name' => trim($professor_name)
                ]
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Napaka pri posodabljanju profesorja'
            ]);
        }
        exit;
    }
    
} catch (Exception $e) {
    // Log napako
    error_log("Serverless services error: " . $e->getMessage());
    
    // Če je AJAX zahtevek, vrni JSON napako
    if (isset($_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Sistemska napaka: ' . $e->getMessage()
        ]);
        exit;
    }
    
    // Če ni AJAX, prikaži HTML napako
    echo "<div style='color: red; padding: 20px;'>Sistemska napaka: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serverless Storitve - SUA Asistent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .service-card {
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .service-card:hover {
            border-color: #007bff;
            box-shadow: 0 0.5rem 1rem rgba(0, 123, 255, 0.15);
        }
        .service-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .professor-item {
            border-left: 4px solid #007bff;
            margin-bottom: 0.5rem;
            padding: 0.75rem;
            background-color: #f8f9fa;
            border-radius: 0.375rem;
        }
        .comment-item {
            border-left: 4px solid #28a745;
            margin-bottom: 0.25rem;
            padding: 0.5rem;
            background-color: #f8f9fa;
            border-radius: 0.375rem;
        }
        .action-buttons {
            display: flex;
            gap: 0.25rem;
            flex-wrap: wrap;
        }
        
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .result-area {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        .status-online { background-color: #28a745; }
        .status-offline { background-color: #dc3545; }
        
        /* Animacije za operacije */
        .professor-item, .comment-item {
            transition: all 0.3s ease;
        }
        
        .professor-item:hover, .comment-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        /* Stili za rezultate */
        .result-area {
            max-height: 300px;
            overflow-y: auto;
        }
        
        /* Stili za input polja */
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        /* Stili za gumbe */
        .btn {
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
        
        /* Kompaktni prikaz */
        .card-header {
            padding: 0.5rem 1rem;
        }
        
        .card-body {
            padding: 0.75rem 1rem;
        }
        
        .form-control-sm {
            height: calc(1.5em + 0.5rem + 2px);
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        /* Zmanjšaj razmike med elementi */
        .mb-4 {
            margin-bottom: 1rem !important;
        }
        
        .mb-3 {
            margin-bottom: 0.75rem !important;
        }
        
        .mb-2 {
            margin-bottom: 0.5rem !important;
        }
        
        .mb-1 {
            margin-bottom: 0.25rem !important;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i>SUA Asistent
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Domov
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quiz.php">
                            <i class="fas fa-question-circle me-1"></i>Kviz
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="serverless_services.php">
                            <i class="fas fa-cloud me-1"></i>Serverless
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profesorji_graphql.php">
                            <i class="fas fa-code me-1"></i>GraphQL
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="api/docs/">
                            <i class="fas fa-book me-1"></i>API Docs
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card service-header">
                    <div class="card-body text-center">
                        <h1 class="mb-2">
                            <i class="fas fa-cloud me-3"></i>
                            🔄 Serverless Storitve
                        </h1>
                        <p class="mb-0">Oblačno računalništvo - Upravljanje profesorjev in komentarjev</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-server me-2"></i>Status Storitev
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <span class="status-indicator status-online"></span>
                                    <strong>Analytics Service:</strong> 
                                    <span id="analytics-status">Preverjam...</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <span class="status-indicator status-online"></span>
                                    <strong>Notification Service:</strong> 
                                    <span id="notification-status">Preverjam...</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services -->
        <div class="row">
            <!-- Analytics Service -->
            <div class="col-lg-6 mb-3">
                <div class="card service-card h-100">
                    <div class="card-header bg-info text-white py-2">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            📊 Analytics Service
                        </h6>
                    </div>
                    <div class="card-body py-2">
                        <p class="text-muted small mb-2">Upravljanje logov in statistike uporabe</p>
                        
                        <div class="mb-2">
                            <h6 class="small">Statistike:</h6>
                            <div class="row text-center g-1">
                                <div class="col-4">
                                    <div class="border rounded p-1">
                                        <strong id="total-logs" class="small">-</strong>
                                        <br><small class="text-muted">Skupaj</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-1">
                                        <strong id="today-logs" class="small">-</strong>
                                        <br><small class="text-muted">Danes</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-1">
                                        <strong id="popular-action" class="small">-</strong>
                                        <br><small class="text-muted">Popularno</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button class="btn btn-info btn-sm" onclick="analyticsService('GET')">
                                <i class="fas fa-download me-1"></i>GET
                            </button>
                            <button class="btn btn-success btn-sm" onclick="analyticsService('POST')">
                                <i class="fas fa-plus me-1"></i>POST
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="analyticsService('PUT')">
                                <i class="fas fa-edit me-1"></i>PUT
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="analyticsService('DELETE')">
                                <i class="fas fa-trash me-1"></i>DELETE
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Service -->
            <div class="col-lg-6 mb-3">
                <div class="card service-card h-100">
                    <div class="card-header bg-warning text-dark py-2">
                        <h6 class="mb-0">
                            <i class="fas fa-bell me-2"></i>
                            🔔 Notification Service
                        </h6>
                    </div>
                    <div class="card-body py-2">
                        <p class="text-muted small mb-2">Upravljanje notifikacij in obvestil</p>
                        
                        <div class="mb-2">
                            <h6 class="small">Notifikacije:</h6>
                            <div class="row text-center g-1">
                                <div class="col-4">
                                    <div class="border rounded p-1">
                                        <strong id="total-notifications" class="small">-</strong>
                                        <br><small class="text-muted">Skupaj</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-1">
                                        <strong id="unread-notifications" class="small">-</strong>
                                        <br><small class="text-muted">Neprebrane</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-1">
                                        <strong id="high-priority" class="small">-</strong>
                                        <br><small class="text-muted">Visoka prioriteta</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button class="btn btn-info btn-sm" onclick="notificationService('GET')">
                                <i class="fas fa-download me-1"></i>GET
                            </button>
                            <button class="btn btn-success btn-sm" onclick="notificationService('POST')">
                                <i class="fas fa-plus me-1"></i>POST
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="notificationService('PUT')">
                                <i class="fas fa-edit me-1"></i>PUT
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="notificationService('DELETE')">
                                <i class="fas fa-trash me-1"></i>DELETE
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Management -->
        <div class="row">
            <!-- Professors Management -->
            <div class="col-lg-6 mb-3">
                <div class="card">
                    <div class="card-header bg-primary text-white py-2">
                        <h6 class="mb-0">
                            <i class="fas fa-user-tie me-2"></i>
                            Profesorji
                        </h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="mb-2">
                            <div class="input-group mb-1">
                                <input type="text" class="form-control form-control-sm" id="professor-search" placeholder="Išči profesorje...">
                                <button class="btn btn-outline-primary btn-sm" onclick="searchProfessors()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm" id="new-professor-name" placeholder="Ime novega profesorja...">
                                <button class="btn btn-success btn-sm" onclick="addNewProfessor()">
                                    <i class="fas fa-plus"></i> Dodaj
                                </button>
                            </div>
                        </div>
                        
                        <div id="professors-list">
                            <?php foreach ($profesorji_data as $profesor_item): ?>
                                <div class="professor-item" data-professor-id="<?php echo $profesor_item['id']; ?>">
                                    <h6 class="mb-1">
                                        <i class="fas fa-user-tie me-2"></i>
                                        <?php echo htmlspecialchars($profesor_item['ime']); ?>
                                    </h6>
                                    <p class="mb-2 text-muted">
                                        <i class="fas fa-comments me-1"></i>
                                        <?php echo $profesor_item['stevilo_komentarjev']; ?> komentarjev
                                    </p>
                                    <div class="action-buttons">
                                        <button class="btn btn-outline-info btn-sm" onclick="viewProfessor(<?php echo $profesor_item['id']; ?>)">
                                            <i class="fas fa-eye me-1"></i>Pogled
                                        </button>
                                        <button class="btn btn-outline-warning btn-sm" onclick="editProfessor(<?php echo $profesor_item['id']; ?>)">
                                            <i class="fas fa-edit me-1"></i>Uredi
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteProfessor(<?php echo $profesor_item['id']; ?>)">
                                            <i class="fas fa-trash me-1"></i>Izbriši
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comments Management -->
            <div class="col-lg-6 mb-3">
                <div class="card">
                    <div class="card-header bg-success text-white py-2">
                        <h6 class="mb-0">
                            <i class="fas fa-comments me-2"></i>
                            Komentarji za <span id="selected-professor-name">vse profesorje</span>
                        </h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="mb-2">
                            <div class="input-group mb-1">
                                <input type="text" class="form-control form-control-sm" id="comment-search" placeholder="Išči komentarje...">
                                <button class="btn btn-outline-success btn-sm" onclick="searchComments()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm" id="new-comment-text" placeholder="Nov komentar...">
                                <button class="btn btn-success btn-sm" onclick="addNewComment()">
                                    <i class="fas fa-plus"></i> Dodaj
                                </button>
                            </div>
                        </div>
                        
                                <div id="comments-list">
            <div class="text-muted text-center">
                <i class="fas fa-info-circle me-2"></i>
                Kliknite na "Pogled" pri profesorju za prikaz komentarjev
            </div>
        </div>
        
        <div class="mt-2 text-center">
            <button class="btn btn-outline-secondary btn-sm" onclick="resetComments()">
                <i class="fas fa-refresh me-1"></i>Ponastavi
            </button>
        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Area -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-terminal me-2"></i>
                            Rezultati operacij
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="results-area" class="result-area" style="display: none;">
                            <pre id="results-output"></pre>
                        </div>
                        <div id="no-results" class="text-muted text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            Rezultati operacij se bodo prikazali tukaj
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
            <script>
        const SERVERLESS_BASE_URL = 'http://localhost:3000';
        
        // Globalne spremenljivke za izbranega profesorja
        let selectedProfessorId = null;
        let selectedProfessorName = null;
        
        // Check service status on page load
        document.addEventListener('DOMContentLoaded', function() {
        checkServiceStatus();
        loadComments();
    });
    
    // Check service status
    async function checkServiceStatus() {
        try {
            const response = await fetch(`${SERVERLESS_BASE_URL}/health`);
            const data = await response.json();
            
            if (data.status === 'OK') {
                document.getElementById('analytics-status').textContent = 'Online';
                document.getElementById('notification-status').textContent = 'Online';
                
                // Load initial data
                analyticsService('GET');
                notificationService('GET');
            }
        } catch (error) {
            document.getElementById('analytics-status').textContent = 'Offline';
            document.getElementById('notification-status').textContent = 'Offline';
        }
    }
    
    // Analytics Service
    async function analyticsService(method) {
        try {
            let response;
            const options = {
                method: method,
                headers: { 'Content-Type': 'application/json' }
            };
            
            if (method === 'POST') {
                options.body = JSON.stringify({
                    action: 'professor_view',
                    user_id: 1,
                    details: 'Pogled na profesorje iz serverless strani'
                });
            } else if (method === 'PUT') {
                options.body = JSON.stringify({
                    id: 1,
                    action: 'professor_edit',
                    user_id: 1,
                    details: 'Posodobitev profesorja'
                });
            }
            
            if (method === 'DELETE') {
                response = await fetch(`${SERVERLESS_BASE_URL}/api/analytics?id=1`, options);
            } else {
                response = await fetch(`${SERVERLESS_BASE_URL}/api/analytics`, options);
            }
            
            const data = await response.json();
            showResults(`Analytics Service (${method})`, data);
            
            // Update statistics if GET request
            if (method === 'GET' && data.data) {
                updateAnalyticsStats(data.data);
            }
            
        } catch (error) {
            showResults(`Analytics Service (${method}) - Error`, { error: error.message });
        }
    }
    
    // Notification Service
    async function notificationService(method) {
        try {
            let response;
            const options = {
                method: method,
                headers: { 'Content-Type': 'application/json' }
            };
            
            if (method === 'POST') {
                options.body = JSON.stringify({
                    user_id: 1,
                    message: 'Nova notifikacija iz serverless strani',
                    type: 'info',
                    priority: 'normal'
                });
            } else if (method === 'PUT') {
                options.body = JSON.stringify({
                    id: 1,
                    read: true
                });
            }
            
            if (method === 'DELETE') {
                response = await fetch(`${SERVERLESS_BASE_URL}/api/notifications?id=1`, options);
            } else {
                response = await fetch(`${SERVERLESS_BASE_URL}/api/notifications`, options);
            }
            
            const data = await response.json();
            showResults(`Notification Service (${method})`, data);
            
            // Update statistics if GET request
            if (method === 'GET' && data.data) {
                updateNotificationStats(data.data);
            }
            
        } catch (error) {
            showResults(`Notification Service (${method}) - Error`, { error: error.message });
        }
    }
    
    // Update Analytics Statistics
    function updateAnalyticsStats(data) {
        document.getElementById('total-logs').textContent = data.total_logs || '-';
        document.getElementById('today-logs').textContent = data.today_logs || '-';
        document.getElementById('popular-action').textContent = data.popular_actions?.[0] || '-';
    }
    
    // Update Notification Statistics
    function updateNotificationStats(data) {
        const total = data.length || 0;
        const unread = data.filter(n => !n.read).length || 0;
        const highPriority = data.filter(n => n.priority === 'high').length || 0;
        
        document.getElementById('total-notifications').textContent = total;
        document.getElementById('unread-notifications').textContent = unread;
        document.getElementById('high-priority').textContent = highPriority;
    }
    
    // Professor Management
    function viewProfessor(id) {
        console.log('Viewing professor with ID:', id);
        
        // Poišči profesorja v seznamu
        const professorElement = document.querySelector(`[data-professor-id="${id}"]`);
        console.log('Found professor element:', professorElement);
        
        if (professorElement) {
            const professorName = professorElement.querySelector('h6').textContent.replace('👤 ', '').trim();
            console.log('Professor name extracted:', professorName);
            
            // Preveri, ali je ta profesor že izbran
            if (selectedProfessorId === id) {
                showResults('View Professor', { 
                    action: 'view', 
                    id: id, 
                    name: professorName,
                    message: 'Ta profesor je že izbran' 
                });
                return;
            }
            
            // Odstrani prejšnjo oznako
            const previouslySelected = document.querySelector('.professor-item[style*="background-color: rgb(227, 242, 253)"]');
            if (previouslySelected) {
                previouslySelected.style.backgroundColor = '';
                previouslySelected.style.border = '';
            }
            
            // Označi profesorja z modro barvo
            professorElement.style.backgroundColor = '#e3f2fd';
            professorElement.style.border = '2px solid #2196F3';
            
            // Shrani izbranega profesorja v globalne spremenljivke
            selectedProfessorId = id;
            selectedProfessorName = professorName;
            
            // Posodobi naslov sekcije za komentarje
            document.getElementById('selected-professor-name').textContent = professorName;
            
            // Pokaži rezultate
            showResults('View Professor', { 
                action: 'view', 
                id: id, 
                name: professorName,
                message: 'Nalagam komentarje za profesorja...' 
            });
            
            // Naloži komentarje za tega profesorja
            loadCommentsForProfessor(id, professorName);
            
            // Odstrani vizualno oznako po 5 sekundah, vendar ohrani izbiro
            setTimeout(() => {
                professorElement.style.backgroundColor = '';
                professorElement.style.border = '';
            }, 5000);
        } else {
            console.error('Professor element not found for ID:', id);
            showResults('View Professor - Error', { 
                action: 'view', 
                id: id, 
                error: 'Profesor ni najden',
                message: 'Napaka: Profesor z ID ' + id + ' ni najden' 
            });
        }
    }
    
    async function editProfessor(id) {
        console.log('Editing professor:', id);
        
        const professorElement = document.querySelector(`[data-professor-id="${id}"]`);
        if (!professorElement) return;
        
        const currentName = professorElement.querySelector('h6').textContent.replace('👤 ', '').trim();
        const newName = prompt('Vnesite novo ime profesorja:', currentName);
        
        if (newName && newName.trim() && newName !== currentName) {
            try {
                // Označi spremembo z rumeno barvo
                professorElement.style.backgroundColor = '#fff3cd';
                professorElement.style.border = '2px solid #ffc107';
                
                // Pokaži rezultate
                showResults('Edit Professor', { 
                    action: 'edit', 
                    id: id, 
                    old_name: currentName,
                    new_name: newName.trim(),
                    message: 'Profesor se posodablja v bazi...' 
                });
                
                // Kliči PHP endpoint za spreminjanje profesorja
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=edit_professor&professor_id=${id}&professor_name=${encodeURIComponent(newName.trim())}`
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Edit professor response:', data);
                
                if (data.success) {
                    // Posodobi ime v UI
                    professorElement.querySelector('h6').innerHTML = `<i class="fas fa-user-tie me-2"></i>${newName.trim()}`;
                    
                    // Pokaži rezultate
                    showResults('Edit Professor', { 
                        action: 'edit', 
                        id: id, 
                        old_name: currentName,
                        new_name: newName.trim(),
                        message: 'Ime profesorja uspešno posodobljeno v bazi' 
                    });
                    
                    // Odstrani oznako po 3 sekundah
                    setTimeout(() => {
                        professorElement.style.backgroundColor = '';
                        professorElement.style.border = '';
                    }, 3000);
                } else {
                    throw new Error(data.message || 'Napaka pri posodabljanju profesorja');
                }
                
            } catch (error) {
                console.error('Error editing professor:', error);
                alert(`Napaka pri posodabljanju profesorja: ${error.message}`);
                
                // Ponastavi vizualno stanje
                professorElement.style.backgroundColor = '';
                professorElement.style.border = '';
                
                showResults('Edit Professor - Error', { 
                    error: error.message,
                    id: id,
                    old_name: currentName,
                    new_name: newName.trim()
                });
            }
        }
    }
    
    async function deleteProfessor(id) {
        console.log('Deleting professor:', id);
        
        const professorElement = document.querySelector(`[data-professor-id="${id}"]`);
        if (!professorElement) {
            console.error('Professor element not found:', id);
            return;
        }
        
        const professorName = professorElement.querySelector('h6').textContent.replace('👤 ', '').trim();
        
        if (confirm(`Ali ste prepričani, da želite izbrisati profesorja "${professorName}"?`)) {
            try {
                // Označi za brisanje z rdečo barvo
                professorElement.style.backgroundColor = '#f8d7da';
                professorElement.style.border = '2px solid #dc3545';
                
                // Pokaži rezultate
                showResults('Delete Professor', { 
                    action: 'delete', 
                    id: id, 
                    name: professorName,
                    message: 'Profesor se briše iz baze...' 
                });
                
                // Kliči PHP endpoint za brisanje profesorja
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_professor&professor_id=${id}`
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Delete professor response:', data);
                
                if (data.success) {
                    // Takoj odstrani profesorja iz UI
                    professorElement.style.transform = 'scale(0.8)';
                    professorElement.style.opacity = '0';
                    professorElement.style.transition = 'all 0.3s ease';
                    
                    setTimeout(() => {
                        professorElement.remove();
                        updateProfessorCount();
                        
                        // Posodobi rezultate
                        showResults('Delete Professor', { 
                            action: 'delete', 
                            id: id, 
                            name: professorName,
                            message: 'Profesor uspešno izbrisan iz baze' 
                        });
                    }, 300);
                } else {
                    throw new Error(data.message || 'Napaka pri brisanju profesorja');
                }
                
            } catch (error) {
                console.error('Error deleting professor:', error);
                alert(`Napaka pri brisanju profesorja: ${error.message}`);
                
                // Ponastavi vizualno stanje
                professorElement.style.backgroundColor = '';
                professorElement.style.border = '';
                
                showResults('Delete Professor - Error', { 
                    error: error.message,
                    id: id,
                    name: professorName
                });
            }
        }
    }
    
    function searchProfessors() {
        console.log('Searching professors...');
        
        const searchTerm = document.getElementById('professor-search').value.trim();
        if (!searchTerm) {
            // Ponovno naloži vse profesorje
            const professorElements = document.querySelectorAll('[data-professor-id]');
            professorElements.forEach(element => {
                element.style.display = 'block';
                element.style.backgroundColor = '';
                element.style.border = '';
            });
            return;
        }
        
        const professorElements = document.querySelectorAll('[data-professor-id]');
        let foundCount = 0;
        
        professorElements.forEach(element => {
            const name = element.querySelector('h6').textContent.toLowerCase();
            if (name.includes(searchTerm.toLowerCase())) {
                element.style.display = 'block';
                element.style.backgroundColor = '#fff3cd';
                element.style.border = '2px solid #ffc107';
                foundCount++;
            } else {
                element.style.display = 'none';
            }
        });
        
        showResults('Search Professors', { 
            search: searchTerm, 
            found: foundCount,
            total: professorElements.length,
            message: `Najdenih ${foundCount} profesorjev` 
        });
        
        // Odstrani oznako po 5 sekundah
        setTimeout(() => {
            professorElements.forEach(element => {
                if (element.style.display !== 'none') {
                    element.style.backgroundColor = '';
                    element.style.border = '';
                }
            });
        }, 5000);
    }
    
    // Comment Management
    async function loadComments() {
        try {
            const response = await fetch('graphql_simple.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    query: `{
                        komentarji {
                            id
                            komentar_tekst
                            profesor {
                                ime
                            }
                        }
                    }`
                })
            });
            
            const data = await response.json();
            if (data.data && data.data.komentarji) {
                displayComments(data.data.komentarji);
            } else {
                // Fallback - prikaži test komentarje
                displayComments([
                    {
                        id: 1,
                        komentar_tekst: 'Odličen profesor, zelo jasno razlaga.',
                        profesor: { ime: 'Test Profesor' }
                    },
                    {
                        id: 2,
                        komentar_tekst: 'Priporočam vsem študentom.',
                        profesor: { ime: 'Test Profesor' }
                    }
                ]);
            }
        } catch (error) {
            console.error('Error loading comments:', error);
            // Prikaži test komentarje v primeru napake
            displayComments([
                {
                    id: 1,
                    komentar_tekst: 'Odličen profesor, zelo jasno razlaga.',
                    profesor: { ime: 'Test Profesor' }
                },
                {
                    id: 2,
                    komentar_tekst: 'Priporočam vsem študentom.',
                    profesor: { ime: 'Test Profesor' }
                }
            ]);
        }
    }
    
    function displayComments(comments) {
        const container = document.getElementById('comments-list');
        container.innerHTML = '';
        
        if (!comments || comments.length === 0) {
            container.innerHTML = `
                <div class="text-muted text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Ni komentarjev za tega profesorja
                </div>
            `;
            return;
        }
        
        comments.forEach(comment => {
            if (comment.komentar_tekst) {
                const commentDiv = document.createElement('div');
                commentDiv.className = 'comment-item';
                commentDiv.setAttribute('data-comment-id', comment.id);
                
                // Formatiraj datum
                const commentDate = comment.timestamp ? new Date(comment.timestamp).toLocaleDateString('sl-SI') : 'Neznan datum';
                
                commentDiv.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <p class="mb-1 flex-grow-1">${comment.komentar_tekst}</p>
                        <small class="text-muted ms-2">${commentDate}</small>
                    </div>
                    <small class="text-muted d-block mb-2">
                        <i class="fas fa-user me-1"></i>${comment.profesor?.ime || 'Neznan profesor'}
                    </small>
                    <div class="action-buttons">
                        <button class="btn btn-outline-info btn-sm" onclick="viewComment(${comment.id})">
                            <i class="fas fa-eye me-1"></i>Pogled
                        </button>
                        <button class="btn btn-outline-warning btn-sm" onclick="editComment(${comment.id})">
                            <i class="fas fa-edit me-1"></i>Uredi
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="deleteComment(${comment.id})">
                            <i class="fas fa-trash me-1"></i>Izbriši
                        </button>
                    </div>
                `;
                container.appendChild(commentDiv);
            }
        });
        
        // Posodobi števec komentarjev
        updateCommentCount();
    }
    
            // Ponastavi komentarje
        function resetComments() {
            // Počisti globalne spremenljivke
            selectedProfessorId = null;
            selectedProfessorName = null;
            
            // Počisti vizualne oznake
            const previouslySelected = document.querySelector('.professor-item[style*="background-color: rgb(227, 242, 253)"]');
            if (previouslySelected) {
                previouslySelected.style.backgroundColor = '';
                previouslySelected.style.border = '';
            }
            
            document.getElementById('selected-professor-name').textContent = 'vse profesorje';
            document.getElementById('comments-list').innerHTML = `
                <div class="text-muted text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Kliknite na "Pogled" pri profesorju za prikaz komentarjev
                </div>
            `;
            
            showResults('Reset Comments', { 
                action: 'reset', 
                message: 'Komentarji ponastavljeni, izbira profesorja počiscena' 
            });
        }
        
        // Preveri stanje izbire profesorja
        function checkProfessorSelection() {
            if (selectedProfessorId && selectedProfessorName) {
                console.log('Selected professor:', selectedProfessorId, selectedProfessorName);
                return true;
            } else {
                console.log('No professor selected');
                return false;
            }
        }
    
    function viewComment(id) {
        console.log('Viewing comment:', id);
        
        const commentElement = document.querySelector(`[data-comment-id="${id}"]`);
        if (!commentElement) return;
        
        const commentText = commentElement.querySelector('p').textContent;
        
        // Označi komentar z modro barvo
        commentElement.style.backgroundColor = '#e3f2fd';
        commentElement.style.border = '2px solid #2196F3';
        
        showResults('View Comment', { 
            action: 'view', 
            id: id, 
            text: commentText,
            message: 'Komentar označen za ogled' 
        });
        
        // Odstrani oznako po 3 sekundah
        setTimeout(() => {
            commentElement.style.backgroundColor = '';
            commentElement.style.border = '';
        }, 3000);
    }
    
    async function editComment(id) {
        console.log('Editing comment:', id);
        
        const commentElement = document.querySelector(`[data-comment-id="${id}"]`);
        if (!commentElement) return;
        
        const currentText = commentElement.querySelector('p').textContent;
        const newText = prompt('Uredite komentar:', currentText);
        
        if (newText && newText.trim() && newText !== currentText) {
            try {
                // Označi spremembo z rumeno barvo
                commentElement.style.backgroundColor = '#fff3cd';
                commentElement.style.border = '2px solid #ffc107';
                
                showResults('Edit Comment', { 
                    action: 'edit', 
                    id: id, 
                    old_text: currentText,
                    new_text: newText.trim(),
                    message: 'Komentar se posodablja v bazi...' 
                });
                
                // Kliči PHP endpoint za spreminjanje komentarja
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=edit_comment&comment_id=${id}&comment_text=${encodeURIComponent(newText.trim())}`
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Edit comment response:', data);
                
                if (data.success) {
                    // Posodobi komentar v UI
                    commentElement.querySelector('p').textContent = newText.trim();
                    
                    showResults('Edit Comment', { 
                        action: 'edit', 
                        id: id, 
                        old_text: currentText,
                        new_text: newText.trim(),
                        message: 'Komentar uspešno posodobljen v bazi' 
                    });
                    
                    // Odstrani oznako po 3 sekundah
                    setTimeout(() => {
                        commentElement.style.backgroundColor = '';
                        commentElement.style.border = '';
                    }, 3000);
                } else {
                    throw new Error(data.message || 'Napaka pri posodabljanju komentarja');
                }
                
            } catch (error) {
                console.error('Error editing comment:', error);
                alert(`Napaka pri posodabljanju komentarja: ${error.message}`);
                
                // Ponastavi vizualno stanje
                commentElement.style.backgroundColor = '';
                commentElement.style.border = '';
                
                showResults('Edit Comment - Error', { 
                    error: error.message,
                    id: id,
                    old_text: currentText,
                    new_text: newText.trim()
                });
            }
        }
    }
    
    async function deleteComment(id) {
        console.log('Deleting comment:', id);
        
        const commentElement = document.querySelector(`[data-comment-id="${id}"]`);
        if (!commentElement) {
            console.error('Comment element not found:', id);
            return;
        }
        
        const commentText = commentElement.querySelector('p').textContent;
        
        if (confirm(`Ali ste prepričani, da želite izbrisati komentar: "${commentText.substring(0, 50)}..."?`)) {
            try {
                // Označi za brisanje z rdečo barvo
                commentElement.style.backgroundColor = '#f8d7da';
                commentElement.style.border = '2px solid #dc3545';
                
                showResults('Delete Comment', { 
                    action: 'delete', 
                    id: id, 
                    text: commentText,
                    message: 'Komentar se briše iz baze...' 
                });
                
                // Kliči PHP endpoint za brisanje komentarja
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_comment&comment_id=${id}`
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Delete comment response:', data);
                
                if (data.success) {
                    // Takoj odstrani komentar iz UI
                    commentElement.style.transform = 'scale(0.8)';
                    commentElement.style.opacity = '0';
                    commentElement.style.transition = 'all 0.3s ease';
                    
                    setTimeout(() => {
                        commentElement.remove();
                        updateCommentCount();
                        
                        // Posodobi rezultate
                        showResults('Delete Comment', { 
                            action: 'delete', 
                            id: id, 
                            text: commentText,
                            message: 'Komentar uspešno izbrisan iz baze' 
                        });
                    }, 300);
                } else {
                    throw new Error(data.message || 'Napaka pri brisanju komentarja');
                }
                
            } catch (error) {
                console.error('Error deleting comment:', error);
                alert(`Napaka pri brisanju komentarja: ${error.message}`);
                
                // Ponastavi vizualno stanje
                commentElement.style.backgroundColor = '';
                commentElement.style.border = '';
                
                showResults('Delete Comment - Error', { 
                    error: error.message,
                    id: id,
                    text: commentText
                });
            }
        }
    }
    
    function searchComments() {
        console.log('Searching comments...');
        
        const searchTerm = document.getElementById('comment-search').value.trim();
        if (!searchTerm) {
            // Ponovno naloži vse komentarje
            const commentElements = document.querySelectorAll('[data-comment-id]');
            commentElements.forEach(element => {
                element.style.display = 'block';
                element.style.backgroundColor = '';
                element.style.border = '';
            });
            return;
        }
        
        const commentElements = document.querySelectorAll('[data-comment-id]');
        let foundCount = 0;
        
        commentElements.forEach(element => {
            const text = element.querySelector('p').textContent.toLowerCase();
            if (text.includes(searchTerm.toLowerCase())) {
                element.style.display = 'block';
                element.style.backgroundColor = '#fff3cd';
                element.style.border = '2px solid #ffc107';
                foundCount++;
            } else {
                element.style.display = 'none';
            }
        });
        
        showResults('Search Comments', { 
            search: searchTerm, 
            found: foundCount,
            total: commentElements.length,
            message: `Najdenih ${foundCount} komentarjev` 
        });
        
        // Odstrani oznako po 5 sekundah
        setTimeout(() => {
            commentElements.forEach(element => {
                if (element.style.display !== 'none') {
                    element.style.backgroundColor = '';
                    element.style.border = '';
                }
            });
        }, 5000);
    }
    
    // Load professors on page load
    function loadProfessors() {
        // Profesorji so že naloženi v PHP z pravilnimi ID-ji
        // Ne prepisujemo ID-jev, ker so že pravilno nastavljeni v PHP
        console.log('Professors loaded, checking IDs...');
        
        const professorElements = document.querySelectorAll('.professor-item');
        professorElements.forEach((element) => {
            const currentId = element.getAttribute('data-professor-id');
            console.log('Professor element ID:', currentId, 'Name:', element.querySelector('h6').textContent.trim());
        });
        
        // Posodobi števec profesorjev
        updateProfessorCount();
    }
    
    // Naloži komentarje za določenega profesorja
    async function loadCommentsForProfessor(professorId, professorName) {
        try {
            console.log('Loading comments for professor:', professorId, professorName);
            
            // Kliči PHP endpoint za pridobivanje komentarjev
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_comments&professor_id=${professorId}`
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Comments response:', data);
            
            if (data.success && data.comments) {
                // Pretvori PHP podatke v format, ki ga pričakuje displayComments
                const formattedComments = data.comments.map(comment => ({
                    id: comment.komentar_id || comment.id,
                    komentar_tekst: comment.komentar_tekst || comment.komentar,
                    profesor: { ime: professorName },
                    timestamp: comment.komentar_created_at || comment.created_at || new Date().toISOString()
                }));
                
                // Prikaži komentarje
                displayComments(formattedComments);
                
                // Posodobi rezultate
                showResults('Load Comments', { 
                    action: 'load_comments', 
                    professor_id: professorId, 
                    professor_name: professorName,
                    comments_count: formattedComments.length,
                    message: `Naloženih ${formattedComments.length} dejanskih komentarjev za ${professorName}` 
                });
            } else {
                throw new Error('Ni komentarjev ali napaka pri nalaganju');
            }
            
        } catch (error) {
            console.error('Error loading comments:', error);
            
            // Prikaži sporočilo o napaki
            document.getElementById('comments-list').innerHTML = `
                <div class="text-danger text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Napaka pri nalaganju komentarjev: ${error.message}
                </div>
            `;
            
            showResults('Load Comments - Error', { 
                error: error.message,
                professor_id: professorId,
                professor_name: professorName
            });
        }
    }
    
    // Update professor count
    function updateProfessorCount() {
        const professorElements = document.querySelectorAll('[data-professor-id]');
        const count = professorElements.length;
        
        // Posodobi števec v header-ju, če obstaja
        const headerElement = document.querySelector('.service-header h1');
        if (headerElement) {
            const currentText = headerElement.textContent;
            if (currentText.includes('(')) {
                headerElement.innerHTML = `<i class="fas fa-cloud me-3"></i>🔄 Serverless Storitve <small class="text-light">(${count} profesorjev)</small>`;
            } else {
                headerElement.innerHTML = `<i class="fas fa-cloud me-3"></i>🔄 Serverless Storitve <small class="text-light">(${count} profesorjev)</small>`;
            }
        }
        
        console.log(`Professor count updated: ${count}`);
    }
    
    // Update comment count
    function updateCommentCount() {
        const commentElements = document.querySelectorAll('[data-comment-id]');
        const count = commentElements.length;
        
        console.log(`Comment count updated: ${count}`);
    }
    
    // Add new professor
    async function addNewProfessor() {
        const nameInput = document.getElementById('new-professor-name');
        const name = nameInput.value.trim();
        
        if (!name) {
            alert('Vnesite ime profesorja!');
            return;
        }
        
        try {
            // Kliči PHP endpoint za dodajanje profesorja
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_professor&professor_name=${encodeURIComponent(name)}`
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Add professor response:', data);
            
            if (data.success) {
                // Počisti input
                nameInput.value = '';
                
                // Ponovno naloži profesorje iz baze
                window.location.reload();
                
                // Pokaži rezultate
                showResults('Add Professor', { 
                    action: 'add', 
                    name: name,
                    message: 'Nov profesor uspešno dodan v bazo' 
                });
            } else {
                throw new Error(data.message || 'Napaka pri dodajanju profesorja');
            }
            
        } catch (error) {
            console.error('Error adding professor:', error);
            alert(`Napaka pri dodajanju profesorja: ${error.message}`);
            
            showResults('Add Professor - Error', { 
                error: error.message,
                name: name
            });
        }
    }
    
    // Add new comment
    async function addNewComment() {
        const commentInput = document.getElementById('new-comment-text');
        const commentText = commentInput.value.trim();
        
        if (!commentText) {
            alert('Vnesite komentar!');
            return;
        }
        
        // Preveri, ali je izbran profesor
        if (!selectedProfessorId || !selectedProfessorName) {
            alert('Najprej izberite profesorja (kliknite na "Pogled")!');
            return;
        }
        
        try {
            // Kliči PHP endpoint za dodajanje komentarja
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_comment&professor_id=${selectedProfessorId}&comment_text=${encodeURIComponent(commentText)}`
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Add comment response:', data);
            
            if (data.success) {
                // Počisti input
                commentInput.value = '';
                
                // Ponovno naloži komentarje za tega profesorja
                await loadCommentsForProfessor(selectedProfessorId, selectedProfessorName);
                
                // Pokaži rezultate
                showResults('Add Comment', { 
                    action: 'add', 
                    professor_id: selectedProfessorId,
                    professor_name: selectedProfessorName,
                    text: commentText,
                    message: `Nov komentar uspešno dodan v bazo za ${selectedProfessorName}` 
                });
            } else {
                throw new Error(data.message || 'Napaka pri dodajanju komentarja');
            }
            
        } catch (error) {
            console.error('Error adding comment:', error);
            alert(`Napaka pri dodajanju komentarja: ${error.message}`);
            
            showResults('Add Comment - Error', { 
                error: error.message,
                professor_id: selectedProfessorId,
                professor_name: selectedProfessorName,
                text: commentText
            });
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadProfessors();
        console.log('Serverless services page loaded');
        
        // Dodaj event listener za Enter tipko v input poljih
        document.getElementById('professor-search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProfessors();
            }
        });
        
        document.getElementById('new-professor-name').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                addNewProfessor();
            }
        });
        
        document.getElementById('comment-search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchComments();
            }
        });
        
        document.getElementById('new-comment-text').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                addNewComment();
            }
        });
    });
    
    // Show Results
    function showResults(title, data) {
        document.getElementById('results-area').style.display = 'block';
        document.getElementById('no-results').style.display = 'none';
        
        const output = document.getElementById('results-output');
        output.textContent = `${title}:\n${JSON.stringify(data, null, 2)}`;
        
        // Scroll to results
        document.getElementById('results-area').scrollIntoView({ behavior: 'smooth' });
    }
    </script>
</body>
</html>
