<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Preveri, če je uporabnik stranka
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'stranka') {
    header('Location: ../public/login.php');
    exit();
}

// Pridobi iskalni niz
$iskanje = trim($_GET['iskanje'] ?? '');

// Pripravi poizvedbo za artikle
if (!empty($iskanje)) {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               IFNULL(AVG(o.ocena), 0) AS povprecna_ocena, 
               COUNT(o.id) AS stevilo_ocen
        FROM artikel a
        LEFT JOIN artikel_ocene o ON a.id = o.artikel_id
        WHERE a.aktiven = TRUE
          AND MATCH(a.naziv, a.opis) AGAINST(:iskanje IN BOOLEAN MODE)
        GROUP BY a.id
        ORDER BY a.naziv
    ");
    
    $stmt->execute(['iskanje' => $iskanje]);
} else {
    $stmt = $pdo->query("
        SELECT a.*, 
               IFNULL(AVG(o.ocena), 0) AS povprecna_ocena, 
               COUNT(o.id) AS stevilo_ocen
        FROM artikel a
        LEFT JOIN artikel_ocene o ON a.id = o.artikel_id
        WHERE a.aktiven = TRUE
        GROUP BY a.id
        ORDER BY a.naziv
    ");
}

$artikli = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spletna Trgovina</title>
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
                        <a class="nav-link" href="moja_narocila.php">Moja Naročila</a>
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
        <h1>Naši Artikli</h1>

        <!-- Obrazec za iskanje -->
        <form method="get" class="mb-4">
            <div class="input-group">
                <input type="text" name="iskanje" class="form-control" placeholder="Išči po artiklih..." value="<?= htmlspecialchars($iskanje) ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Išči
                </button>
            </div>
        </form>

        <!-- Prikaz rezultatov -->
        <div class="row row-cols-1 row-cols-md-3 g-4 mt-2">
            <?php foreach ($artikli as $artikel): ?>
            <div class="col">
                <div class="card h-100">
                    <!-- Prikaz slike -->
                    <?php
                    $prvaSlika = !empty($artikel['glavna_slika']) ? $artikel['glavna_slika'] : 'no-image.png';
                    ?>
                    <img src="../uploads/<?= htmlspecialchars($prvaSlika) ?>" class="card-img-top" alt="Slika izdelka">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($artikel['naziv']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($artikel['opis']) ?></p>
                        <p class="card-text">
                            <strong>Cena: </strong>
                            <?= number_format($artikel['cena'], 2) ?> €
                        </p>
                        <p class="card-text">
                            <strong>Ocena: </strong>
                            <?= number_format($artikel['povprecna_ocena'], 1) ?> / 5
                            (<?= $artikel['stevilo_ocen'] ?> ocen)
                        </p>
                        <form method="post" action="dodaj_v_kosarico.php">
                            <input type="hidden" name="artikel_id" value="<?= $artikel['id'] ?>">
                            <div class="d-flex align-items-center">
                                <input type="number" name="kolicina" value="1" min="1" class="form-control w-25 me-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-cart-plus"></i> V košarico
                                </button>
                            </div>
                        </form>

                        <!-- Dodaj oceno -->
                        <form method="post" action="dodaj_oceno.php" class="mt-3">
                            <input type="hidden" name="artikel_id" value="<?= $artikel['id'] ?>">
                            <div class="mb-2">
                                <label for="ocena" class="form-label">Ocena:</label>
                                <input type="number" name="ocena" min="1" max="5" class="form-control w-25" value="5" required>
                            </div>
                            <div class="mb-2">
                                <label for="komentar" class="form-label">Komentar:</label>
                                <textarea name="komentar" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-star"></i> Oceni
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
