<?php
// admin/login.php
session_start();

// Preveri, ali je certifikat poslan
if (empty($_SERVER['SSL_CLIENT_S_DN']) || empty($_SERVER['SSL_CLIENT_M_SERIAL'])) {
    die("Dostop zavrnjen: Manjkajoč certifikat.");
}

// Preveri serijsko številko certifikata (admin ima unikatni certifikat)
$adminCertSerial = '01A2B3C4D5E6F7'; // Zamenjaj z dejansko serijsko številko
$certSerial = strtoupper(trim($_SERVER['SSL_CLIENT_M_SERIAL']));

if ($certSerial !== $adminCertSerial) {
    die("Dostop zavrnjen: Neveljaven certifikat.");
}

// Nadaljuj, če je certifikat veljaven
$_SESSION['admin_authenticated'] = true;
header("Location: index.php");
exit();
?>