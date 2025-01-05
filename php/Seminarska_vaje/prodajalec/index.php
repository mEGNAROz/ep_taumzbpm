<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Preveri, če je uporabnik prodajalec
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'prodajalec') {
    header('Location: ../public/login.php');
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
$stmt = $pdo->prepare("SELECT cert_serial FROM prodajalec WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$prodajalec = $stmt->fetch();

if (!$prodajalec || $prodajalec['cert_serial'] !== $serijska_certifikat) {
    // Če serijski številki ne ustrezata, preusmeritev z obvestilom
    $_SESSION['error_message'] = "Napačen certifikat. Prosimo, uporabite ustrezen certifikat.";
    header('Location: ../public/login.php');
    exit();
}
// Pridobi vsa naročila
$stmt = $pdo->query("
    SELECT n.*, 
           s.ime AS stranka_ime, 
           s.priimek AS stranka_priimek,
           s.email AS stranka_email
    FROM narocilo n
    JOIN stranka s ON n.stranka_id = s.id
    ORDER BY n.datum_oddaje DESC
");
$narocila = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prodajalec Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Prodajalec Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Naročila</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="artikli.php">Artikli</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="stranke.php">Stranke</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profil.php">Moj Profil</a>
                    </li>
                </ul>
                <div class="navbar-nav ms-auto">
                    <span class="nav-link">Dobrodošli, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <a class="nav-link" href="../public/logout.php">Odjava</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Pregled Naročil</h1>

        <?php if (empty($narocila)): ?>
            <div class="alert alert-info">Trenutno ni nobenih naročil.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Datum</th>
                            <th>Stranka</th>
                            <th>Status</th>
                            <th>Skupna cena</th>
                            <th>Akcije</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($narocila as $narocilo): ?>
                        <tr>
                            <td><?= htmlspecialchars($narocilo['id']) ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($narocilo['datum_oddaje'])) ?></td>
                            <td>
                                <?= htmlspecialchars($narocilo['stranka_ime'] . ' ' . $narocilo['stranka_priimek']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($narocilo['stranka_email']) ?></small>
                            </td>
                            <td>
                                <?php
                                $status_class = match($narocilo['status']) {
                                    'oddano' => 'bg-warning',
                                    'potrjeno' => 'bg-success',
                                    'preklicano' => 'bg-danger',
                                    'stornirano' => 'bg-secondary',
                                    default => 'bg-primary'
                                };
                                ?>
                                <span class="badge <?= $status_class ?>">
                                    <?= ucfirst($narocilo['status']) ?>
                                </span>
                            </td>
                            <td><?= number_format($narocilo['skupna_cena'], 2) ?> €</td>
                            <td>
                                <a href="narocilo_podrobnosti.php?id=<?= $narocilo['id'] ?>" class="btn btn-info btn-sm">
                                    <i class="bi bi-eye"></i> Podrobnosti
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
