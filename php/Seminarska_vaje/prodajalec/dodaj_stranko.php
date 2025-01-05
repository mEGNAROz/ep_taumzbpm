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
// Inicializiraj spremenljivke za polja obrazca
$ime = $priimek = $email = $geslo = $potrditev_gesla = $ulica = $hisna_stevilka = $postna_stevilka = $posta = '';
$ime = htmlspecialchars($ime, ENT_QUOTES, 'UTF-8');
$priimek = htmlspecialchars($priimek, ENT_QUOTES, 'UTF-8');
$ulica = htmlspecialchars($ulica, ENT_QUOTES, 'UTF-8');
$posta = htmlspecialchars($posta, ENT_QUOTES, 'UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ime = htmlspecialchars(trim($_POST['ime'] ?? ''), ENT_QUOTES, 'UTF-8');
    $priimek = htmlspecialchars(trim($_POST['priimek'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
    $geslo = trim($_POST['geslo'] ?? '');
    $potrditev_gesla = trim($_POST['potrditev_gesla'] ?? '');
    $ulica = htmlspecialchars(trim($_POST['ulica'] ?? ''), ENT_QUOTES, 'UTF-8');
    $hisna_stevilka = htmlspecialchars(trim($_POST['hisna_stevilka'] ?? ''), ENT_QUOTES, 'UTF-8');
    $postna_stevilka = htmlspecialchars(trim($_POST['postna_stevilka'] ?? ''), ENT_QUOTES, 'UTF-8');
    $posta = htmlspecialchars(trim($_POST['posta'] ?? ''), ENT_QUOTES, 'UTF-8');


    try {
        // Preveri, če email že obstaja
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM stranka WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Email naslov je že v uporabi.');
        }
        if ($geslo !== $potrditev_gesla) {
            throw new Exception('Gesli se ne ujemata.');
        }

        // Preveri geslo
        if (strlen($geslo) < 8) {
            throw new Exception('Geslo mora biti dolgo vsaj 8 znakov.');
        }

        // Preveri poštno številko
        if (!preg_match('/^[0-9]{4}$/', $postna_stevilka)) {
            throw new Exception('Poštna številka mora vsebovati 4 številke.');
        }
        if (!ctype_digit($hisna_stevilka)) {
            throw new Exception('Hišna številka mora vsebovati samo številke.');
        }


        // Začni transakcijo
        $pdo->beginTransaction();

        // Dodaj stranko
        $stmt = $pdo->prepare("
            INSERT INTO stranka (ime, priimek, email, geslo, ulica, hisna_stevilka, postna_stevilka, posta, aktiven)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)
        ");
        $stmt->execute([
            $ime,
            $priimek,
            $email,
            password_hash($geslo, PASSWORD_DEFAULT),
            $ulica,
            $hisna_stevilka,
            $postna_stevilka,
            $posta
        ]);

        $pdo->commit();
        $success = 'Stranka je bila uspešno dodana.';
        
        // Preusmeri nazaj na seznam po kratkem zamiku
        header('refresh:2;url=stranke.php');

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();

    }
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj Stranko - Prodajalec Panel</title>
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
            <h1>Dodaj Novo Stranko</h1>
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
                                <input type="text" class="form-control" id="ime" name="ime" value="<?= htmlspecialchars($ime ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="priimek" class="form-label">Priimek</label>
                                <input type="text" class="form-control" id="priimek" name="priimek" value="<?= htmlspecialchars($priimek ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                                <div class="form-text">(npr. "janez.novak@gmail.com)</div>
                            </div>

                            <div class="mb-3">
                                <label for="geslo" class="form-label">Geslo</label>
                                <input type="password" class="form-control" id="geslo" name="geslo" required>
                                <div class="form-text">Geslo mora biti dolgo vsaj 8 znakov.</div>
                            </div>
                            <div class="mb-3">
                                <label for="potrditev_gesla" class="form-label">Potrdi Geslo</label>
                                <input type="password" class="form-control" id="potrditev_gesla" name="potrditev_gesla" required>
                                <div class="form-text">Enako kot geslo zgoraj.</div>
                            </div>

                            <div class="mb-3">
                                <label for="ulica" class="form-label">Ulica</label>
                                <input type="text" class="form-control" id="ulica" name="ulica" value="<?= htmlspecialchars($ulica ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="hisna_stevilka" class="form-label">Hišna Številka</label>
                                <input type="text" class="form-control" id="hisna_stevilka" name="hisna_stevilka" required>
                                <div class="form-text">Hišna številka je sestavljena iz numeričnih znakov</div>
                            </div>

                            <div class="mb-3">
                                <label for="postna_stevilka" class="form-label">Poštna Številka</label>
                                <input type="text" class="form-control" id="postna_stevilka" name="postna_stevilka" 
                                    value="<?= htmlspecialchars($postna_stevilka ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                    pattern="[0-9]{4}" required>
                                <div class="form-text">Poštna številka mora vsebovati 4 številke.</div>
                            </div>

                            <div class="mb-3">
                                <label for="posta" class="form-label">Pošta</label>
                                <input type="text" class="form-control" id="posta" name="posta" required>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus"></i> Dodaj Stranko
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
