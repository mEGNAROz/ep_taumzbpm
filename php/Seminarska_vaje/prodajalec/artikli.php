<?php
require_once __DIR__ . '/../config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
$uploadDir = __DIR__ . '/../uploads/';

// Preveri iskalni niz
$search = trim($_GET['search'] ?? '');

// Obdelaj akcije
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_status') {
        $artikel_id = (int)$_POST['artikel_id'];
        $novi_status = ($_POST['novi_status'] === '1') ? 1 : 0;

        try {
            $stmt = $pdo->prepare("UPDATE artikel SET aktiven = ? WHERE id = ?");
            $stmt->execute([$novi_status, $artikel_id]);
            $success = 'Status artikla je bil uspešno posodobljen.';
        } catch (PDOException $e) {
            $error = 'Prišlo je do napake pri posodabljanju statusa.';
        }
    } elseif ($action === 'add') {
        $naziv = trim($_POST['naziv'] ?? '');
        $opis = trim($_POST['opis'] ?? '');
        $cena = floatval(str_replace(',', '.', $_POST['cena'] ?? 0));

        try {
            if (empty($naziv)) {
                throw new Exception('Naziv artikla je obvezen.');
            }
            if ($cena <= 0) {
                throw new Exception('Cena mora biti večja od 0.');
            }
            if (!preg_match('/^\d+(\.\d{1,2})?$/', $cena)) {
                throw new Exception('Cena mora biti številka z največ dvema decimalnima mestoma.');
            }
            if (strlen($opis) > 500) {
                throw new Exception('Opis ne sme presegati 500 znakov.');
            }

            $stmt = $pdo->prepare("INSERT INTO artikel (naziv, opis, cena, aktiven) VALUES (?, ?, ?, TRUE)");
            $stmt->execute([$naziv, $opis, $cena]);
            $artikel_id = $pdo->lastInsertId();
            // $stmt = $pdo->prepare("INSERT INTO artikel_slike (artikel_id, pot) VALUES (?, ?)");
            //             $stmt->execute([$artikel_id, basename($_FILES['slike']['name'])]);

            if (!empty($_FILES['slike']['name'])) {
                $filename = basename($_FILES['slike']['name']);
                $targetFile = $uploadDir . $filename;
                $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

                if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    if (move_uploaded_file($_FILES['slike']['tmp_name'], $targetFile)) {
                        $stmt = $pdo->prepare("INSERT INTO artikel_slike (artikel_id, pot) VALUES (?, ?)");
                        $stmt->execute([$artikel_id, $filename]);

                        $stmt = $pdo->prepare("UPDATE artikel SET glavna_slika = ? WHERE id = ?");
                        $stmt->execute([$filename, $artikel_id]);
                        $success .= ' Artikel in slika sta bila uspešno dodana.';
                    } else {
                        throw new Exception('Napaka pri nalaganju datoteke.');
                    }
                } else {
                    throw new Exception('Dovoljene so samo JPG, JPEG, PNG in GIF datoteke.');
                }
            } else {
                $success .= ' Artikel je bil uspešno dodan.';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($action === 'edit') {
        $artikel_id = (int)$_POST['artikel_id'];
        $naziv = trim($_POST['naziv'] ?? '');
        $opis = trim($_POST['opis'] ?? '');
        $cena = floatval(str_replace(',', '.', $_POST['cena'] ?? 0));

        try {
            if (empty($naziv)) {
                throw new Exception('Naziv artikla je obvezen.');
            }
            if ($cena <= 0) {
                throw new Exception('Cena mora biti večja od 0.');
            }

            $stmt = $pdo->prepare("UPDATE artikel SET naziv = ?, opis = ?, cena = ? WHERE id = ?");
            $stmt->execute([$naziv, $opis, $cena, $artikel_id]);
            //$error .= $_FILES['slike']['name'];

            if (!empty($_FILES['slike']['name'])) {
                $filename = basename($_FILES['slike']['name']);
                $targetFile = $uploadDir . $filename;
                $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                

                if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    if (move_uploaded_file($_FILES['slike']['tmp_name'], $targetFile)) {
                        $stmt = $pdo->prepare("INSERT INTO artikel_slike (artikel_id, pot) VALUES (?, ?)");
                        $stmt->execute([$artikel_id, $filename]);
                        $stmt = $pdo->prepare("UPDATE artikel SET glavna_slika = ? WHERE id = ?");
                        $stmt->execute([$filename, $artikel_id]);

                        $success .= ' Slika je bila uspešno dodana.';
                    } else {
                        throw new Exception('Napaka pri nalaganju datoteke.');
                    }
                } else {
                    throw new Exception('Dovoljene so samo JPG, JPEG, PNG in GIF datoteke.');
                }
            }

            $success = 'Artikel je bil uspešno posodobljen.';
        } catch (Exception $e) {
            $error .= $e->getMessage();
        }
    } elseif ($action === 'delete_image') {
        $slika_id = (int)$_POST['slika_id'];

        try {
            $stmt = $pdo->prepare("SELECT pot FROM artikel_slike WHERE id = ?");
            $stmt->execute([$slika_id]);
            $slika = $stmt->fetch();

            if ($slika && file_exists($uploadDir . $slika['pot'])) {
                unlink($uploadDir . $slika['pot']);
            }

            $stmt = $pdo->prepare("DELETE FROM artikel_slike WHERE id = ?");
            $stmt->execute([$slika_id]);
            $success = 'Slika je bila uspešno odstranjena.';
        } catch (PDOException $e) {
            $error = 'Prišlo je do napake pri brisanju slike.';
        }
    }
}

// Pridobi vse artikle
$query = "
    SELECT a.*, 
           (SELECT COUNT(*) FROM narocilo_artikel WHERE artikel_id = a.id) as st_narocil,
           COUNT(o.id) AS st_ocen,
           ROUND(AVG(o.ocena), 1) AS povprecna_ocena
    FROM artikel a
    LEFT JOIN artikel_ocene o ON a.id = o.artikel_id
    WHERE a.aktiven = TRUE
";

$params = [];
if ($search) {
    $query .= " AND MATCH(a.naziv, a.opis) AGAINST(:search IN BOOLEAN MODE)";
    $params['search'] = $search;
}

$query .= " GROUP BY a.id ORDER BY a.naziv";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$artikli = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upravljanje Artiklov - Prodajalec Panel</title>
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
                        <a class="nav-link active" href="artikli.php">Artikli</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="stranke.php">Stranke</a>
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
        <?php if ($success): ?>
            <div class="alert alert-success mt-3"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>Upravljanje Artiklov</h1>
                </div>


                <div class="card mt-4">
                    <div class="card-body">
                        <form method="get" class="mb-4">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Išči po artiklih..." value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-primary">Išči</button>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Naziv</th>
                                        <th>Opis</th>
                                        <th>Cena</th>
                                        <th>Povprečna Ocena</th>
                                        <th>Št. Naročil</th>
                                        <th>Status</th>
                                        <th>Akcije</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($artikli as $artikel): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($artikel['naziv']) ?></td>
                                            <td><?= htmlspecialchars($artikel['opis']) ?></td>
                                            <td><?= number_format($artikel['cena'], 2, ',', '.') ?> €</td>
                                            <!-- Prikaz povprečne ocene -->
                                            <td>
                                                <?php if ($artikel['st_ocen'] > 0): ?>
                                                    <?= number_format($artikel['povprecna_ocena'], 1) ?> (<?= $artikel['st_ocen'] ?> ocen)
                                                <?php else: ?>
                                                    Ni ocen
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $artikel['st_narocil'] ?></td>
                                            <td>
                                                <span class="badge bg-<?= $artikel['aktiven'] ? 'success' : 'danger' ?>">
                                                    <?= $artikel['aktiven'] ? 'Aktiven' : 'Neaktiven' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-sm" 
                                                        onclick="nastaviArtikel(<?= htmlspecialchars(json_encode($artikel)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="artikel_id" value="<?= $artikel['id'] ?>">
                                                    <input type="hidden" name="novi_status" value="<?= $artikel['aktiven'] ? '0' : '1' ?>">
                                                    <button type="submit" class="btn btn-<?= $artikel['aktiven'] ? 'danger' : 'success' ?> btn-sm">
                                                        <?php if ($artikel['aktiven']): ?>
                                                            <i class="bi bi-x-circle"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-check-circle"></i>
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
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0" id="formTitle">Dodaj Nov Artikel</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" id="artikelForm" enctype="multipart/form-data">
                            <input type="hidden" name="action" id="formAction" value="add">
                            <input type="hidden" name="artikel_id" id="artikelId" value="">

                            <div class="mb-3">
                                <label for="naziv" class="form-label">Naziv</label>
                                <input type="text" class="form-control" id="naziv" name="naziv" required>
                            </div>

                            <div class="mb-3">
                                <label for="opis" class="form-label">Opis</label>
                                <textarea class="form-control" id="opis" name="opis" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="cena" class="form-label">Cena (€)</label>
                                <input type="number" class="form-control" id="cena" name="cena" 
                                       step="0.01" min="0.01" required>
                            </div>

                            <div class="mb-3">
                                <label for="slike" class="form-label">Dodaj Slike</label>
                                <input type="file" class="form-control" id="slike" name="slike" accept="image/*" multiple>
                            </div>

                            <div id="prikazSlik" class="mb-3">
                                <!-- Prikaz obstoječih slik -->
                                <?php if (isset($artikel['slike']) && !empty($artikel['slike'])): ?>
                                    <div class="row">
                                        <?php foreach ($artikel['slike'] as $slika): ?>
                                            <div class="col-md-4 mb-3">
                                                <div class="card">
                                                    <img src="../uploads/<?= htmlspecialchars($slika['pot']) ?>" class="card-img-top" alt="Slika">
                                                    <div class="card-body text-center">
                                                        <form method="post">
                                                            <input type="hidden" name="action" value="delete_image">
                                                            <input type="hidden" name="slika_id" value="<?= $slika['id'] ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">
                                                                <i class="bi bi-trash"></i> Odstrani
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="bi bi-plus-circle"></i> Dodaj Artikel
                                </button>
                                <button type="button" class="btn btn-secondary" id="resetBtn" style="display: none;">
                                    <i class="bi bi-x-circle"></i> Prekliči Urejanje
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function nastaviArtikel(artikel) {
        document.getElementById('formTitle').textContent = 'Uredi Artikel';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('artikelId').value = artikel.id;
        document.getElementById('naziv').value = artikel.naziv;
        document.getElementById('opis').value = artikel.opis;
        document.getElementById('cena').value = artikel.cena;
        document.getElementById('submitBtn').innerHTML = '<i class="bi bi-save"></i> Shrani Spremembe';
        document.getElementById('resetBtn').style.display = 'block';
    }

    document.getElementById('resetBtn').addEventListener('click', function() {
        document.getElementById('formTitle').textContent = 'Dodaj Nov Artikel';
        document.getElementById('artikelForm').reset();
        document.getElementById('formAction').value = 'add';
        document.getElementById('artikelId').value = '';
        document.getElementById('submitBtn').innerHTML = '<i class="bi bi-plus-circle"></i> Dodaj Artikel';
        this.style.display = 'none';
    });
    </script>
</body>
</html>