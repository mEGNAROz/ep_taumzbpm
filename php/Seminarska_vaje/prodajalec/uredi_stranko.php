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

$success = '';
$error = '';

// Pridobi ID stranke iz URL
$stranka_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$stranka_id) {
    header('Location: stranke.php');
    exit();
}

// Pridobi podatke o stranki
try {
    $stmt = $pdo->prepare("SELECT * FROM stranka WHERE id = ?");
    $stmt->execute([$stranka_id]);
    $stranka = $stmt->fetch();

    if (!$stranka) {
        header('Location: stranke.php');
        exit();
    }
} catch (PDOException $e) {
    $error = 'Napaka pri pridobivanju podatkov o stranki.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ime = trim($_POST['ime'] ?? '');
    $priimek = trim($_POST['priimek'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $novo_geslo = trim($_POST['novo_geslo'] ?? '');
    $ulica = trim($_POST['ulica'] ?? '');
    $hisna_stevilka = trim($_POST['hisna_stevilka'] ?? '');
    $postna_stevilka = trim($_POST['postna_stevilka'] ?? '');
    $posta = trim($_POST['posta'] ?? '');

    try {
        // Preveri, če email že obstaja pri drugi stranki
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM stranka WHERE email = ? AND id != ?");
        $stmt->execute([$email, $stranka_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Email naslov je že v uporabi.');
        }

        // Preveri poštno številko
        if (!preg_match('/^[0-9]{4}$/', $postna_stevilka)) {
            throw new Exception('Poštna številka mora vsebovati 4 številke.');
        }

        // Začni transakcijo
        $pdo->beginTransaction();

        // Pripravi osnovni SQL za posodobitev
        $sql = "
            UPDATE stranka 
            SET ime = ?, 
                priimek = ?, 
                email = ?,
                ulica = ?,
                hisna_stevilka = ?,
                postna_stevilka = ?,
                posta = ?
            WHERE id = ?
        ";
        $params = [$ime, $priimek, $email, $ulica, $hisna_stevilka, $postna_stevilka, $posta, $stranka_id];

        // Če je vneseno novo geslo, ga posodobi
        if (!empty($novo_geslo)) {
            if (strlen($novo_geslo) < 8) {
                throw new Exception('Novo geslo mora biti dolgo vsaj 8 znakov.');
            }
            $sql = "
                UPDATE stranka 
                SET ime = ?, 
                    priimek = ?, 
                    email = ?,
                    geslo = ?,
                    ulica = ?,
                    hisna_stevilka = ?,
                    postna_stevilka = ?,
                    posta = ?
                WHERE id = ?
            ";
            $params = [
                $ime, 
                $priimek, 
                $email, 
                password_hash($novo_geslo, PASSWORD_DEFAULT),
                $ulica,
                $hisna_stevilka,
                $postna_stevilka,
                $posta,
                $stranka_id
            ];
        }

        // Izvedi posodobitev
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $pdo->commit();
        $success = 'Podatki stranke so bili uspešno posodobljeni.';
        
        // Ponovno naloži podatke
        $stmt = $pdo->prepare("SELECT * FROM stranka WHERE id = ?");
        $stmt->execute([$stranka_id]);
        $stranka = $stmt->fetch();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uredi Stranko - Prodajalec Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Prodajalec Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Naročila</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="artikli.php">Artikli</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="stranke.php">Stranke</a>
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
        <div class="d-flex justify-content-between align-items-center">
            <h1>Uredi Stranko</h1>
            <a href="stranke.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Nazaj na seznam
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success mt-3"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label for="ime" class="form-label">Ime</label>
                                <input type="text" class="form-control" id="ime" name="ime" 
                                       value="<?= htmlspecialchars($stranka['ime']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="priimek" class="form-label">Priimek</label>
                                <input type="text" class="form-control" id="priimek" name="priimek" 
                                       value="<?= htmlspecialchars($stranka['priimek']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($stranka['email']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="novo_geslo" class="form-label">Novo Geslo</label>
                                <input type="password" class="form-control" id="novo_geslo" name="novo_geslo">
                                <div class="form-text">Pustite prazno, če ne želite spremeniti gesla.</div>
                            </div>

                            <div class="mb-3">
                                <label for="ulica" class="form-label">Ulica</label>
                                <input type="text" class="form-control" id="ulica" name="ulica" 
                                       value="<?= htmlspecialchars($stranka['ulica']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="hisna_stevilka" class="form-label">Hišna Številka</label>
                                <input type="text" class="form-control" id="hisna_stevilka" name="hisna_stevilka" 
                                       value="<?= htmlspecialchars($stranka['hisna_stevilka']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="postna_stevilka" class="form-label">Poštna Številka</label>
                                <input type="text" class="form-control" id="postna_stevilka" name="postna_stevilka" 
                                       value="<?= htmlspecialchars($stranka['postna_stevilka']) ?>" 
                                       pattern="[0-9]{4}" required>
                                <div class="form-text">Poštna številka mora vsebovati 4 številke.</div>
                            </div>

                            <div class="mb-3">
                                <label for="posta" class="form-label">Pošta</label>
                                <input type="text" class="form-control" id="posta" name="posta" 
                                       value="<?= htmlspecialchars($stranka['posta']) ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Shrani Spremembe
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Podatki o Stranki</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?= $stranka['aktiven'] ? 'success' : 'danger' ?>">
                                <?= $stranka['aktiven'] ? 'Aktiven' : 'Neaktiven' ?>
                            </span>
                        </p>
                        <p><strong>Registriran:</strong> <?= date('d.m.Y H:i', strtotime($stranka['created_at'])) ?></p>
                        
                        <?php
                        // Pridobi število naročil
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM narocilo WHERE stranka_id = ?");
                        $stmt->execute([$stranka_id]);
                        $st_narocil = $stmt->fetchColumn();
                        ?>
                        <p><strong>Število naročil:</strong> <?= $st_narocil ?></p>
                        
                        <?php if ($st_narocil > 0): ?>
                            <a href="narocila.php?stranka_id=<?= $stranka_id ?>" class="btn btn-info btn-sm">
                                <i class="bi bi-bag"></i> Prikaži Naročila
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
