<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//# Avtorizirani uporabniki (to navadno pride iz podatkovne baze)
//$authorized_users = ["Admin"];
//
//# preberemo odjemačev certifikat
//$client_cert = filter_input(INPUT_SERVER, "SSL_CLIENT_CERT");
//
//# in ga razčlenemo
//$cert_data = openssl_x509_parse($client_cert);
//
//# preberemo ime uporabnika (polje "common name")
//$commonname = $cert_data['subject']['CN'];

// Preveri, če je uporabnik prijavljen kot admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Preusmeri v seller mapo, če je uporabnik prodajalec
if (isset($_SESSION['prodajalec_id'])) {
    header('Location: ../seller/index.php');
    exit();
}


// Pridobi serijsko številko certifikata uporabnika
$client_cert = filter_input(INPUT_SERVER, "SSL_CLIENT_CERT");
if (!$client_cert) {
    // Če certifikata ni, preusmeritev na prijavno stran
    header('Location: ../public/login.php');
    exit();
}

$cert_data = openssl_x509_parse($client_cert);
// var_dump($cert_data);
if (!$cert_data || !isset($cert_data['serialNumberHex'])) {
    // Če certifikat ni veljaven ali ni mogoče pridobiti serijske številke
    header('Location: ../public/login.php');
    exit();
}

$serijska_certifikat = $cert_data['serialNumberHex'];

// Pridobi serijsko številko iz podatkovne baze za prijavljenega prodajalca
$stmt = $pdo->prepare("SELECT cert_serial FROM administrator WHERE id = :id");
$stmt->execute(['id' => 1]);
$admin = $stmt->fetch();

if (!$admin || $admin['cert_serial'] !== $serijska_certifikat) {
    // Če serijski številki ne ustrezata, preusmeritev z obvestilom
    $_SESSION['error_message'] = "Napačen certifikat. Prosimo, uporabite ustrezen certifikat.";
    header('Location: ../public/login.php');
    exit();
}
// Pridobi sporočila
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

// Počisti sporočila
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Spletna Prodajalna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Dobrodošli, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="prodajalci.php">Prodajalci</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profil.php">Moj Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../public/logout.php">Odjava</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h1>Admin Dashboard</h1>
        
        <div class="row mt-4">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Upravljanje Prodajalcev</h5>
                        <p class="card-text">Dodajanje, urejanje in upravljanje prodajalcev.</p>
                        <a href="prodajalci.php" class="btn btn-primary">
                            <i class="bi bi-people"></i> Upravljaj Prodajalce
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Upravljanje Profila</h5>
                        <p class="card-text">Urejanje in upravljanje svojega profila.</p>
                        <a href="profil.php" class="btn btn-primary">
                            <i class="bi bi-person"></i> Upravljaj Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
