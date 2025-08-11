<?php
session_start();
require_once 'models/Profesor.php';
require_once 'models/Kviz.php';

$profesor = new Profesor();
$kviz = new Kviz();

// Pridobi statistiko
$statistika_profesorjev = $profesor->getAll();
$stevilo_profesorjev = $statistika_profesorjev->rowCount();

$statistika_kvizov = $kviz->getStatistics();
$vsi_rezultati = $kviz->getAllResults();

// Dodaj novega profesorja
if (isset($_POST['add_profesor'])) {
    $novi_profesor = new Profesor();
    $novi_profesor->ime = trim($_POST['ime'] ?? '');
    $novi_profesor->url = trim($_POST['url'] ?? '');
    $novi_profesor->komentar = trim($_POST['komentar'] ?? '');
    
    if (empty($novi_profesor->ime) || empty($novi_profesor->url) || empty($novi_profesor->komentar)) {
        $error = 'Vsa polja so obvezna!';
    } else {
        if ($novi_profesor->create()) {
            $success = 'Profesor je bil uspešno dodan!';
            // Počisti obrazec
            $_POST = array();
        } else {
            $error = 'Napaka pri dodajanju profesorja!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administracija - Profesorji Komentarji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .admin-card {
            border-left: 4px solid #28a745;
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
        <h1 class="display-4 text-primary mb-4">
            <i class="fas fa-cog me-3"></i>
            Administracija
        </h1>

        <!-- Sporočila -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check me-2"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Statistika -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="fas fa-users me-2"></i>
                            Profesorji
                        </h3>
                        <h2 class="display-4"><?php echo $stevilo_profesorjev; ?></h2>
                        <p class="mb-0">Skupno število</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="fas fa-question-circle me-2"></i>
                            Kvizi
                        </h3>
                        <h2 class="display-4"><?php echo $statistika_kvizov['skupno_kvizov'] ?? 0; ?></h2>
                        <p class="mb-0">Opravljenih</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line me-2"></i>
                            Povprečje
                        </h3>
                        <h2 class="display-4"><?php echo round($statistika_kvizov['povprecje'] ?? 0, 1); ?>%</h2>
                        <p class="mb-0">Uspešnost</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Dodaj novega profesorja -->
            <div class="col-md-6">
                <div class="card admin-card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-plus me-2"></i>
                            Dodaj novega profesorja
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="ime" class="form-label">Ime in priimek</label>
                                <input type="text" class="form-control" id="ime" name="ime" 
                                       value="<?php echo htmlspecialchars($_POST['ime'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="url" class="form-label">URL profila</label>
                                <input type="url" class="form-control" id="url" name="url" 
                                       value="<?php echo htmlspecialchars($_POST['url'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="komentar" class="form-label">Komentar</label>
                                <textarea class="form-control" id="komentar" name="komentar" rows="3" required><?php echo htmlspecialchars($_POST['komentar'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" name="add_profesor" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i>
                                Dodaj profesorja
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Hitri ukazi -->
            <div class="col-md-6">
                <div class="card admin-card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-tools me-2"></i>
                            Hitri ukazi
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-eye me-2"></i>
                                Pregled vseh profesorjev
                            </a>
                            <a href="quiz.php" class="btn btn-outline-success">
                                <i class="fas fa-play me-2"></i>
                                Testiraj kviz
                            </a>
                            <a href="import_data.py" class="btn btn-outline-info">
                                <i class="fas fa-download me-2"></i>
                                Uvozi CSV podatke
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zadnji rezultati kvizov -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Zadnji rezultati kvizov
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($vsi_rezultati)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Uporabnik</th>
                                            <th>Pravilni</th>
                                            <th>Skupno</th>
                                            <th>Rezultat</th>
                                            <th>Datum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($vsi_rezultati, 0, 10) as $rezultat): ?>
                                            <tr>
                                                <td><?php echo $rezultat['id']; ?></td>
                                                <td>
                                                    <code><?php echo substr($rezultat['uporabnik_id'], 0, 8); ?>...</code>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <?php echo $rezultat['stevilo_pravilnih']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $rezultat['stevilo_vprasanj']; ?></td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo $rezultat['rezultat']; ?>%
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('d.m.Y H:i', strtotime($rezultat['created_at'])); ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Ni rezultatov kvizov</h5>
                                <p class="text-muted">Rezultati se bodo prikazali, ko bodo uporabniki rešili kvize</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
