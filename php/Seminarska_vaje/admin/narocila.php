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
    $narocilo_id = $_POST['narocilo_id'] ?? 0;

    switch ($action) {
        case 'update_status':
            $nov_status = $_POST['status'] ?? '';
            try {
                $stmt = $pdo->prepare("UPDATE narocilo SET status = ? WHERE id = ?");
                $stmt->execute([$nov_status, $narocilo_id]);
                $success = 'Status naročila je bil uspešno posodobljen.';
            } catch (PDOException $e) {
                $error = 'Napaka pri posodabljanju statusa naročila.';
            }
            break;
    }
}

// Pridobi vsa naročila z dodatnimi podatki
$stmt = $pdo->query("
    SELECT 
        n.id,
        n.status,
        n.datum_oddaje,
        n.skupna_cena,
        s.ime AS stranka_ime,
        s.priimek AS stranka_priimek,
        s.email AS stranka_email,
        p.ime AS prodajalec_ime,
        p.priimek AS prodajalec_priimek
    FROM narocilo n
    LEFT JOIN stranka s ON n.stranka_id = s.id
    LEFT JOIN prodajalec p ON n.prodajalec_id = p.id
    ORDER BY n.datum_oddaje DESC
");
$narocila = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pregled Naročil - Admin</title>
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
        <h1>Pregled Naročil</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Datum</th>
                        <th>Stranka</th>
                        <th>Prodajalec</th>
                        <th>Znesek</th>
                        <th>Status</th>
                        <th>Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($narocila as $narocilo): ?>
                    <tr>
                        <td><?= htmlspecialchars($narocilo['id']) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($narocilo['datum_oddaje'])) ?></td>
                        <td>
                            <?= htmlspecialchars($narocilo['stranka_ime'] . ' ' . $narocilo['stranka_priimek']) ?><br>
                            <small class="text-muted"><?= htmlspecialchars($narocilo['stranka_email']) ?></small>
                        </td>
                        <td>
                            <?php if ($narocilo['prodajalec_ime']): ?>
                                <?= htmlspecialchars($narocilo['prodajalec_ime'] . ' ' . $narocilo['prodajalec_priimek']) ?>
                            <?php else: ?>
                                <span class="text-muted">Ni dodeljen</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($narocilo['skupna_cena'], 2) ?> €</td>
                        <td>
                            <?php
                            $status_class = match($narocilo['status']) {
                                'oddano' => 'bg-warning',
                                'potrjeno' => 'bg-success',
                                'preklicano' => 'bg-danger',
                                'stornirano' => 'bg-secondary',
                                default => 'bg-primary'
                            };
                            ?>
                            <span class="badge <?= $status_class ?>">
                                <?= ucfirst($narocilo['status']) ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#urediStatusModal"
                                    data-id="<?= $narocilo['id'] ?>"
                                    data-status="<?= htmlspecialchars($narocilo['status']) ?>">
                                <i class="bi bi-pencil"></i> Spremeni Status
                            </button>
                            <a href="narocilo_podrobnosti.php?id=<?= $narocilo['id'] ?>" class="btn btn-info btn-sm">
                                <i class="bi bi-eye"></i> Podrobnosti
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal za urejanje statusa -->
    <div class="modal fade" id="urediStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Spremeni status naročila</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="narocilo_id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="oddano">Oddano</option>
                                <option value="potrjeno">Potrjeno</option>
                                <option value="preklicano">Preklicano</option>
                                <option value="stornirano">Stornirano</option>
                            </select>
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
        // Napolni modal za urejanje statusa
        document.addEventListener('DOMContentLoaded', function() {
            const urediStatusModal = document.getElementById('urediStatusModal');
            urediStatusModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const status = button.getAttribute('data-status');

                urediStatusModal.querySelector('#edit_id').value = id;
                urediStatusModal.querySelector('#edit_status').value = status;
            });
        });
    </script>
</body>
</html>
