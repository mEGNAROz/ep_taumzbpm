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

// Obdelaj akcije
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_status') {
        // Preverimo, če so poslani potrebni podatki
        $stranka_id = isset($_POST['stranka_id']) ? (int)$_POST['stranka_id'] : 0;
        $novi_status = isset($_POST['novi_status']) ? $_POST['novi_status'] : null;

        // Preverimo, če je 'novi_status' veljaven (torej, da ni prazno in je bodisi '1' bodisi '0')
        if ($stranka_id > 0 && ($novi_status === '1' || $novi_status === '0')) {
            // Pretvorimo novi status v boolean (true/false)
            $novi_status = ($novi_status === '1') ? 1 : 0;

            try {
                $stmt = $pdo->prepare("UPDATE stranka SET aktiven = ? WHERE id = ?");
                $stmt->execute([$novi_status, $stranka_id]);
                $success = 'Status stranke je bil uspešno posodobljen.';
            } catch (PDOException $e) {
                // Če pride do napake pri SQL poizvedbi
                $error = 'Prišlo je do napake pri posodabljanju statusa: ' . $e->getMessage();
            }
        } else {
            // Če podatki niso veljavni ali so manjkajoči
            $error = 'Napačni podatki za stranko ali status.';
        }
    }
}
// Pridobi vse stranke
$stmt = $pdo->query("
    SELECT *, 
           (SELECT COUNT(*) FROM narocilo WHERE stranka_id = stranka.id) as st_narocil
    FROM stranka 
    ORDER BY priimek, ime
");
$stranke = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravljanje Strank - Prodajalec Panel</title>
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
            <h1>Upravljanje Strank</h1>
            <a href="dodaj_stranko.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Dodaj Stranko
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success mt-3"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card mt-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Ime</th>
                                <th>Priimek</th>
                                <th>Email</th>
                                <th>Naslov</th>
                                <th>Št. Naročil</th>
                                <th>Status</th>
                                <th>Registriran</th>
                                <th>Akcije</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stranke as $stranka): ?>
                                <tr>
                                    <td><?= htmlspecialchars($stranka['ime']) ?></td>
                                    <td><?= htmlspecialchars($stranka['priimek']) ?></td>
                                    <td><?= htmlspecialchars($stranka['email']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($stranka['ulica'] . ' ' . $stranka['hisna_stevilka']) ?><br>
                                        <?= htmlspecialchars($stranka['postna_stevilka'] . ' ' . $stranka['posta']) ?>
                                    </td>
                                    <td><?= $stranka['st_narocil'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $stranka['aktiven'] ? 'success' : 'danger' ?>">
                                            <?= $stranka['aktiven'] ? 'Aktiven' : 'Neaktiven' ?>
                                        </span>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($stranka['created_at'])) ?></td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="stranka_id" value="<?= $stranka['id'] ?>">
                                            <input type="hidden" name="novi_status" value="<?= $stranka['aktiven'] ? '0' : '1' ?>">
                                            <button type="submit" class="btn btn-<?= $stranka['aktiven'] ? 'danger' : 'success' ?> btn-sm">
                                                <?php if ($stranka['aktiven']): ?>
                                                    <i class="bi bi-person-x"></i> Deaktiviraj
                                                <?php else: ?>
                                                    <i class="bi bi-person-check"></i> Aktiviraj
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                        <a href="uredi_stranko.php?id=<?= $stranka['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-pencil"></i> Uredi
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
