<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Preveri, če je uporabnik prijavljen
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'stranka') {
    header('Location: ../public/login.php');
    exit;
}

// Pridobi vsa naročila stranke
$stmt = $pdo->prepare("
    SELECT n.*, 
           p.ime AS prodajalec_ime, 
           p.priimek AS prodajalec_priimek
    FROM narocilo n
    LEFT JOIN prodajalec p ON n.prodajalec_id = p.id
    WHERE n.stranka_id = ?
    ORDER BY n.datum_oddaje DESC
");
$stmt->execute([$_SESSION['user_id']]);
$narocila = $stmt->fetchAll();

// Za vsako naročilo pridobi še njegove artikle
$narocila_artikli = [];
if (!empty($narocila)) {
    $stmt = $pdo->prepare("
        SELECT na.*, a.naziv
        FROM narocilo_artikel na
        JOIN artikel a ON na.artikel_id = a.id
        WHERE na.narocilo_id = ?
    ");
    
    foreach ($narocila as $narocilo) {
        $stmt->execute([$narocilo['id']]);
        $narocila_artikli[$narocilo['id']] = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moja Naročila - Spletna Trgovina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Spletna Trgovina</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Artikli</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kosarica.php">
                            <i class="bi bi-cart"></i> Košarica
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="moja_narocila.php">Moja Naročila</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profil.php">Moj Profil</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <span class="nav-link">Dobrodošli, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <a class="nav-link" href="../public/logout.php">Odjava</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Moja Naročila</h1>

        <?php if (empty($narocila)): ?>
            <div class="alert alert-info">Nimate še nobenega naročila.</div>
            <a href="index.php" class="btn btn-primary">Začni z nakupovanjem</a>
        <?php else: ?>
            <div class="accordion" id="accordionNarocila">
                <?php foreach ($narocila as $narocilo): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?= $narocilo['id'] ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapse<?= $narocilo['id'] ?>">
                                Naročilo #<?= $narocilo['id'] ?> - 
                                <?= date('d.m.Y H:i', strtotime($narocilo['datum_oddaje'])) ?> - 
                                Status: <span class="badge bg-<?= match($narocilo['status']) {
                                    'oddano' => 'warning',
                                    'potrjeno' => 'success',
                                    'preklicano' => 'danger',
                                    'stornirano' => 'secondary',
                                    default => 'primary'
                                } ?> ms-2"><?= ucfirst($narocilo['status']) ?></span>
                            </button>
                        </h2>
                        <div id="collapse<?= $narocilo['id'] ?>" class="accordion-collapse collapse" 
                             data-bs-parent="#accordionNarocila">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>Podatki o naročilu</h5>
                                        <p>
                                            <strong>Datum oddaje:</strong> 
                                            <?= date('d.m.Y H:i', strtotime($narocilo['datum_oddaje'])) ?>
                                        </p>
                                        <p>
                                            <strong>Status:</strong> 
                                            <?= ucfirst($narocilo['status']) ?>
                                        </p>
                                        <p>
                                            <strong>Skupna cena:</strong> 
                                            <?= number_format($narocilo['skupna_cena'], 2) ?> €
                                        </p>
                                        <?php if ($narocilo['prodajalec_id']): ?>
                                            <p>
                                                <strong>Prodajalec:</strong>
                                                <?= htmlspecialchars($narocilo['prodajalec_ime'] . ' ' . $narocilo['prodajalec_priimek']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>Naročeni artikli</h5>
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Artikel</th>
                                                    <th>Količina</th>
                                                    <th>Cena/kos</th>
                                                    <th>Skupaj</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($narocila_artikli[$narocilo['id']] as $artikel): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($artikel['naziv']) ?></td>
                                                        <td><?= $artikel['kolicina'] ?></td>
                                                        <td><?= number_format($artikel['cena_na_kos'], 2) ?> €</td>
                                                        <td><?= number_format($artikel['cena_na_kos'] * $artikel['kolicina'], 2) ?> €</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
