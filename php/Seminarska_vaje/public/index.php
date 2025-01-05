<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Preveri, ali obstaja iskalna poizvedba
$iskanje = trim($_GET['iskanje'] ?? '');
$params = [];
$query = "SELECT a.id, a.naziv, a.opis, a.cena, a.glavna_slika,
                 AVG(o.ocena) AS povprecna_ocena, COUNT(o.id) AS st_ocen
          FROM artikel a
          LEFT JOIN artikel_ocene o ON a.id = o.artikel_id
          WHERE a.aktiven = TRUE";


// Če obstaja iskanje, uporabi FULLTEXT
if (!empty($iskanje)) {
    $query .= " AND MATCH(a.naziv, a.opis) AGAINST(? IN BOOLEAN MODE)";
    $params[] = $iskanje;
}

$query .= " GROUP BY a.id ORDER BY a.naziv";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$izdelki = $stmt->fetchAll();

// Pridobi sporočila
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spletna Prodajalna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Spletna Prodajalna</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Prijava</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="registracija.php">Registracija</a>
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

        <!-- Iskalnik -->
        <form method="get" action="index.php" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" name="iskanje" value="<?= htmlspecialchars($iskanje) ?>" placeholder="Išči po nazivih in opisu...">
                <button type="submit" class="btn btn-primary">Išči</button>
            </div>
        </form>

        <h1 class="mb-4">Naši izdelki</h1>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($izdelki as $izdelek): ?>
            <div class="col">
                <div class="card h-100">
                    <!-- Prikaz slike -->
                    <?php
                    $prvaSlika = !empty($izdelek['glavna_slika']) ? $izdelek['glavna_slika'] : 'no-image.png';
                    ?>
                    <img src="../uploads/<?= htmlspecialchars($prvaSlika) ?>" class="card-img-top" alt="Slika izdelka">

                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($izdelek['naziv']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($izdelek['opis']) ?></p>
                        <p class="card-text"><strong>Cena: <?= number_format($izdelek['cena'], 2) ?> €</strong></p>

                        <!-- Prikaz povprečne ocene -->
                        <?php if ($izdelek['st_ocen'] > 0): ?>
                            <p class="card-text">Ocena: <?= number_format($izdelek['povprecna_ocena'], 1) ?> (<?= $izdelek['st_ocen'] ?> ocen)</p>
                        <?php else: ?>
                            <p class="card-text">Ocena: Ni ocen</p>
                        <?php endif; ?>

                        <!-- Gumb za dodajanje v košarico -->
                        <?php if (isset($_SESSION['stranka_id'])): ?>
                            <a href="dodaj_v_kosarico.php?id=<?= $izdelek['id'] ?>" class="btn btn-primary">Dodaj v košarico</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline-primary">Prijava za nakup</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
