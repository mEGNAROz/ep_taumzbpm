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
    $prodajalec_id = $_POST['prodajalec_id'] ?? 0;

    switch ($action) {
        case 'toggle_status':
            try {
                $stmt = $pdo->prepare("UPDATE prodajalec SET aktiven = NOT aktiven WHERE id = ?");
                $stmt->execute([$prodajalec_id]);
                $success = 'Status prodajalca je bil uspešno posodobljen.';
            } catch (PDOException $e) {
                $error = 'Napaka pri posodabljanju statusa prodajalca.';
            }
            break;

        case 'update':
            $ime = $_POST['ime'] ?? '';
            $priimek = $_POST['priimek'] ?? '';
            $email = $_POST['email'] ?? '';
            
            $cert_serial = $_POST['cert_serial'] ?? '';
            try {
                $stmt = $pdo->prepare(
                    "UPDATE prodajalec SET ime = ?, priimek = ?, email = ?, cert_serial = ? WHERE id = ?"
                );
                $stmt->execute([$ime, $priimek, $email, $cert_serial, $prodajalec_id]);
                $success = 'Podatki prodajalca so bili uspešno posodobljeni.';
            } catch (PDOException $e) {
                $error = 'Napaka pri posodabljanju podatkov prodajalca.';
            }

            break;

        case 'add':
            $ime = $_POST['ime'] ?? '';
            $priimek = $_POST['priimek'] ?? '';
            $email = $_POST['email'] ?? '';
            $geslo = $_POST['geslo'] ?? '';

            $cert_serial = $_POST['cert_serial'] ?? '';
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO prodajalec (ime, priimek, email, geslo, cert_serial, aktiven) VALUES (?, ?, ?, ?, ?, TRUE)"
                );
                $stmt->execute([$ime, $priimek, $email, password_hash($geslo, PASSWORD_DEFAULT), $cert_serial]);
                $success = 'Nov prodajalec je bil uspešno dodan.';
            } catch (PDOException $e) {
                $error = 'Napaka pri dodajanju novega prodajalca.';
            }
            break;

}}

// Pridobi vse prodajalce
$stmt = $pdo->query("SELECT id, ime, priimek, email, aktiven, cert_serial FROM prodajalec ORDER BY priimek, ime");
$prodajalci = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravljanje Prodajalcev - Admin</title>
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
        <h1>Upravljanje Prodajalcev</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="mb-4">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#dodajModal">
                <i class="bi bi-plus-lg"></i> Dodaj Prodajalca
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                

<thead>
    <tr>
        <th>ID</th>
        <th>Ime</th>
        <th>Priimek</th>
        <th>Email</th>
        <th>Status</th>
        <th>Akcije</th>
    </tr>
</thead>
<tbody>
    <?php foreach ($prodajalci as $prodajalec): ?>
    <tr>
        <td><?= htmlspecialchars($prodajalec['id']) ?></td>
        <td><?= htmlspecialchars($prodajalec['ime']) ?></td>
        <td><?= htmlspecialchars($prodajalec['priimek']) ?></td>
        <td><?= htmlspecialchars($prodajalec['email']) ?></td>
        <td>
            <?php if ($prodajalec['aktiven']): ?>
                <span class="badge bg-success">Aktiven</span>
            <?php else: ?>
                <span class="badge bg-danger">Neaktiven</span>
            <?php endif; ?>
        </td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#urediModal"
                                    data-id="<?= $prodajalec['id'] ?>"
                                    data-ime="<?= htmlspecialchars($prodajalec['ime']) ?>"
                                    data-priimek="<?= htmlspecialchars($prodajalec['priimek']) ?>"
                                    data-email="<?= htmlspecialchars($prodajalec['email']) ?>"
                                    data-cert-serial="<?= htmlspecialchars($prodajalec['cert_serial']) ?>">
                                <i class="bi bi-pencil"></i> Uredi
                            </button>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="prodajalec_id" value="<?= $prodajalec['id'] ?>">
                                <button type="submit" class="btn btn-<?= $prodajalec['aktiven'] ? 'warning' : 'success' ?> btn-sm">
                                    <?php if ($prodajalec['aktiven']): ?>
                                        <i class="bi bi-person-x"></i> Deaktiviraj
                                    <?php else: ?>
                                        <i class="bi bi-person-check"></i> Aktiviraj
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
                    <h5 class="modal-title">Dodaj prodajalca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Ime</label>
                            <input type="text" class="form-control" name="ime" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priimek</label>
                            <input type="text" class="form-control" name="priimek" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Geslo</label>
                            <input type="password" class="form-control" name="geslo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Serijska številka</label>
                            <input type="text" class="form-control" name="cert_serial" required>
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
                    <h5 class="modal-title">Uredi prodajalca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="prodajalec_id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Ime</label>
                            <input type="text" class="form-control" name="ime" id="edit_ime" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priimek</label>
                            <input type="text" class="form-control" name="priimek" id="edit_priimek" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Serijska številka</label>
                            <input type="text" class="form-control" name="cert_serial" id="edit_cert_serial">
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
                const ime = button.getAttribute('data-ime');
                const priimek = button.getAttribute('data-priimek');
                const email = button.getAttribute('data-email');
                const certSerial = button.getAttribute('data-cert-serial');
                urediModal.querySelector('#edit_cert_serial').value = certSerial;
                urediModal.querySelector('#edit_id').value = id;
                urediModal.querySelector('#edit_ime').value = ime;
                urediModal.querySelector('#edit_priimek').value = priimek;
                urediModal.querySelector('#edit_email').value = email;
            });
        });
    </script>
</body>
</html>