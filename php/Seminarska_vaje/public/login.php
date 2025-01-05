<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';
session_start();

$error = null; // Inicializirajte spremenljivko za prikaz napake

// Preverite, ali je nastavljen $_SESSION['error_message']
if (!empty($_SESSION['error_message'])) {
    $error = $_SESSION['error_message']; // Prenesite sporočilo v lokalno spremenljivko
    unset($_SESSION['error_message']); // Odstranite ga iz seje
}

// Preveri prijavo
//if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//    $role = $_POST['role'] ?? ''; // Določi vlogo uporabnika
//
//    if ($role === 'admin') {
//        // Preusmeri na HTTPS in zahteva certifikat
//        header("Location: https://".$_SERVER['HTTP_HOST']."/netbeans/Seminarska_vaje/admin/login.php");
//        exit();
//    } elseif ($role === 'prodajalec') {
//        // Preusmeri na HTTPS in zahteva certifikat
//        header("Location: https://".$_SERVER['HTTP_HOST']."/netbeans/Seminarska_vaje/prodajalec/login.php");
//        exit();
//    } elseif ($role === 'stranka') {
//        // Preusmeri brez certifikata na HTTP
//        header("Location: http://".$_SERVER['HTTP_HOST']."/netbeans/Seminarska_vaje/stranka/index.php");
//        exit();
//    } else {
//        $_SESSION['error'] = "Neveljavna vloga!";
//        header("Location: index.php");
//        exit();
//    }
//}
//        # Avtorizirani uporabniki (to navadno pride iz podatkovne baze)
//        $authorized_users = ["Ana"];
//
//        # preberemo odjemačev certifikat
//        $client_cert = filter_input(INPUT_SERVER, "SSL_CLIENT_CERT");
//
//        # in ga razčlenemo
//        $cert_data = openssl_x509_parse($client_cert);
//        
//        # preberemo ime uporabnika (polje "common name")
//        $commonname = $cert_data['subject']['CN'];
        
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $geslo = $_POST['geslo'] ?? '';
    $tip = $_POST['tip_uporabnika'] ?? '';
    
    try {
        // Izberi pravo tabelo glede na tip uporabnika
        switch ($tip) {
            case 'prodajalec':
                $table = 'prodajalec';
                $redirect = '../prodajalec/index.php';
                break;
            case 'admin':
                $table = 'administrator';
                $redirect = '../admin/index.php';
                break;
            case 'stranka':
                $table = 'stranka';
                $redirect = '../stranka/index.php';
                break;
            default:
                throw new Exception('Neveljaven tip uporabnika');
        }
        
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE email = ? AND aktiven = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($geslo, $user['geslo'])) {
            session_regenerate_id(true);
            $_SESSION = []; // Clear existing session
            $_SESSION['user_type'] = $tip;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['ime'] . ' ' . $user['priimek'];
            $_SESSION['email'] = $user['email'];
            
            header("Location: $redirect");
            exit();
        } else {
                $error = 'Napačen email ali geslo.';
            }
            
        
    } catch (Exception $e) {
        $error = 'Prišlo je do napake: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prijava - Spletna Prodajalna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Spletna Prodajalna</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link active" href="login.php">Prijava</a>
                <a class="nav-link" href="registracija.php">Registracija</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Prijava</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Tip uporabnika</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tip_uporabnika" id="prodajalec" value="prodajalec" checked>
                                    <label class="form-check-label" for="prodajalec">
                                        Prodajalec
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tip_uporabnika" id="admin" value="admin">
                                    <label class="form-check-label" for="admin">
                                        Administrator
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tip_uporabnika" id="stranka" value="stranka">
                                    <label class="form-check-label" for="stranka">
                                        Stranka
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email naslov</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="geslo" class="form-label">Geslo</label>
                                <input type="password" class="form-control" id="geslo" name="geslo" required>
                            </div>

                            <button type="submit" class="btn btn-primary">Prijava</button>
                        </form>

                        <div class="mt-3">
                            <p>Še nimate računa? <a href="registracija.php">Registrirajte se</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
