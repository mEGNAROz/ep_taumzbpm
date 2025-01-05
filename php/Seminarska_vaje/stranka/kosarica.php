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

$success = '';
$error = '';

// Obdelaj akcije
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        $artikel_id = $_POST['artikel_id'] ?? 0;
        $kolicina = (int)($_POST['kolicina'] ?? 0);

        if ($kolicina > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE kosarica SET kolicina = ? WHERE stranka_id = ? AND artikel_id = ?");
                $stmt->execute([$kolicina, $_SESSION['user_id'], $artikel_id]);
                $success = 'Količina je bila posodobljena.';
            } catch (PDOException $e) {
                $error = 'Napaka pri posodabljanju količine.';
            }
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM kosarica WHERE stranka_id = ? AND artikel_id = ?");
                $stmt->execute([$_SESSION['user_id'], $artikel_id]);
                $success = 'Artikel je bil odstranjen iz košarice.';
            } catch (PDOException $e) {
                $error = 'Napaka pri odstranjevanju artikla.';
            }
        }
    } elseif ($action === 'checkout') {
        try {
            // Začni transakcijo
            $pdo->beginTransaction();

            // Pridobi artikle v košarici
            $stmt = $pdo->prepare("
                SELECT k.artikel_id, k.kolicina, a.cena, a.naziv
                FROM kosarica k
                JOIN artikel a ON k.artikel_id = a.id
                WHERE k.stranka_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $artikli = $stmt->fetchAll();

            if (empty($artikli)) {
                throw new Exception('Košarica je prazna.');
            }

            // Izračunaj skupno ceno
            $skupna_cena = 0;
            foreach ($artikli as $artikel) {
                $skupna_cena += $artikel['cena'] * $artikel['kolicina'];
            }

            // Ustvari novo naročilo
            $stmt = $pdo->prepare("
                INSERT INTO narocilo (stranka_id, status, skupna_cena, datum_oddaje)
                VALUES (?, 'oddano', ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $skupna_cena]);
            $narocilo_id = $pdo->lastInsertId();

            // Dodaj artikle v naročilo
            $stmt = $pdo->prepare("
                INSERT INTO narocilo_artikel (narocilo_id, artikel_id, kolicina, cena_na_kos)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($artikli as $artikel) {
                $stmt->execute([
                    $narocilo_id,
                    $artikel['artikel_id'],
                    $artikel['kolicina'],
                    $artikel['cena']
                ]);
            }

            // Izprazni košarico
            $stmt = $pdo->prepare("DELETE FROM kosarica WHERE stranka_id = ?");
            $stmt->execute([$_SESSION['user_id']]);

            // Potrdi transakcijo
            $pdo->commit();
            
            header('Location: moja_narocila.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

// Pridobi vsebino košarice
$stmt = $pdo->prepare("
    SELECT k.artikel_id, k.kolicina, a.naziv, a.cena
    FROM kosarica k
    JOIN artikel a ON k.artikel_id = a.id
    WHERE k.stranka_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$artikli = $stmt->fetchAll();

// Izračunaj skupno ceno
$skupna_cena = 0;
foreach ($artikli as $artikel) {
    $skupna_cena += $artikel['cena'] * $artikel['kolicina'];
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Košarica - Spletna Trgovina</title>
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
                        <a class="nav-link active" href="kosarica.php">
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
        <h1>Košarica</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($artikli)): ?>
            <div class="alert alert-info">Vaša košarica je prazna.</div>
            <a href="index.php" class="btn btn-primary">Nadaljuj z nakupovanjem</a>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Artikel</th>
                            <th>Cena</th>
                            <th>Količina</th>
                            <th>Skupaj</th>
                            <th>Akcije</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($artikli as $artikel): ?>
                        <tr>
                            <td><?= htmlspecialchars($artikel['naziv']) ?></td>
                            <td><?= number_format($artikel['cena'], 2) ?> €</td>
                            <td>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="artikel_id" value="<?= $artikel['artikel_id'] ?>">
                                    <input type="number" name="kolicina" value="<?= $artikel['kolicina'] ?>" 
                                           min="0" class="form-control d-inline-block" style="width: 80px;"
                                           onchange="this.form.submit()">
                                </form>
                            </td>
                            <td><?= number_format($artikel['cena'] * $artikel['kolicina'], 2) ?> €</td>
                            <td>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="artikel_id" value="<?= $artikel['artikel_id'] ?>">
                                    <input type="hidden" name="kolicina" value="0">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Skupaj:</strong></td>
                            <td><strong><?= number_format($skupna_cena, 2) ?> €</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Nadaljuj z nakupovanjem
                </a>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                <i class="bi bi-check-circle"></i> Zaključi nakup
                </button>
            </div>
        <?php endif; ?>
    </div>
    <div id="checkoutModal" class="modal fade" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
     <div class="modal-dialog">
         <div class="modal-content">
             <div class="modal-header">
                 <h5 class="modal-title" id="checkoutModalLabel">Zaključi nakup</h5>
                 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
             </div>
             <div class="modal-body">
                 Ali ste prepričani, da želite zaključiti nakup?
             </div>
             <div class="modal-footer">
                 <form method="post">
                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Prekliči</button>
                     <input type="hidden" name="action" value="checkout">
                     <button type="submit" class="btn btn-success">
                         <i class="bi bi-check-circle"></i> Potrdi nakup
                     </button>
                 </form>
             </div>
         </div>
     </div>
 </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Preveri, če je gumb najden
            const checkoutBtn = document.querySelector('[data-bs-target="#checkoutModal"]');
            console.log('Checkout button:', checkoutBtn);

            // Preveri, če je modal najden
            const modal = document.getElementById('checkoutModal');
            console.log('Modal element:', modal);

            if (checkoutBtn && modal) {
                // Dodaj event listener za klik
                checkoutBtn.addEventListener('click', function() {
                    console.log('Checkout button clicked');
                    const bootstrapModal = new bootstrap.Modal(modal);
                    bootstrapModal.show();
                });

                // Dodaj event listener za modal events
                modal.addEventListener('show.bs.modal', function() {
                    console.log('Modal is about to show');
                });
                modal.addEventListener('shown.bs.modal', function() {
                    console.log('Modal is shown');
                });
            }
             const modalElement = document.getElementById('checkoutModal');
    const cancelButton = document.querySelector('[data-bs-dismiss="modal"]');

    if (modalElement && cancelButton) {
        cancelButton.addEventListener('click', function() {
            console.log('Prekliči gumb je bil kliknjen.');
            const bootstrapModal = bootstrap.Modal.getInstance(modalElement);
            bootstrapModal.hide();
        });
    }
});
        });
    </script>
</body>
</html>