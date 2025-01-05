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

// Preveri, če je podan ID naročila
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$narocilo_id = (int)$_GET['id'];
$success = '';
$error = '';

// Obdelaj akcije
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowed_actions = ['potrdi', 'preklici', 'storniraj'];
   /*  if (!in_array($action, $allowed_actions)) {
        $error = 'Neveljavna akcija.';
    } */
    
    $action = $_POST['action'] ?? '';
    
    try {
        $pdo->beginTransaction();
        
        if ($action === 'potrdi') {
            // Preveri trenutni status
            $stmt = $pdo->prepare("SELECT status FROM narocilo WHERE id = ?");
            $stmt->execute([$narocilo_id]);
            $trenutni_status = $stmt->fetchColumn();
            
            if ($trenutni_status === 'oddano') {
                $stmt = $pdo->prepare("
                    UPDATE narocilo 
                    SET status = 'potrjeno', 
                        prodajalec_id = ?,
                        datum_spremembe = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $narocilo_id]);
                $success = 'Naročilo je bilo uspešno potrjeno.';
            } else {
                $error = 'Naročilo ni v stanju, ki bi ga lahko potrdili.';
            }
        } 
        elseif ($action === 'preklici') {
            // Lahko prekličemo samo oddana naročila
            $stmt = $pdo->prepare("SELECT status FROM narocilo WHERE id = ?");
            $stmt->execute([$narocilo_id]);
            $trenutni_status = $stmt->fetchColumn();
            
            if ($trenutni_status === 'oddano') {
                $stmt = $pdo->prepare("
                    UPDATE narocilo 
                    SET status = 'preklicano',
                        prodajalec_id = ?,
                        datum_spremembe = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $narocilo_id]);
                $success = 'Naročilo je bilo preklicano.';
            } else {
                $error = 'Naročilo ni v stanju, ki bi ga lahko preklicali.';
            }
        }
        elseif ($action === 'storniraj') {
            // Lahko storniramo samo potrjena naročila
            $stmt = $pdo->prepare("SELECT status FROM narocilo WHERE id = ?");
            $stmt->execute([$narocilo_id]);
            $trenutni_status = $stmt->fetchColumn();
            
            if ($trenutni_status === 'potrjeno') {
                $stmt = $pdo->prepare("
                    UPDATE narocilo 
                    SET status = 'stornirano',
                        datum_spremembe = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$narocilo_id]);
                $success = 'Naročilo je bilo stornirano.';
            } else {
                $error = 'Stornirati je mogoče le potrjena naročila.';
            }
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Prišlo je do napake pri obdelavi naročila.';
    }
}

// Pridobi podatke o naročilu
$stmt = $pdo->prepare("
    SELECT n.*, 
           s.ime AS stranka_ime, 
           s.priimek AS stranka_priimek,
           s.email AS stranka_email,
           s.ulica AS stranka_ulica,
           s.hisna_stevilka AS stranka_hisna_stevilka,
           s.postna_stevilka AS stranka_postna_stevilka,
           s.posta AS stranka_posta,
           p.ime AS prodajalec_ime,
           p.priimek AS prodajalec_priimek
    FROM narocilo n
    JOIN stranka s ON n.stranka_id = s.id
    LEFT JOIN prodajalec p ON n.prodajalec_id = p.id
    WHERE n.id = ?
");
$stmt->execute([$narocilo_id]);
$narocilo = $stmt->fetch();

if (!$narocilo) {
    header('Location: index.php');
    exit();
}

// Pridobi artikle naročila
$stmt = $pdo->prepare("
    SELECT na.*, a.naziv
    FROM narocilo_artikel na
    JOIN artikel a ON na.artikel_id = a.id
    WHERE na.narocilo_id = ?
");
$stmt->execute([$narocilo_id]);
$artikli = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Podrobnosti Naročila #<?= $narocilo_id ?> - Prodajalec Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
 <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Prodajalec Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Naročila</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="artikli.php">Artikli</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="stranke.php">Stranke</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profil.php">Moj Profil</a>
                    </li>
                </ul>
                <div class="navbar-nav ms-auto">
                    <span class="nav-link">Dobrodošli, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <a class="nav-link" href="../public/logout.php">Odjava</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center">
            <h1>Naročilo #<?= $narocilo_id ?></h1>
            <a href="index.php" class="btn btn-secondary">
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
                    <div class="card-header">
                        <h5 class="card-title mb-0">Podatki o naročilu</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?= match($narocilo['status']) {
                                'oddano' => 'warning',
                                'potrjeno' => 'success',
                                'preklicano' => 'danger',
                                'stornirano' => 'secondary',
                                default => 'primary'
                            } ?>">
                                <?= ucfirst($narocilo['status']) ?>
                            </span>
                        </p>
                        <p><strong>Datum oddaje:</strong> <?= date('d.m.Y H:i', strtotime($narocilo['datum_oddaje'])) ?></p>
                        <?php if ($narocilo['datum_spremembe'] !== $narocilo['datum_oddaje']): ?>
                            <p><strong>Zadnja sprememba:</strong> <?= date('d.m.Y H:i', strtotime($narocilo['datum_spremembe'])) ?></p>
                        <?php endif; ?>
                        <p><strong>Skupna cena:</strong> <?= number_format($narocilo['skupna_cena'], 2) ?> €</p>
                        <?php if ($narocilo['prodajalec_id']): ?>
                            <p><strong>Obdelal:</strong> <?= htmlspecialchars($narocilo['prodajalec_ime'] . ' ' . $narocilo['prodajalec_priimek']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Podatki o stranki</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Ime in priimek:</strong> <?= htmlspecialchars($narocilo['stranka_ime'] . ' ' . $narocilo['stranka_priimek']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($narocilo['stranka_email']) ?></p>
                        <p><strong>Naslov:</strong><br>
                            <?= htmlspecialchars($narocilo['stranka_ulica'] . ' ' . $narocilo['stranka_hisna_stevilka']) ?><br>
                            <?= htmlspecialchars($narocilo['stranka_postna_stevilka'] . ' ' . $narocilo['stranka_posta']) ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Artikli</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Artikel</th>
                                        <th>Količina</th>
                                        <th>Cena/kos</th>
                                        <th>Skupaj</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($artikli as $artikel): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($artikel['naziv']) ?></td>
                                            <td><?= $artikel['kolicina'] ?></td>
                                            <td><?= number_format($artikel['cena_na_kos'], 2) ?> €</td>
                                            <td><?= number_format($artikel['cena_na_kos'] * $artikel['kolicina'], 2) ?> €</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Skupaj:</strong></td>
                                        <td><strong><?= number_format($narocilo['skupna_cena'], 2) ?> €</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Akcije</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" class="d-flex gap-2">
                            <?php if ($narocilo['status'] === 'oddano'): ?>
                                <button type="submit" name="action" value="potrdi" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Potrdi naročilo
                                </button>
                                <button type="submit" name="action" value="preklici" class="btn btn-danger">
                                    <i class="bi bi-x-circle"></i> Prekliči naročilo
                                </button>
                            <?php elseif ($narocilo['status'] === 'potrjeno'): ?>
                                <button type="submit" name="action" value="storniraj" class="btn btn-warning">
                                    <i class="bi bi-dash-circle"></i> Storniraj naročilo
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
