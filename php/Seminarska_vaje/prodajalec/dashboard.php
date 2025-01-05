<?php
require_once '../config.php';

// Check if seller is logged in
if (!isset($_SESSION['seller_id'])) {
    header('Location: login.php');
    exit;
}

// Get seller info
$stmt = $pdo->prepare("SELECT * FROM prodajalec WHERE id = ? AND aktiven = 1");
$stmt->execute([$_SESSION['seller_id']]);
$seller = $stmt->fetch();

if (!$seller) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle form submissions for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $stmt = $pdo->prepare("UPDATE prodajalec SET ime = ?, priimek = ?, email = ? WHERE id = ?");
    $stmt->execute([$_POST['ime'], $_POST['priimek'], $_POST['email'], $_SESSION['seller_id']]);
    
    if (!empty($_POST['novo_geslo'])) {
        $stmt = $pdo->prepare("UPDATE prodajalec SET geslo = ? WHERE id = ?");
        $stmt->execute([password_hash($_POST['novo_geslo'], PASSWORD_DEFAULT), $_SESSION['seller_id']]);
    }
    
    header('Location: dashboard.php?message=profile_updated');
    exit;
}
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prodajalec Nadzorna Plošča</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Prodajalec Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Nadzorna Plošča</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="narocila.php">Naročila</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="artikli.php">Artikli</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="stranke.php">Stranke</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <span class="nav-item nav-link text-light">Pozdravljen, <?php echo htmlspecialchars($seller['ime'] . ' ' . $seller['priimek']); ?></span>
                    <a class="nav-link" href="logout.php">Odjava</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success">
                <?php 
                    switch($_GET['message']) {
                        case 'profile_updated':
                            echo 'Profil je bil uspešno posodobljen.';
                            break;
                    }
                ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Posodobi Profil</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="mb-3">
                                <label for="ime" class="form-label">Ime</label>
                                <input type="text" class="form-control" id="ime" name="ime" value="<?php echo htmlspecialchars($seller['ime']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="priimek" class="form-label">Priimek</label>
                                <input type="text" class="form-control" id="priimek" name="priimek" value="<?php echo htmlspecialchars($seller['priimek']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($seller['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="novo_geslo" class="form-label">Novo Geslo (pustite prazno če ne želite spremeniti)</label>
                                <input type="password" class="form-control" id="novo_geslo" name="novo_geslo">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Posodobi Profil</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Statistika</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get order statistics
                        $stats = $pdo->query("
                            SELECT 
                                COUNT(CASE WHEN status = 'neobdelano' THEN 1 END) as neobdelana,
                                COUNT(CASE WHEN status = 'potrjeno' THEN 1 END) as potrjena,
                                COUNT(CASE WHEN status = 'preklicano' THEN 1 END) as preklicana,
                                COUNT(CASE WHEN status = 'stornirano' THEN 1 END) as stornirana
                            FROM narocilo
                        ")->fetch();
                        ?>
                        <h5>Naročila</h5>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Neobdelana naročila
                                <span class="badge bg-primary rounded-pill"><?php echo $stats['neobdelana']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Potrjena naročila
                                <span class="badge bg-success rounded-pill"><?php echo $stats['potrjena']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Preklicana naročila
                                <span class="badge bg-danger rounded-pill"><?php echo $stats['preklicana']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Stornirana naročila
                                <span class="badge bg-warning rounded-pill"><?php echo $stats['stornirana']; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
