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

// Preveri, če je zahteva POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Pridobi podatke
$artikel_id = filter_input(INPUT_POST, 'artikel_id', FILTER_VALIDATE_INT);
$kolicina = filter_input(INPUT_POST, 'kolicina', FILTER_VALIDATE_INT) ?? 1;

if (!$artikel_id || $kolicina < 1) {
    $_SESSION['error'] = 'Neveljavni podatki za dodajanje v košarico.';
    header('Location: index.php');
    exit;
}

try {
    // Preveri, če artikel obstaja in je aktiven
    $stmt = $pdo->prepare("SELECT id FROM artikel WHERE id = ? AND aktiven = TRUE");
    $stmt->execute([$artikel_id]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'Artikel ne obstaja ali ni na voljo.';
        header('Location: index.php');
        exit;
    }

    // Preveri, če je artikel že v košarici
    $stmt = $pdo->prepare("SELECT kolicina FROM kosarica WHERE stranka_id = ? AND artikel_id = ?");
    $stmt->execute([$_SESSION['user_id'], $artikel_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Posodobi količino
        $stmt = $pdo->prepare("UPDATE kosarica SET kolicina = kolicina + ? WHERE stranka_id = ? AND artikel_id = ?");
        $stmt->execute([$kolicina, $_SESSION['user_id'], $artikel_id]);
    } else {
        // Dodaj nov artikel v košarico
        $stmt = $pdo->prepare("INSERT INTO kosarica (stranka_id, artikel_id, kolicina) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $artikel_id, $kolicina]);
    }

    $_SESSION['success'] = 'Artikel je bil uspešno dodan v košarico.';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Napaka pri dodajanju artikla v košarico.';
}

header('Location: index.php');
exit;
