<?php
session_start();

require 'vendor/autoload.php';
use GuzzleHttp\Client;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['userID'])) {
        echo json_encode(['error' => 'Nie jesteś zalogowany.']);
        exit();
    }

    $userID = $_SESSION['userID'];
    $input = file_get_contents("php://input");
    $json = json_decode($input, true);
    $selectedFiles = $json['selectedFiles'] ?? [];
    $selectedFolders = $json['selectedFolders'] ?? [];
    $noteText = $json['noteText'] ?? '';

    if (empty($selectedFiles) && empty($selectedFolders) && empty($noteText)) {
        echo json_encode(['error' => 'Nie wybrano plików ani folderów, ani nie podano notatki.']);
        exit();
    }

    $apiKey = "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";
    $responseData = [];

    try {
        $conn = new PDO("pgsql:host=db;dbname=db", "docker", "docker");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (!empty($noteText) && count($selectedFolders) === 1) {
            $folderID = intval($selectedFolders[0]);
            $noteName = "notatka_" . date("Ymd_His") . ".txt";
            $uploadsDir = __DIR__ . '/uploads';
            $filePath = $uploadsDir . '/' . basename($noteName);

            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0777, true);
            }

            file_put_contents($filePath, $noteText);

            $stmt = $conn->prepare("INSERT INTO materials (userid, material_name, material_path, folderid) VALUES (:userID, :materialName, :materialPath, :folderID)");
            $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
            $stmt->bindParam(':materialName', $noteName, PDO::PARAM_STR);
            $stmt->bindParam(':materialPath', $filePath, PDO::PARAM_STR);
            $stmt->bindParam(':folderID', $folderID, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Notatka została zapisana.']);
            exit();
        }

        if (!empty($selectedFolders)) {
            $placeholders = implode(',', array_fill(0, count($selectedFolders), '?'));
            $stmt = $conn->prepare("SELECT materialid FROM materials WHERE folderid IN ($placeholders) AND userid = ?");
            $stmt->execute([...$selectedFolders, $userID]);
            $folderFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $selectedFiles = array_merge($selectedFiles, $folderFiles);
        }

        if (empty($selectedFiles)) {
            echo json_encode(['error' => 'Brak plików do przetworzenia.']);
            exit();
        }

        $selectedFiles = array_unique($selectedFiles);

        $placeholders = implode(',', array_fill(0, count($selectedFiles), '?'));
        $stmt = $conn->prepare("SELECT material_name, material_path FROM materials WHERE materialid IN ($placeholders) AND userid = ?");
        $stmt->execute([...$selectedFiles, $userID]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summaries = [];
        $client = new Client();

        foreach ($files as $file) {
            $path = $file['material_path'];

            if (!file_exists($path)) {
                $summaries[] = [
                    'material_name' => $file['material_name'],
                    'summary' => '❌ Plik nie istnieje: ' . $path
                ];
                continue;
            }

            $content = file_get_contents($path);
            if ($content === false) {
                $summaries[] = [
                    'material_name' => $file['material_name'],
                    'summary' => '❌ Nie udało się odczytać pliku: ' . $file['material_name']
                ];
                continue;
            }

            $content = mb_convert_encoding($content, "UTF-8", "auto");

            try {
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

                if (!isset($result['choices'][0]['message']['content'])) {
                    throw new Exception("Brak poprawnej odpowiedzi z API OpenAI.");
                }

                $summaries[] = [
                    'material_name' => $file['material_name'],
                    'summary' => $result['choices'][0]['message']['content']
                ];
            } catch (Exception $e) {
                $summaries[] = [
                    'material_name' => $file['material_name'],
                    'summary' => '❌ Błąd API: ' . $e->getMessage()
                ];
            }
        }

        echo json_encode(['success' => true, 'data' => $summaries], JSON_UNESCAPED_UNICODE);
        exit();

    } catch (Exception $e) {
        echo json_encode([
            'error' => 'Błąd: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
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

        $stmt = $conn->prepare("
            SELECT m.materialid, m.material_name, m.material_path, m.folderid
            FROM materials m
            INNER JOIN folders f ON m.folderid = f.folderid
            WHERE f.userid = :userID
        ");
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

            error_log("Upload attempt - File: " . $fileName);
            error_log("Upload directory: " . $uploadsDir);
            error_log("File path: " . $filePath);
            error_log("Folder ID: " . $folderID);
            error_log("User ID: " . $userID);

            if (!is_dir($uploadsDir)) {
                error_log("Creating uploads directory...");
                if (!mkdir($uploadsDir, 0777, true)) {
                    error_log("Failed to create uploads directory. Error: " . error_get_last()['message']);
                    die("❌ Failed to create uploads directory.");
                }
                error_log("Uploads directory created successfully");
            }

            if (!is_writable($uploadsDir)) {
                error_log("Uploads directory is not writable");
                chmod($uploadsDir, 0777);
                if (!is_writable($uploadsDir)) {
                    die("❌ Uploads directory is not writable");
                }
            }

            $stmt = $conn->prepare("SELECT folderid FROM folders WHERE folderid = :folderID AND userid = :userID");
            $stmt->bindParam(':folderID', $folderID, PDO::PARAM_INT);
            $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
            $stmt->execute();
            $folderExists = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$folderExists) {
                error_log("Invalid folder selection. Folder ID: " . $folderID . ", User ID: " . $userID);
                die("❌ Invalid folder selection.");
            }

            error_log("Attempting to move uploaded file...");
            if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                error_log("File moved successfully");
                try {
                    $stmt = $conn->prepare("INSERT INTO materials (userid, material_name, material_path, folderid) VALUES (:userID, :materialName, :materialPath, :folderID)");
                    $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
                    $stmt->bindParam(':materialName', $fileName, PDO::PARAM_STR);
                    $stmt->bindParam(':materialPath', $filePath, PDO::PARAM_STR);
                    $stmt->bindParam(':folderID', $folderID, PDO::PARAM_INT);
                    $stmt->execute();
                    error_log("Material added to database successfully");

                    header("Location: generate.php");
                    exit();
                } catch (PDOException $e) {
                    error_log("Error adding material to database: " . $e->getMessage());
                    die("❌ Error adding material: " . $e->getMessage());
                }
            } else {
                error_log("Failed to move uploaded file. Upload error code: " . $_FILES['file']['error']);
                error_log("Temporary file exists: " . (file_exists($_FILES['file']['tmp_name']) ? 'yes' : 'no'));
                error_log("Destination path is writable: " . (is_writable(dirname($filePath)) ? 'yes' : 'no'));
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteFolderIDs'])) {
        $deleteFolderIDs = $_POST['deleteFolderIDs'];

        if (is_array($deleteFolderIDs)) {
            foreach ($deleteFolderIDs as $folderID) {
                $folderID = intval($folderID);

                $stmt = $conn->prepare("DELETE FROM folders WHERE folderid = :folderID AND userid = :userID");
                $stmt->bindParam(':folderID', $folderID, PDO::PARAM_INT);
                $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
                $stmt->execute();
            }

            header("Location: generate.php");
            exit();
        }
    }


    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteID']) && isset($_POST['deleteType'])) {
        $deleteID = intval($_POST['deleteID']);
        $deleteType = $_POST['deleteType'];

        try {
            if ($deleteType === 'material') {
                $stmt = $conn->prepare("DELETE FROM materials WHERE materialid = :deleteID AND userid = :userID");
                $stmt->bindParam(':deleteID', $deleteID, PDO::PARAM_INT);
                $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
                $stmt->execute();
            } elseif ($deleteType === 'folder') {
                $stmt = $conn->prepare("DELETE FROM studycards WHERE folderid = :deleteID");
                $stmt->bindParam(':deleteID', $deleteID, PDO::PARAM_INT);
                $stmt->execute();

                $stmt = $conn->prepare("DELETE FROM folders WHERE folderid = :deleteID AND userid = :userID");
                $stmt->bindParam(':deleteID', $deleteID, PDO::PARAM_INT);
                $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                throw new Exception("Invalid delete type.");
            }

            header("Location: generate.php");
            exit();
        } catch (Exception $e) {
            die("❌ Error deleting item: " . $e->getMessage());
        }
    }


    } catch (PDOException $e) {
        die("❌ Database connection error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Summary</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="navbar.css">

    <link rel="stylesheet" href="styles_generate.css">

    <script src="script_generate.js" defer></script>
</head>
<body>
<div class="navbar">
        <div class="nav-logo">
            <img src="logo.png" alt="Study Buddy">
        </div>
        <div class="nav-menu" id="nav-menu">
            <div class="nav-links">
                <a href="home-page.php">Home</a>
                <a href="generate.php"  class="active">Create Summary</a>
                <a href="study-cards.php">Study Cards</a>
            </div>
            <div>
                <?php if ($isLoggedIn): ?>
                    <span>Witaj, <?php echo htmlspecialchars($email); ?></span>
                    <a href="logout.php" class="login-btn" >Wyloguj się</a>
                <?php else: ?>
                    <a href="login.html" class="login-btn">Log in</a>
                    <a href="register.html" class="signup-btn" >Sign up</a> 
                <?php endif; ?>
            </div>
        </div>
        <button class="hamburger" id="hamburger">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
    <div id="loading" class="loading hidden">
    <i class="fas fa-spinner fa-spin"></i> Generowanie streszczenia...
</div>


    <div class="container">
        <div class="white-block">
            <div class="left-section">
                <h1>Add Materials</h1>
                
                <!-- Add hidden file upload form -->
                <form id="uploadForm" action="generate.php" method="POST" enctype="multipart/form-data" style="display: none;">
                    <input type="file" id="fileInput" name="file">
                    <input type="hidden" id="selectedFolderID" name="folderID" value="">
                </form>

                <div class="file-list" id="fileList">
                    <?php if (!empty($folders)): ?>
                        <?php foreach ($folders as $folder): ?>
                            <div class="folder-item">
                                <div class="folder-header">
                                    <i class="fas fa-folder"></i> 
                                    <input type="checkbox" class="folder-checkbox" id="folder-<?php echo htmlspecialchars($folder['folderid']); ?>" value="<?php echo htmlspecialchars($folder['folderid']); ?>">
                                    <label for="folder-<?php echo htmlspecialchars($folder['folderid']); ?>" class="folder-label">
                                        <?php echo htmlspecialchars($folder['folder_name']); ?>
                                    </label>
                                </div>
                                <?php if (!empty($materialsByFolder[$folder['folderid']])): ?>
                                    <ul class="material-list">
                                        <?php foreach ($materialsByFolder[$folder['folderid']] as $material): ?>
                                            <li class="material-item">
                                                <div class="material-content">
                                                    <input type="checkbox" class="material-checkbox" id="material-<?php echo htmlspecialchars($material['materialid']); ?>" value="<?php echo htmlspecialchars($material['materialid']); ?>">
                                                    <label for="material-<?php echo htmlspecialchars($material['materialid']); ?>" class="material-label">
                                                        <i class="fas fa-paperclip"></i>
                                                        <a href="<?php echo htmlspecialchars($material['material_path']); ?>" target="_blank">
                                                            <?php echo htmlspecialchars($material['material_name']); ?>
                                                        </a>
                                                    </label>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="no-materials">No materials in this folder.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-folders">No folders available.</div>
                    <?php endif; ?>
                </div>
        
        <div class="button-group">
            <button id="triggerFileInput" class="add-material-btn" title="Dodaj materiał">
                <i class="fas fa-plus-circle"></i>
                <span class="btn-text">Add Material</span>
            </button>

            <form method="POST" action="generate.php" class="add-folder-form">
                <button type="submit" class="add-folder-btn" title="Dodaj folder">
                    <i class="fas fa-folder"></i>
                    <span class="btn-text">Add Folder</span>
                </button>
            </form>

            <form method="POST" action="generate.php" class="remove-item-form">
                <input type="hidden" name="deleteID" id="deleteID">
                <input type="hidden" name="deleteType" id="deleteType">
                <button type="submit" class="remove-item-btn" title="Usuń">
                    <i class="fas fa-trash-alt"></i>
                    <span class="btn-text">Move to Trash</span>
                </button>
            </form>

            <form method="POST" action="#" id="generateResponseForm">
                <button type="button" id="generateResponse" class="generate-response-btn" title="Wygeneruj streszczenie">
                    <i class="fas fa-magic"></i>
                    <span class="btn-text">Generate Response</span>
                </button>
            </form>
        </div>

              
            </div>
            <div class="right-section">
                <h2>Response</h2>

                <div id="summaryResult" class="hidden">
                    <h2>Generated Summary</h2>
                    <p id="summaryText"></p>
                </div>
                <h2>Add Notes</h2>
                <textarea id="notesInput" placeholder="Write your notes here..."></textarea>
                <button id="sendNote" class="generate-response-btn">
                    <i class="fas fa-paper-plane"></i> Send Note
                </button>
            </div>
        </div>
    </div>

    <div class="background-image"></div>

</div>

</body>
</html>


