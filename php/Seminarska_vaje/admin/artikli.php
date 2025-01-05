<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Preveri, če je uporabnik administrator
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Obdelaj akcije
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $artikel_id = $_POST['artikel_id'] ?? 0;

    switch ($action) {
        case 'toggle_status':
            try {
                $stmt = $pdo->prepare("UPDATE artikel SET aktiven = NOT aktiven WHERE id = ?");
                $stmt->execute([$artikel_id]);
                $success = 'Status artikla je bil uspešno posodobljen.';
            } catch (PDOException $e) {
                $error = 'Napaka pri posodabljanju statusa artikla.';
            }
            break;

        case 'update':
            $naziv = $_POST['naziv'] ?? '';
            $opis = $_POST['opis'] ?? '';
            $cena = $_POST['cena'] ?? 0;
            
            try {
                $stmt = $pdo->prepare("UPDATE artikel SET naziv = ?, opis = ?, cena = ? WHERE id = ?");
                $stmt->execute([$naziv, $opis, $cena, $artikel_id]);
                $success = 'Podatki artikla so bili uspešno posodobljeni.';
            } catch (PDOException $e) {
                $error = 'Napaka pri posodabljanju podatkov artikla.';
            }
            break;

        case 'add':
            $naziv = $_POST['naziv'] ?? '';
            $opis = $_POST['opis'] ?? '';
            $cena = $_POST['cena'] ?? 0;

            if (empty($naziv) || empty($cena)) {
                $error = 'Naziv in cena sta obvezna.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO artikel (naziv, opis, cena, aktiven) VALUES (?, ?, ?, TRUE)");
                    $stmt->execute([$naziv, $opis, $cena]);
                    $success = 'Nov artikel je bil uspešno dodan.';
                } catch (PDOException $e) {
                    $error = 'Napaka pri dodajanju novega artikla.';
                }
            }
            break;
    }
}

// Pridobi vse artikle
$stmt = $pdo->query("SELECT id, naziv, opis, cena, aktiven FROM artikel ORDER BY naziv");
$artikli = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravljanje Artiklov - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Admin Panel</a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link">Dobrodošli, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
                <a class="nav-link" href="../public/logout.php">Odjava</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Upravljanje Artiklov</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="mb-4">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#dodajModal">
                <i class="bi bi-plus-lg"></i> Dodaj Artikel
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Naziv</th>
                        <th>Opis</th>
                        <th>Cena (€)</th>
                        <th>Status</th>
                        <th>Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($artikli as $artikel): ?>
                    <tr>
                        <td><?= htmlspecialchars($artikel['id']) ?></td>
                        <td><?= htmlspecialchars($artikel['naziv']) ?></td>
                        <td><?= htmlspecialchars($artikel['opis']) ?></td>
                        <td><?= number_format($artikel['cena'], 2) ?></td>
                        <td>
                            <?php if ($artikel['aktiven']): ?>
                                <span class="badge bg-success">Aktiven</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Neaktiven</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#urediModal"
                                    data-id="<?= $artikel['id'] ?>"
                                    data-naziv="<?= htmlspecialchars($artikel['naziv']) ?>"
                                    data-opis="<?= htmlspecialchars($artikel['opis']) ?>"
                                    data-cena="<?= htmlspecialchars($artikel['cena']) ?>">
                                <i class="bi bi-pencil"></i> Uredi
                            </button>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="artikel_id" value="<?= $artikel['id'] ?>">
                                <button type="submit" class="btn btn-<?= $artikel['aktiven'] ? 'warning' : 'success' ?> btn-sm">
                                    <?php if ($artikel['aktiven']): ?>
                                        <i class="bi bi-eye-slash"></i> Deaktiviraj
                                    <?php else: ?>
                                        <i class="bi bi-eye"></i> Aktiviraj
                                    <?php endif; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal za dodajanje -->
    <div class="modal fade" id="dodajModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dodaj artikel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Naziv</label>
                            <input type="text" class="form-control" name="naziv" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Opis</label>
                            <textarea class="form-control" name="opis" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cena (€)</label>
                            <input type="number" step="0.01" class="form-control" name="cena" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Prekliči</button>
                        <button type="submit" class="btn btn-primary">Dodaj</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal za urejanje -->
    <div class="modal fade" id="urediModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Uredi artikel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="artikel_id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Naziv</label>
                            <input type="text" class="form-control" name="naziv" id="edit_naziv" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Opis</label>
                            <textarea class="form-control" name="opis" id="edit_opis" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cena (€)</label>
                            <input type="number" step="0.01" class="form-control" name="cena" id="edit_cena" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Prekliči</button>
                        <button type="submit" class="btn btn-primary">Shrani</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Napolni modal za urejanje
        document.addEventListener('DOMContentLoaded', function() {
            const urediModal = document.getElementById('urediModal');
            urediModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const naziv = button.getAttribute('data-naziv');
                const opis = button.getAttribute('data-opis');
                const cena = button.getAttribute('data-cena');

                urediModal.querySelector('#edit_id').value = id;
                urediModal.querySelector('#edit_naziv').value = naziv;
                urediModal.querySelector('#edit_opis').value = opis;
                urediModal.querySelector('#edit_cena').value = cena;
            });
        });
    </script>
</body>
</html>
