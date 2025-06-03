<?php
session_start();
use GuzzleHttp\Client;
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['userID'])) {
        echo json_encode(['error' => 'Nie jesteś zalogowany.']);
        exit();
    }

    $userID = $_SESSION['userID'];
    $input = file_get_contents("php://input");
    $json = json_decode($input, true);
    $selectedFiles = $json['selectedFiles'] ?? [];

    if (empty($selectedFiles)) {
        echo json_encode(['error' => 'Nie wybrano plików.']);
        exit();
    }

    require 'vendor/autoload.php';

    $apiKey = "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";

    $responseData = [];

    try {
        $conn = new PDO("pgsql:host=db;dbname=db", "docker", "docker");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $conn->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");

        $placeholders = implode(',', array_fill(0, count($selectedFiles), '?'));
        $stmt = $conn->prepare("SELECT material_name, material_path FROM materials WHERE materialid IN ($placeholders) AND userid = ?");
        $stmt->execute([...$selectedFiles, $userID]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summaries = [];
        $client = new Client();

        foreach ($files as $file) {
            $content = file_get_contents($file['material_path']);
            $content = mb_convert_encoding($content, "UTF-8", "auto");

            $response = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => "Bearer $apiKey",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Jesteś pomocnym asystentem edukacyjnym. Tworzysz przystępne do nauki podsumowania tekstów w języku polskim.'],
                        ['role' => 'user', 'content' => "Stwórz przystępne do nauki podsumowanie tego dokumentu w języku polskim:\n\n" . substr($content, 0, 4000)],
                    ]
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            $summaries[] = [
                'material_name' => $file['material_name'],
                'summary' => $result['choices'][0]['message']['content'] ?? 'Brak podsumowania'
            ];
        }

        echo json_encode(['success' => true, 'data' => $summaries], JSON_UNESCAPED_UNICODE);
        exit();

    } catch (Exception $e) {
        echo json_encode(['error' => 'Błąd: ' . $e->getMessage()]);
        exit();
    }
}


$isLoggedIn = isset($_SESSION['userID']);
$email = $_SESSION['email'] ?? null;
$userID = $_SESSION['userID'] ?? null;

$folders = [];
$materialsByFolder = [];

if ($isLoggedIn) {
    try {
        $conn = new PDO("pgsql:host=db;dbname=db", "docker", "docker");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT folderid, folder_name FROM folders WHERE userid = :userID");
        $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
        $stmt->execute();
        $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("SELECT m.materialid, m.material_name, m.material_path, m.folderid FROM materials m INNER JOIN folders f ON m.folderid = f.folderid WHERE f.userid = :userID");
        $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
        $stmt->execute();
        $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($materials as $material) {
            $materialsByFolder[$material['folderid']][] = $material;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && isset($_POST['folderID'])) {
            $folderID = intval($_POST['folderID']);
            $fileName = $_FILES['file']['name'];
            $uploadsDir = __DIR__ . '/uploads';
            $filePath = $uploadsDir . '/' . basename($fileName);

            if (!is_dir($uploadsDir)) {
                if (!mkdir($uploadsDir, 0777, true)) {
                    die("❌ Failed to create uploads directory.");
                }
            }

            $stmt = $conn->prepare("SELECT folderid FROM folders WHERE folderid = :folderID AND userid = :userID");
            $stmt->bindParam(':folderID', $folderID, PDO::PARAM_INT);
            $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
            $stmt->execute();
            $folderExists = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$folderExists) {
                die("❌ Invalid folder selection.");
            }

            if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                try {
                    $stmt = $conn->prepare("INSERT INTO materials (userid, material_name, material_path, folderid) VALUES (:userID, :materialName, :materialPath, :folderID)");
                    $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
                    $stmt->bindParam(':materialName', $fileName, PDO::PARAM_STR);
                    $stmt->bindParam(':materialPath', $filePath, PDO::PARAM_STR);
                    $stmt->bindParam(':folderID', $folderID, PDO::PARAM_INT);
                    $stmt->execute();

                    header("Location: generate.php");
                    exit();
                } catch (PDOException $e) {
                    die("❌ Error adding material: " . $e->getMessage());
                }
            } else {
                die("❌ Failed to upload file.");
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['folderName'])) {
            $folderName = trim($_POST['folderName']);
            if (!empty($folderName)) {
                try {
                    $stmt = $conn->prepare("INSERT INTO folders (userid, folder_name) VALUES (:userID, :folderName)");
                    $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
                    $stmt->bindParam(':folderName', $folderName, PDO::PARAM_STR);
                    $stmt->execute();

                    header("Location: generate.php");
                    exit();
                } catch (PDOException $e) {
                    die("❌ Error adding folder: " . $e->getMessage());
                }
            } else {
                echo "❌ Folder name cannot be empty.";
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteMaterialID'])) {
            $materialID = intval($_POST['deleteMaterialID']);
            try {
                $stmt = $conn->prepare("DELETE FROM materials WHERE materialid = :materialID AND userid = :userID");
                $stmt->bindParam(':materialID', $materialID, PDO::PARAM_INT);
                $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
                $stmt->execute();

                header("Location: generate.php");
                exit();
            } catch (PDOException $e) {
                die("❌ Error deleting material: " . $e->getMessage());
            }
        }
    } catch (PDOException $e) {
        die("❌ Database connection error: " . $e->getMessage());
    }
}
?>
