<?php
session_start();
session_destroy();

// Preusmeritev nazaj na HTTP
$http_host = $_SERVER['HTTP_HOST'];
$uri = '/netbeans/Seminarska_vaje/public/index.php';
header("Location: http://$http_host$uri");
exit();
?>
