<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Preveri, ali je uporabnik prijavljen kot stranka
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'stranka') {
    header('Location: ../public/login.php');
    exit();
}

// Preveri vhodne podatke
$artikel_id = (int)($_POST['artikel_id'] ?? 0);
$ocena = (int)($_POST['ocena'] ?? 0);
$komentar = trim($_POST['komentar'] ?? '');

if ($artikel_id <= 0 || $ocena < 1 || $ocena > 5) {
    $_SESSION['error'] = "Neveljavna ocena ali artikel.";
    header('Location: index.php');
    exit();
}

try {
    // Preveri, ali je uporabnik že podal oceno za ta artikel
    $stmt = $pdo->prepare("
        SELECT id FROM artikel_ocene 
        WHERE artikel_id = ? AND stranka_id = ?
    ");
    $stmt->execute([$artikel_id, $_SESSION['user_id']]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Posodobi obstoječo oceno
        $stmt = $pdo->prepare("
            UPDATE artikel_ocene 
            SET ocena = ?, komentar = ?, created_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$ocena, $komentar, $existing['id']]);
        $_SESSION['success'] = "Vaša ocena je bila posodobljena.";
    } else {
        // Vstavi novo oceno
        $stmt = $pdo->prepare("
            INSERT INTO artikel_ocene (artikel_id, stranka_id, ocena, komentar)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$artikel_id, $_SESSION['user_id'], $ocena, $komentar]);
        $_SESSION['success'] = "Vaša ocena je bila dodana.";
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Napaka pri shranjevanju ocene.";
}

header('Location: index.php');
exit();
?>
