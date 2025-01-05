<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Preveri, če je uporabnik prijavljen in je stranka
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'stranka') {
    header('Location: ../public/login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Pridobi podatke o stranki
$stmt = $pdo->prepare("SELECT * FROM stranka WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stranka = $stmt->fetch();

// Obdelaj obrazec za posodobitev profila
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $ime = trim($_POST['ime']);
        $priimek = trim($_POST['priimek']);
        $email = trim($_POST['email']);
        $ulica = trim($_POST['ulica']);
        $hisna_stevilka = trim($_POST['hisna_stevilka']);
        $postna_stevilka = trim($_POST['postna_stevilka']);
        $posta = trim($_POST['posta']);
        
        try {
            // Preveri, če je email že v uporabi
            $stmt = $pdo->prepare("SELECT id FROM stranka WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $error_message = "Email naslov je že v uporabi.";
            } else {
                // Preveri poštno številko
                if (!preg_match('/^[0-9]{4}$/', $postna_stevilka)) {
                    throw new Exception('Poštna številka mora vsebovati 4 številke.');
                }

                $stmt = $pdo->prepare("
                    UPDATE stranka 
                    SET ime = ?, 
                        priimek = ?, 
                        email = ?, 
                        ulica = ?,
                        hisna_stevilka = ?,
                        postna_stevilka = ?,
                        posta = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $ime, 
                    $priimek, 
                    $email, 
                    $ulica,
                    $hisna_stevilka,
                    $postna_stevilka,
                    $posta,
                    $_SESSION['user_id']
                ]);
                $success_message = "Profil je bil uspešno posodobljen.";
                
                // Posodobi podatke v seji
                $_SESSION['user_name'] = $ime . ' ' . $priimek;
                
                // Preusmeri na isto stran za osvežitev podatkov
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    } elseif (isset($_POST['update_password'])) {
        $staro_geslo = $_POST['staro_geslo'];
        $novo_geslo = $_POST['novo_geslo'];
        $potrdi_geslo = $_POST['potrdi_geslo'];

        if ($novo_geslo !== $potrdi_geslo) {
            $error_message = "Novi gesli se ne ujemata.";
        } elseif (strlen($novo_geslo) < 6) {
            $error_message = "Novo geslo mora biti dolgo vsaj 6 znakov.";
        } else {
            // Preveri staro geslo
            if (password_verify($staro_geslo, $stranka['geslo'])) {
                $hash = password_hash($novo_geslo, PASSWORD_DEFAULT);
                try {
                    $stmt = $pdo->prepare("UPDATE stranka SET geslo = ? WHERE id = ?");
                    $stmt->execute([$hash, $_SESSION['user_id']]);
                    $success_message = "Geslo je bilo uspešno posodobljeno.";
                    
                    // Preusmeri na isto stran za osvežitev podatkov
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                } catch (PDOException $e) {
                    $error_message = "Napaka pri posodabljanju gesla.";
                }
            } else {
                $error_message = "Napačno staro geslo.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moj Profil - Spletna Prodajalna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
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
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Osebni podatki -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Osebni podatki</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
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

                            <h6 class="mt-4 mb-3">Naslov</h6>
                            <div class="mb-3">
                                <label for="ulica" class="form-label">Ulica</label>
                                <input type="text" class="form-control" id="ulica" name="ulica" 
                                       value="<?= htmlspecialchars($stranka['ulica']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="hisna_stevilka" class="form-label">Hišna številka</label>
                                <input type="text" class="form-control" id="hisna_stevilka" name="hisna_stevilka" 
                                       value="<?= htmlspecialchars($stranka['hisna_stevilka']) ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="postna_stevilka" class="form-label">Poštna številka</label>
                                    <input type="text" class="form-control" id="postna_stevilka" name="postna_stevilka" 
                                           value="<?= htmlspecialchars($stranka['postna_stevilka']) ?>" 
                                           pattern="[0-9]{4}" maxlength="4" required>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label for="posta" class="form-label">Pošta</label>
                                    <input type="text" class="form-control" id="posta" name="posta" 
                                           value="<?= htmlspecialchars($stranka['posta']) ?>" required>
                                </div>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="bi bi-save"></i> Shrani spremembe
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Spremeni geslo -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Spremeni geslo</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="staro_geslo" class="form-label">Staro geslo</label>
                                <input type="password" class="form-control" id="staro_geslo" name="staro_geslo" required>
                            </div>
                            <div class="mb-3">
                                <label for="novo_geslo" class="form-label">Novo geslo</label>
                                <input type="password" class="form-control" id="novo_geslo" name="novo_geslo" 
                                       required minlength="6">
                                <div class="form-text">Geslo mora biti dolgo vsaj 6 znakov.</div>
                            </div>
                            <div class="mb-3">
                                <label for="potrdi_geslo" class="form-label">Potrdi novo geslo</label>
                                <input type="password" class="form-control" id="potrdi_geslo" name="potrdi_geslo" 
                                       required minlength="6">
                            </div>
                            <button type="submit" name="update_password" class="btn btn-primary">
                                <i class="bi bi-key"></i> Spremeni geslo
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Če je bil obrazec uspešno poslan, osveži stran
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                // Obrazec se bo poslal in PHP bo poskrbel za preusmeritev
                // Ni potrebno ničesar dodati tukaj, ker smo dodali header('Location') v PHP
            });
        });
        
        // Če je sporočilo o uspehu, osveži tudi navigacijo po 1 sekundi
        if (document.querySelector('.alert-success')) {
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        }
    });
    </script>
</body>
</html>
