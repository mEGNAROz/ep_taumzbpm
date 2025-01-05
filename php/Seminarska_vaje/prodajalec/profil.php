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

// Pridobi trenutne podatke prodajalca
$stmt = $pdo->prepare("SELECT * FROM prodajalec WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$prodajalec = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ime = trim($_POST['ime'] ?? '');
    $priimek = trim($_POST['priimek'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $trenutno_geslo = $_POST['trenutno_geslo'] ?? '';
    $novo_geslo = $_POST['novo_geslo'] ?? '';
    $potrdi_geslo = $_POST['potrdi_geslo'] ?? '';

    try {
        // Preveri, če je trenutno geslo pravilno
        if (!password_verify($trenutno_geslo, $prodajalec['geslo'])) {
            throw new Exception('Trenutno geslo ni pravilno.');
        }

        // Začni transakcijo
        $pdo->beginTransaction();

        // Posodobi osnovne podatke
        $stmt = $pdo->prepare("
            UPDATE prodajalec 
            SET ime = ?, 
                priimek = ?, 
                email = ?
            WHERE id = ?
        ");
        $stmt->execute([$ime, $priimek, $email, $_SESSION['user_id']]);

        // Če je vneseno novo geslo, ga posodobi
        if (!empty($novo_geslo)) {
            if (strlen($novo_geslo) < 8) {
                throw new Exception('Novo geslo mora biti dolgo vsaj 8 znakov.');
            }
            if ($novo_geslo !== $potrdi_geslo) {
                throw new Exception('Novo geslo in potrditev se ne ujemata.');
            }

            $hash = password_hash($novo_geslo, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE prodajalec SET geslo = ? WHERE id = ?");
            $stmt->execute([$hash, $_SESSION['user_id']]);
        }

        $pdo->commit();
        $success = 'Profil je bil uspešno posodobljen.';
        
        // Posodobi session podatke
        $_SESSION['user_name'] = $ime . ' ' . $priimek;
        $_SESSION['email'] = $email;

        // Ponovno naloži podatke
        $stmt = $pdo->prepare("SELECT * FROM prodajalec WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $prodajalec = $stmt->fetch();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $staro_geslo = trim($_POST['staro_geslo'] ?? '');
            $novo_geslo = trim($_POST['novo_geslo'] ?? '');
            $potrditev_gesla = trim($_POST['potrditev_gesla'] ?? '');

            // Preverjanje dolžine novega gesla
            if (strlen($novo_geslo) < 8) {
                $error = "Novo geslo mora imeti vsaj 8 znakov.";
            } elseif ($novo_geslo !== $potrditev_gesla) {
                $error = "Novi gesli se ne ujemata.";
            } else {
                // Preveri staro geslo
                if (!password_verify($staro_geslo, $prodajalec['geslo'])) {
                    $error = "Staro geslo ni pravilno.";
                } else {
                    // Posodobi geslo
                    $novo_geslo_hash = password_hash($novo_geslo, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE prodajalec SET geslo = ? WHERE id = ?");
                    $stmt->execute([$novo_geslo_hash, $_SESSION['user_id']]);
                    $success = "Geslo uspešno posodobljeno.";
                }
            }
        }


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
    <title>Uredi Profil - Prodajalec Panel</title>
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
                        <a class="nav-link" href="stranke.php">Stranke</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profil.php">Moj Profil</a>
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
        <h1>Uredi Profil</h1>

        <?php if ($success): ?>
            <div class="alert alert-success mt-3"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Osebni Podatki</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label for="ime" class="form-label">Ime</label>
                                <input type="text" class="form-control" id="ime" name="ime" 
                                       value="<?= htmlspecialchars($prodajalec['ime']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="priimek" class="form-label">Priimek</label>
                                <input type="text" class="form-control" id="priimek" name="priimek" 
                                       value="<?= htmlspecialchars($prodajalec['priimek']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($prodajalec['email']) ?>" required>
                            </div>

                            <hr>

                            <div class="mb-3">
                                <label for="trenutno_geslo" class="form-label">Trenutno Geslo</label>
                                <input type="password" class="form-control" id="trenutno_geslo" name="trenutno_geslo" required>
                                <div class="form-text">Za potrditev sprememb vnesite trenutno geslo.</div>
                            </div>

                            <div class="mb-3">
                                <label for="novo_geslo" class="form-label">Novo Geslo</label>
                                <input type="password" class="form-control" id="novo_geslo" name="novo_geslo" required>
                                <div class="form-text">Vnesite novo geslo (vsaj 8 znakov).</div>
                            </div>

                            <div class="mb-3">
                                <label for="potrdi_geslo" class="form-label">Potrdi Novo Geslo</label>
                                <input type="password" class="form-control" id="potrdi_geslo" name="potrdi_geslo" required>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Shrani Spremembe
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
