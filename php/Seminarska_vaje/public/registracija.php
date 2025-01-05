<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ime = trim(htmlspecialchars($_POST['ime'] ?? ''));
    $priimek = trim(htmlspecialchars($_POST['priimek'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $geslo = $_POST['geslo'] ?? '';
    $potrdi_geslo = $_POST['potrdi_geslo'] ?? '';
    $tip_uporabnika = $_POST['tip_uporabnika'] ?? 'stranka';

    // Dodatna polja za stranko
    $ulica = trim(htmlspecialchars($_POST['ulica'] ?? ''));
    $hisna_stevilka = trim(htmlspecialchars($_POST['hisna_stevilka'] ?? ''));
    $posta = trim(htmlspecialchars($_POST['posta'] ?? ''));
    $postna_stevilka = trim($_POST['postna_stevilka'] ?? '');

    // Preveri, če so vsa polja izpolnjena
    if (empty($ime) || empty($priimek) || empty($email) || empty($geslo) || empty($potrdi_geslo)) {
        $error = 'Prosimo, izpolnite vsa obvezna polja.';
    }
    // Dodatna validacija za stranko
    elseif ($tip_uporabnika === 'stranka' && (empty($ulica) || empty($hisna_stevilka) || empty($posta) || empty($postna_stevilka))) {
        $error = 'Prosimo, izpolnite vse podatke o naslovu.';
    }
    // Preveri, če se gesli ujemata
    elseif ($geslo !== $potrdi_geslo) {
        $error = 'Gesli se ne ujemata.';
    }
    // Preveri dolžino gesla
    elseif (strlen($geslo) < 6) {
        $error = 'Geslo mora biti dolgo vsaj 6 znakov.';
    }
    // Preveri veljavnost e-pošte
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Prosimo, vnesite veljaven e-poštni naslov.';
    }
    // Preveri format poštne številke za stranko
    elseif ($tip_uporabnika === 'stranka' && !preg_match('/^[0-9]{4}$/', $postna_stevilka)) {
        $error = 'Poštna številka mora vsebovati 4 številke.';
    }
    else {
        try {
            // Preveri, če e-pošta že obstaja v izbrani tabeli
            $table = $tip_uporabnika === 'stranka' ? 'stranka' : 'prodajalec';
            $stmt = $pdo->prepare("SELECT id FROM $table WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Ta e-poštni naslov je že registriran.';
            } else {
                // Zakodiraj geslo
                $hash = password_hash($geslo, PASSWORD_DEFAULT);
                
                if ($tip_uporabnika === 'stranka') {
                    $stmt = $pdo->prepare('INSERT INTO stranka (ime, priimek, email, geslo, ulica, hisna_stevilka, posta, postna_stevilka) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$ime, $priimek, $email, $hash, $ulica, $hisna_stevilka, $posta, $postna_stevilka]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO prodajalec (ime, priimek, email, geslo) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$ime, $priimek, $email, $hash]);
                }
                
                $success = 'Registracija uspešna! Zdaj se lahko prijavite.';
                header('refresh:2;url=login.php');
            }
        } catch (PDOException $e) {
            error_log("Napaka pri registraciji: " . $e->getMessage());
            $error = 'Prišlo je do napake pri registraciji. Prosimo, poskusite ponovno.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registracija - Spletna Prodajalna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Spletna Prodajalna</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="login.php">Prijava</a>
                <a class="nav-link active" href="registracija.php">Registracija</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Registracija</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Tip uporabnika *</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tip_uporabnika" id="stranka" value="stranka" checked>
                                    <label class="form-check-label" for="stranka">Stranka</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tip_uporabnika" id="prodajalec" value="prodajalec">
                                    <label class="form-check-label" for="prodajalec">Prodajalec</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="ime" class="form-label">Ime *</label>
                                <input type="text" class="form-control" id="ime" name="ime" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="priimek" class="form-label">Priimek *</label>
                                <input type="text" class="form-control" id="priimek" name="priimek" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">E-poštni naslov *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="geslo" class="form-label">Geslo *</label>
                                <input type="password" class="form-control" id="geslo" name="geslo" required>
                                <small class="text-muted">Geslo mora biti dolgo vsaj 6 znakov.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="potrdi_geslo" class="form-label">Potrdi geslo *</label>
                                <input type="password" class="form-control" id="potrdi_geslo" name="potrdi_geslo" required>
                            </div>

                            <div id="stranka-fields">
                                <div class="mb-3">
                                    <label for="ulica" class="form-label">Ulica *</label>
                                    <input type="text" class="form-control" id="ulica" name="ulica">
                                </div>

                                <div class="mb-3">
                                    <label for="hisna_stevilka" class="form-label">Hišna številka *</label>
                                    <input type="text" class="form-control" id="hisna_stevilka" name="hisna_stevilka">
                                </div>

                                <div class="mb-3">
                                    <label for="posta" class="form-label">Pošta *</label>
                                    <input type="text" class="form-control" id="posta" name="posta">
                                </div>

                                <div class="mb-3">
                                    <label for="postna_stevilka" class="form-label">Poštna številka *</label>
                                    <input type="text" class="form-control" id="postna_stevilka" name="postna_stevilka">
                                    <small class="text-muted">Poštna številka mora vsebovati 4 številke.</small>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Registracija</button>
                        </form>

                        <div class="mt-3">
                            <p>Že imate račun? <a href="login.php">Prijavite se</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const strankaFields = document.getElementById('stranka-fields');
        const tipUporabnika = document.getElementsByName('tip_uporabnika');
        
        function toggleStrankaFields() {
            const isStranka = document.getElementById('stranka').checked;
            strankaFields.style.display = isStranka ? 'block' : 'none';
            
            // Toggle required attribute
            const fields = strankaFields.querySelectorAll('input');
            fields.forEach(field => {
                field.required = isStranka;
            });
        }

        // Add event listeners for radio buttons
        tipUporabnika.forEach(radio => {
            radio.addEventListener('change', toggleStrankaFields);
        });

        // Initial toggle
        toggleStrankaFields();
    });
    </script>
</body>
</html>
