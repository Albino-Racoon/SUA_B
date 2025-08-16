<?php
session_start();
require_once 'models/Profesor.php';
require_once 'models/Kviz.php';

$profesor = new Profesor();
$kviz = new Kviz();

// Pridobi vse profesorje z njihovimi komentarji
$profesorji = $profesor->getAllWithComments();
$profesorji_data = $profesorji->fetchAll(PDO::FETCH_ASSOC);

// Pridobi statistiko
$statistika = $kviz->getStatistics();
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profesorji Komentarji - Glavna stran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
            color: #2c3e50 !important;
        }
        .card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-quiz {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
            color: white;
        }
        .btn-quiz:hover {
            background: linear-gradient(135deg, #e585f0 0%, #e54b5f 100%);
            color: white;
        }
        .comment-item {
            border-left: 3px solid #007bff;
            padding-left: 15px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        .comment-item:last-child {
            margin-bottom: 0;
        }
        .professor-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .professor-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .professor-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px 10px 0 0 !important;
        }
        .comment-count {
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9em;
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home me-1"></i>Domov
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quiz.php">
                            <i class="fas fa-question-circle me-1"></i>Kviz
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">
                            <i class="fas fa-cog me-1"></i>Administracija
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="graphql_test.php">
                            <i class="fas fa-code me-1"></i>GraphQL API
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profesorji_graphql.php">
                            <i class="fas fa-users me-1"></i>Profesorji (GraphQL)
                        </a>
                    </li>
                    <li class="nav-item">
                                        <a class="nav-link" href="/api/docs/">
                    <i class="fas fa-book me-1"></i>API Dokumentacija
                </a>
                    </li>
                </ul>
                <form class="d-flex" method="GET" action="index.php">
                    <input class="form-control me-2" type="search" name="search" placeholder="Išči profesorje..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Statistika -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-users me-2"></i>Komentarji
                        </h5>
                        <h3 class="mb-0"><?php echo count($profesorji_data); ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-question-circle me-2"></i>Kvizi
                        </h5>
                        <h3 class="mb-0"><?php echo $statistika['skupno_kvizov'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-trophy me-2"></i>Povprečje
                        </h5>
                        <h3 class="mb-0"><?php echo number_format($statistika['povprecje'] ?? 0, 1); ?>%</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gumb za kviz -->
        <div class="text-center mb-4">
            <a href="quiz.php" class="btn btn-quiz btn-lg">
                <i class="fas fa-play me-2"></i>Začni Kviz
            </a>
        </div>

        <!-- Seznam profesorjev -->
        <div class="row">
            <?php foreach ($profesorji_data as $profesor_item): ?>
                <div class="col-12">
                    <div class="card professor-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-user-tie me-2"></i>
                                <?php echo htmlspecialchars($profesor_item['ime']); ?>
                            </h5>
                            <span class="comment-count">
                                <i class="fas fa-comments me-1"></i>
                                <?php echo $profesor_item['stevilo_komentarjev']; ?> komentarjev
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <p class="text-muted mb-3">
                                        <i class="fas fa-link me-1"></i>
                                        <a href="<?php echo htmlspecialchars($profesor_item['url']); ?>" target="_blank" class="text-decoration-none">
                                            <?php echo htmlspecialchars($profesor_item['url']); ?>
                                        </a>
                                    </p>
                                    
                                    <?php
                                    // Pridobi vse komentarje za tega profesorja
                                    $komentarji = $profesor->getByIdWithComments($profesor_item['id']);
                                    ?>
                                    
                                    <h6 class="mb-3">
                                        <i class="fas fa-comments me-2"></i>Komentarji:
                                    </h6>
                                    
                                    <?php if (!empty($komentarji) && isset($komentarji[0]['komentar_tekst'])): ?>
                                        <?php foreach ($komentarji as $komentar): ?>
                                            <?php if ($komentar['komentar_tekst']): ?>
                                                <div class="comment-item">
                                                    <p class="mb-1"><?php echo htmlspecialchars($komentar['komentar_tekst']); ?></p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo date('d.m.Y H:i', strtotime($komentar['komentar_created_at'])); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">Ni komentarjev.</p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="text-end">
                                        <a href="quiz.php?professor=<?php echo $profesor_item['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-question-circle me-1"></i>Kviz za tega profesorja
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Serverless Storitve Link -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h4 class="mb-3">
                            <i class="fas fa-cloud me-2"></i>
                            🔄 Serverless Storitve
                        </h4>
                        <p class="text-muted mb-3">
                            Posebna stran za oblačno računalništvo - upravljanje profesorjev in komentarjev
                        </p>
                        <a href="serverless_services.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-external-link-alt me-2"></i>
                            Odpri Serverless Storitve
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    

</body>
</html>
