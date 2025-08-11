<?php
session_start();
require_once 'models/Profesor.php';

$profesor = new Profesor();
$message = '';
$error = '';

// Pridobi profesorja za urejanje
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $profesor_data = $profesor->getById($id);
    
    if (!$profesor_data) {
        header('Location: index.php');
        exit;
    }
    
    $profesor->id = $profesor_data['id'];
    $profesor->ime = $profesor_data['ime'];
    $profesor->url = $profesor_data['url'];
    $profesor->komentar = $profesor_data['komentar'];
} else {
    header('Location: index.php');
    exit;
}

// Obdelaj obrazec
if ($_POST) {
    $profesor->ime = trim($_POST['ime'] ?? '');
    $profesor->url = trim($_POST['url'] ?? '');
    $profesor->komentar = trim($_POST['komentar'] ?? '');
    
    if (empty($profesor->ime) || empty($profesor->url) || empty($profesor->komentar)) {
        $error = 'Vsa polja so obvezna!';
    } else {
        if ($profesor->update()) {
            $message = 'Profesor je bil uspešno posodobljen!';
            // Osveži podatke
            $profesor_data = $profesor->getById($id);
            $profesor->ime = $profesor_data['ime'];
            $profesor->url = $profesor_data['url'];
            $profesor->komentar = $profesor_data['komentar'];
        } else {
            $error = 'Napaka pri posodabljanju profesorja!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uredi Profesorja - Profesorji Komentarji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Uredi Profesorja
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check me-2"></i>
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="ime" class="form-label">
                                    <i class="fas fa-user-tie me-2"></i>
                                    Ime in priimek
                                </label>
                                <input type="text" class="form-control" id="ime" name="ime" 
                                       value="<?php echo htmlspecialchars($profesor->ime); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="url" class="form-label">
                                    <i class="fas fa-link me-2"></i>
                                    URL profila
                                </label>
                                <input type="url" class="form-control" id="url" name="url" 
                                       value="<?php echo htmlspecialchars($profesor->url); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="komentar" class="form-label">
                                    <i class="fas fa-comment me-2"></i>
                                    Komentar
                                </label>
                                <textarea class="form-control" id="komentar" name="komentar" rows="5" required><?php echo htmlspecialchars($profesor->komentar); ?></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Nazaj
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Shrani spremembe
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
