<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Povezava na bazo
$servername = "localhost";
$username = "root";
$password = "ep"; 
$dbname = "eprodajalna";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Povezava na bazo ni uspela: " . $conn->connect_error]));
}

// Pridobi akcijo
$action = $_GET['action'] ?? '';

switch ($action) {

    // 1. PRIJAVA UPORABNIKA
    case 'login':
        // Preveri, če je metoda POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["error" => "Metoda ni podprta. Uporabi POST."]);
            exit();
        }

        // Preveri JSON format
        $rawBody = file_get_contents('php://input'); // Surovi podatki
        $data = json_decode($rawBody, true); // Dekodiraj JSON

        // Preveri, ali je JSON veljaven
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(["error" => "Neveljaven JSON format."]);
            exit();
        }

        // Preveri manjkajoče parametre
        if (!isset($data['email']) || !isset($data['password'])) {
            echo json_encode(["error" => "Manjkajoči parametri."]);
            exit();
        }

        $email = $conn->real_escape_string($data['email']);
        $password = $data['password'];

        // Preveri uporabnika v vseh tabelah
        $query = "
            SELECT 'stranka' AS vloga, id, ime, priimek, email, geslo
            FROM stranka WHERE email = ? AND aktiven = 1
            UNION ALL
            SELECT 'prodajalec' AS vloga, id, ime, priimek, email, geslo
            FROM prodajalec WHERE email = ? AND aktiven = 1
            UNION ALL
            SELECT 'administrator' AS vloga, id, ime, priimek, email, geslo
            FROM administrator WHERE email = ? AND aktiven = 1
        ";

        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param('sss', $email, $email, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if (password_verify($password, $row['geslo'])) {
                    unset($row['geslo']); // Odstrani geslo iz odgovora
                    $row['success'] = true; // Dodaj success=true
                    echo json_encode($row);
                } else {
                    echo json_encode(["error" => "Napačno geslo."]);
                }
            } else {
                echo json_encode(["error" => "Uporabnik ne obstaja."]);
            }
            $stmt->close();
        } else {
            echo json_encode(["error" => "Napaka pri pripravi poizvedbe: " . $conn->error]);
        }
        break;

    // 2. PRIDOBI ARTIKLE
    case 'artikli':
        $sql = "SELECT 
            a.id, 
            a.naziv, 
            a.opis, 
            a.cena, 
            IFNULL(ROUND(AVG(o.ocena), 1), 'Ni ocen') AS povprecna_ocena,
            GROUP_CONCAT(DISTINCT s.pot) AS slike
        FROM artikel a
        LEFT JOIN artikel_ocene o ON a.id = o.artikel_id
        LEFT JOIN artikel_slike s ON a.id = s.artikel_id
        WHERE a.aktiven = 1
        GROUP BY a.id";

        $result = $conn->query($sql);
        $artikli = [];

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['slike'] = $row['slike'] ? explode(',', $row['slike']) : [];
                $artikli[] = $row;
            }
        }
        echo json_encode($artikli);
        exit();

    // 3. PRIDOBI PROFIL UPORABNIKA
    // 3. PRIDOBI PROFIL UPORABNIKA
    case 'profil':
    // Preveri metodo
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // PRIDOBI PROFIL
            $userId = $_GET['id'] ?? '';
            $vloga = $_GET['vloga'] ?? '';

            if (!empty($userId) && !empty($vloga)) {
                $table = ($vloga === 'stranka') ? 'stranka' : (($vloga === 'prodajalec') ? 'prodajalec' : 'administrator');
                $sql = "SELECT ime, priimek, ulica, hisna_stevilka, posta, postna_stevilka, email 
                        FROM $table WHERE id = ? AND aktiven = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                echo json_encode($result->fetch_assoc() ?: ["error" => "Uporabnik ni najden."]);
                $stmt->close();
            } else {
                echo json_encode(["status" => "error", "message" => "Manjkajoči parametri."]);
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // POSODOBI PROFIL
            $data = json_decode(file_get_contents('php://input'), true);

            if (isset($data['id'], $data['vloga'], $data['ime'], $data['priimek'], $data['email'], $data['ulica'], $data['hisna_stevilka'], $data['posta'], $data['postna_stevilka'])) {
                $id = $data['id'];
                $vloga = $data['vloga'];
                $ime = $conn->real_escape_string($data['ime']);
                $priimek = $conn->real_escape_string($data['priimek']);
                $email = $conn->real_escape_string($data['email']);
                $ulica = $conn->real_escape_string($data['ulica']);
                $hisnaStevilka = $conn->real_escape_string($data['hisna_stevilka']);
                $posta = $conn->real_escape_string($data['posta']);
                $postnaStevilka = $conn->real_escape_string($data['postna_stevilka']);

                $table = ($vloga === 'stranka') ? 'stranka' : (($vloga === 'prodajalec') ? 'prodajalec' : 'administrator');
                $sql = "UPDATE $table 
                        SET ime = ?, priimek = ?, email = ?, ulica = ?, hisna_stevilka = ?, posta = ?, postna_stevilka = ?
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssssssi', $ime, $priimek, $email, $ulica, $hisnaStevilka, $posta, $postnaStevilka, $id);

                if ($stmt->execute()) {
                    echo json_encode(["status" => "success", "message" => "Podatki uspešno posodobljeni."]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Napaka pri posodobitvi podatkov."]);
                }

                $stmt->close();
            } else {
                echo json_encode(["error" => "Manjkajoči parametri."]);
            }
        } else {
            echo json_encode(["error" => "Metoda ni podprta. Uporabi GET ali POST."]);
        }
        break;




    // 4. PRIDOBI KOŠARICO
    case 'kosarica':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // PRIDOBI KOŠARICO
            $userId = $_GET['id'] ?? '';

            if (!empty($userId)) {
                $sql = "SELECT k.id, a.naziv, a.cena, k.kolicina 
                        FROM kosarica k
                        JOIN artikel a ON k.artikel_id = a.id
                        WHERE k.stranka_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $result = $stmt->get_result();

                $kosarica = [];
                while ($row = $result->fetch_assoc()) {
                    $kosarica[] = $row;
                }

                echo json_encode($kosarica ?: ["status" => "error", "message" => "Košarica je prazna."]);
                $stmt->close();
            } else {
                echo json_encode(["status" => "error", "message" => "Manjkajoči parametri."]);
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // ZAKLJUČI NAKUP
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = $data['id'] ?? '';

            if (!empty($userId)) {
                // Preveri, če ima uporabnik artikle v košarici
                $sql = "SELECT k.artikel_id, k.kolicina, a.cena 
                        FROM kosarica k
                        JOIN artikel a ON k.artikel_id = a.id
                        WHERE k.stranka_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $result = $stmt->get_result();

                $artikli = [];
                $totalCena = 0;

                while ($row = $result->fetch_assoc()) {
                    $artikli[] = $row;
                    $totalCena += $row['cena'] * $row['kolicina'];
                }

                $stmt->close();

                if (empty($artikli)) {
                    echo json_encode(["status" => "error", "message" => "Košarica je prazna."]);
                    exit();
                }

                // Vstavi naročilo
                $sql = "INSERT INTO narocilo (stranka_id, skupna_cena) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('id', $userId, $totalCena);
                $stmt->execute();
                $narociloId = $stmt->insert_id;
                $stmt->close();

                // Vstavi artikle v naročilo
                $sql = "INSERT INTO narocilo_artikel (narocilo_id, artikel_id, kolicina, cena_na_kos) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                foreach ($artikli as $artikel) {
                    $stmt->bind_param('iiid', $narociloId, $artikel['artikel_id'], $artikel['kolicina'], $artikel['cena']);
                    $stmt->execute();
                }

                $stmt->close();

                // Očisti košarico
                $sql = "DELETE FROM kosarica WHERE stranka_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $stmt->close();

                echo json_encode(["status" => "success", "message" => "Nakup uspešno zaključen."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Manjkajoči parametri."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Metoda ni podprta. Uporabi GET ali POST."]);
        }
        break;

    case 'zakljuci_nakup':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        // Preveri ID stranke
        if (isset($data['id'])) {
            $stranka_id = $data['id'];

            $sql = "SELECT k.artikel_id, k.kolicina, a.cena
                    FROM kosarica k
                    JOIN artikel a ON k.artikel_id = a.id
                    WHERE k.stranka_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $stranka_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $items = $result->fetch_all(MYSQLI_ASSOC);

            if (!empty($items)) {
                // Izračunaj skupno ceno
                $skupna_cena = 0;
                foreach ($items as $item) {
                    $skupna_cena += $item['cena'] * $item['kolicina'];
                }

                // Ustvari novo naročilo
                $sql = "INSERT INTO narocilo (stranka_id, skupna_cena, status) VALUES (?, ?, 'oddano')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('id', $stranka_id, $skupna_cena);
                $stmt->execute();
                $narocilo_id = $conn->insert_id;

                // Shrani artikle v naročilo
                $sql = "INSERT INTO narocilo_artikel (narocilo_id, artikel_id, kolicina, cena_na_kos) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                foreach ($items as $item) {
                    $stmt->bind_param('iiid', $narocilo_id, $item['artikel_id'], $item['kolicina'], $item['cena']);
                    $stmt->execute();
                }

                // Počisti košarico
                $sql = "DELETE FROM kosarica WHERE stranka_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $stranka_id);
                $stmt->execute();

                echo json_encode(["status" => "success", "message" => "Nakup uspešno zaključen."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Košarica je prazna."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Manjkajoči parametri."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Neveljavna metoda."]);
    }
    break;


    
        
    // 5. POSODOBI PROFIL
    case 'updateProfile':
        // Preveri, ali je metoda POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["error" => "Metoda ni podprta. Uporabi POST."]);
            exit();
        }

        // Preberi telo zahteve
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

        // Preveri, ali je JSON veljaven
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(["error" => "Neveljaven JSON format."]);
            exit();
        }

        // Preveri manjkajoče parametre
        if (!isset($data['id']) || !isset($data['vloga']) || !isset($data['ime']) || !isset($data['priimek']) || !isset($data['email'])) {
            echo json_encode(["error" => "Manjkajoči parametri."]);
            exit();
        }

        $id = $data['id'];
        $vloga = $data['vloga'];
        $ime = $conn->real_escape_string($data['ime']);
        $priimek = $conn->real_escape_string($data['priimek']);
        $email = $conn->real_escape_string($data['email']);

        // Preveri tabelo glede na vlogo
        $table = ($vloga === 'stranka') ? 'stranka' : (($vloga === 'prodajalec') ? 'prodajalec' : 'administrator');
        $sql = "UPDATE $table SET ime = ?, priimek = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssi', $ime, $priimek, $email, $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Podatki uspešno posodobljeni."]);
        } else {
            echo json_encode(["error" => "Napaka pri posodabljanju podatkov: " . $stmt->error]);
        }

        $stmt->close();
        break;
        
        
    case 'orders':
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId = $_GET['id'] ?? '';

        if (!empty($userId)) {
            $sql = "
                SELECT id, datum_oddaje AS date, skupna_cena AS totalPrice, status
                FROM narocilo
                WHERE stranka_id = ?
                ORDER BY datum_oddaje DESC
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $orders = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($orders);
        } else {
            echo json_encode(["status" => "error", "message" => "Manjkajoči parametri."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Neveljavna metoda."]);
    }
    break;



    case 'orders_details':
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $narocilo_id = $_GET['id'] ?? '';

        if (!empty($narocilo_id)) {
            // Pridobi podrobnosti naročila
            $sql = "
                SELECT n.id, n.datum_oddaje, n.skupna_cena, n.status, 
                       a.naziv, na.kolicina, na.cena_na_kos
                FROM narocilo n
                JOIN narocilo_artikel na ON n.id = na.narocilo_id
                JOIN artikel a ON na.artikel_id = a.id
                WHERE n.id = ?
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $narocilo_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $order = null;
            $items = [];

            while ($row = $result->fetch_assoc()) {
                if (!$order) {
                    $order = [
                        "id" => $row['id'],
                        "datum_oddaje" => $row['datum_oddaje'],
                        "skupna_cena" => $row['skupna_cena'],
                        "status" => $row['status']
                    ];
                }
                $items[] = [
                    "naziv" => $row['naziv'],
                    "kolicina" => $row['kolicina'],
                    "cena_na_kos" => $row['cena_na_kos']
                ];
            }

            if ($order) {
                echo json_encode([
                    "status" => "success",
                    "order" => $order,
                    "items" => $items
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Naročilo ni najdeno."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Manjkajoči parametri."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Neveljavna metoda."]);
    }
    break;



}

$conn->close();
?>
