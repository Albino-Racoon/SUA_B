<?php
session_start();
require_once 'models/Profesor.php';
require_once 'models/Kviz.php';

$profesor = new Profesor();
$kviz = new Kviz();

// Generiraj kviz, če je zahtevan
if (isset($_POST['start_quiz'])) {
    $stevilo_profesorjev = min(10, max(1, intval($_POST['stevilo_profesorjev'] ?? 10)));
    $stevilo_vprasanj = min(10, max(1, intval($_POST['stevilo_vprasanj'] ?? 10)));
    
    // Pridobi izbrane profesorje
    $selected_professors = $_POST['selected_professors'] ?? [];
    
    $quiz_data = $kviz->generateQuiz($stevilo_profesorjev, $stevilo_vprasanj, $selected_professors);
    $_SESSION['quiz_data'] = $quiz_data;
    $_SESSION['quiz_started'] = true;
    header('Location: quiz.php?step=quiz');
    exit;
}

// Preveri odgovore kviza
if (isset($_POST['submit_quiz']) && isset($_SESSION['quiz_data'])) {
    $user_answers = $_POST['answers'] ?? [];
    $quiz_data = $_SESSION['quiz_data'];
    
    $rezultati = $kviz->checkAnswers($quiz_data, $user_answers);
    
    // Shrani rezultat
    $kviz->uporabnik_id = session_id();
    $kviz->stevilo_pravilnih = $rezultati['pravilni'];
    $kviz->stevilo_vprasanj = $rezultati['skupno'];
    $kviz->rezultat = $rezultati['rezultat_procent'];
    $kviz->saveResult();
    
    $_SESSION['quiz_results'] = $rezultati;
    header('Location: quiz.php?step=results');
    exit;
}

$step = $_GET['step'] ?? 'setup';

// Pridobi vse profesorje za izbiro
$vsi_profesorji = $profesor->getAllForSelection();
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kviz - Profesorji Komentarji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .quiz-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .question-card {
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
        }
        .answer-option {
            cursor: pointer;
            transition: all 0.2s;
        }
        .answer-option:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        .answer-option.selected {
            background-color: #007bff;
            color: white;
        }
        .result-correct {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .result-incorrect {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .professor-selection {
            max-height: 300px;
            overflow-y: auto;
        }
        .professor-checkbox {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigacija -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i>
                Profesorji Komentarji
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>Domov
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($step === 'setup'): ?>
            <!-- Nastavitve kviza -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card quiz-card">
                        <div class="card-body text-center">
                            <h2 class="card-title mb-4">
                                <i class="fas fa-cog me-2"></i>Nastavitve Kviza
                            </h2>
                            
                            <form method="POST" action="quiz.php">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="stevilo_profesorjev" class="form-label">
                                            <i class="fas fa-users me-2"></i>Število profesorjev
                                        </label>
                                        <select class="form-select" id="stevilo_profesorjev" name="stevilo_profesorjev">
                                            <option value="5">5 profesorjev</option>
                                            <option value="10" selected>10 profesorjev</option>
                                            <option value="15">15 profesorjev</option>
                                            <option value="20">20 profesorjev</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="stevilo_vprasanj" class="form-label">
                                            <i class="fas fa-question-circle me-2"></i>Število vprašanj
                                        </label>
                                        <select class="form-select" id="stevilo_vprasanj" name="stevilo_vprasanj">
                                            <option value="5">5 vprašanj</option>
                                            <option value="10" selected>10 vprašanj</option>
                                            <option value="15">15 vprašanj</option>
                                            <option value="20">20 vprašanj</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Izbira profesorjev -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-user-check me-2"></i>Izbira profesorjev (izberite želene ali pustite prazno za vse)
                                    </label>
                                    <div class="professor-selection border rounded p-3 bg-light">
                                        <div class="row">
                                            <?php foreach ($vsi_profesorji as $prof): ?>
                                                <div class="col-md-6">
                                                    <div class="form-check professor-checkbox">
                                                        <input class="form-check-input" type="checkbox" 
                                                               name="selected_professors[]" 
                                                               value="<?php echo $prof['id']; ?>" 
                                                               id="prof_<?php echo $prof['id']; ?>">
                                                        <label class="form-check-label text-dark" for="prof_<?php echo $prof['id']; ?>">
                                                            <?php echo htmlspecialchars($prof['ime']); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Če ne izberete nobenega profesorja, bo kviz vključeval vse profesorje.
                                    </small>
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-shield-alt me-2"></i>
                                        <strong>Anonimizacija:</strong> V komentarjih bodo imena profesorjev zamenjana z "Profesor 1", "Profesor 2" itd. 
                                        V možnih odgovorih bodo prikazana prava imena.
                                    </div>
                                </div>
                                
                                <button type="submit" name="start_quiz" class="btn btn-light btn-lg">
                                    <i class="fas fa-play me-2"></i>Začni Kviz
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($step === 'quiz' && isset($_SESSION['quiz_data'])): ?>
            <!-- Kviz vprašanja -->
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card quiz-card mb-4">
                        <div class="card-body text-center">
                            <h2 class="mb-0">
                                <i class="fas fa-question-circle me-2"></i>
                                Kviz v teku...
                            </h2>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Pozor:</strong> V komentarjih so imena profesorjev anonimizirana za objektivnost kviza. 
                                V možnih odgovorih so prikazana prava imena.
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="quiz.php" id="quizForm">
                        <?php 
                        $quiz_data = $_SESSION['quiz_data'];
                        foreach ($quiz_data['vprasanja'] as $index => $vprasanje): 
                        ?>
                            <div class="card question-card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <span class="badge bg-primary me-2">Vprašanje <?php echo $index + 1; ?></span>
                                        Komentar pripada profesorju:
                                    </h5>
                                    <div class="alert alert-info">
                                        <i class="fas fa-comment me-2"></i>
                                        "<?php echo htmlspecialchars($vprasanje['komentar']); ?>"
                                    </div>
                                    
                                    <div class="row">
                                        <?php foreach ($vprasanje['mozni_odgovori'] as $odgovor): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="answer-option p-3 border rounded" 
                                                     onclick="selectAnswer(this, '<?php echo htmlspecialchars($odgovor); ?>', <?php echo $index; ?>)">
                                                    <input type="radio" name="answers[<?php echo $index; ?>]" 
                                                           value="<?php echo htmlspecialchars($odgovor); ?>" 
                                                           style="display: none;">
                                                    <i class="fas fa-user-tie me-2"></i>
                                                    <?php echo htmlspecialchars($odgovor); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="text-center mt-4">
                            <button type="submit" name="submit_quiz" class="btn btn-success btn-lg px-5">
                                <i class="fas fa-check me-2"></i>
                                Zaključi Kviz
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($step === 'results' && isset($_SESSION['quiz_results'])): ?>
            <!-- Rezultati kviza -->
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card quiz-card mb-4">
                        <div class="card-body text-center">
                            <h1 class="display-4 mb-3">
                                <i class="fas fa-trophy me-3"></i>
                                Rezultati Kviza
                            </h1>
                            
                            <?php 
                            $rezultati = $_SESSION['quiz_results'];
                            $rezultat_procent = $rezultati['rezultat_procent'];
                            ?>
                            
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card bg-light text-dark">
                                        <div class="card-body">
                                            <h3 class="text-success"><?php echo $rezultati['pravilni']; ?></h3>
                                            <p class="mb-0">Pravilnih odgovorov</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light text-dark">
                                        <div class="card-body">
                                            <h3 class="text-danger"><?php echo $rezultati['napacni']; ?></h3>
                                            <p class="mb-0">Napačnih odgovorov</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light text-dark">
                                        <div class="card-body">
                                            <h3 class="text-primary"><?php echo $rezultat_procent; ?>%</h3>
                                            <p class="mb-0">Uspešnost</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h4>Povzetek odgovorov:</h4>
                                <?php foreach ($rezultati['odgovori'] as $index => $odgovor): ?>
                                    <div class="card mb-2 <?php echo $odgovor['je_pravilen'] ? 'result-correct' : 'result-incorrect'; ?>">
                                        <div class="card-body">
                                            <h6>Vprašanje <?php echo $index + 1; ?></h6>
                                            <p class="mb-2"><em>"<?php echo htmlspecialchars($odgovor['vprasanje']); ?>"</p>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Vaš odgovor:</strong> 
                                                    <span class="<?php echo $odgovor['je_pravilen'] ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo htmlspecialchars($odgovor['user_answer']); ?>
                                                    </span>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Pravilen odgovor:</strong> 
                                                    <span class="text-success">
                                                        <?php echo htmlspecialchars($odgovor['pravilen_odgovor']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="d-flex justify-content-center gap-3">
                                <a href="quiz.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-redo me-2"></i>
                                    Nov Kviz
                                </a>
                                <a href="index.php" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-home me-2"></i>
                                    Domov
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectAnswer(element, value, index) {
            // Odstrani prejšnjo izbiro za to vprašanje
            const questionCard = element.closest('.question-card');
            questionCard.querySelectorAll('.answer-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Označi izbrano možnost
            element.classList.add('selected');
            
            // Označi radio gumb
            const radio = element.querySelector('input[type="radio"]');
            radio.checked = true;
        }
    </script>
</body>
</html>
