<!-- filepath: c:\Users\gabri\Downloads\LAB01-KONFIGURACJA-20250302T072115Z-001\LAB01-KONFIGURACJA\study-cards.php -->
<?php
session_start();

$isLoggedIn = isset($_SESSION['userID']);
$email = $_SESSION['email'] ?? null;
$userID = $_SESSION['userID'] ?? null;

$folders = [];

if ($isLoggedIn) {
    // Database connection
    try {
        $conn = new PDO("pgsql:host=db;dbname=db", "docker", "docker");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch folders for the logged-in user
        $stmt = $conn->prepare("SELECT folderid, folder_name FROM folders WHERE userid = :userID");
        $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
        $stmt->execute();
        $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Study Cards</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Plik CSS -->
    <link rel="stylesheet" href="styles_generate.css">

    <!-- Skrypt JS -->
    <script src="script_generate.js" defer></script>
</head>
<body>
    <div class="navbar">
        <div class="nav-logo">
            <img src="logo.png" alt="Study Buddy">
        </div>
        <div class="nav-links">
            <a href="home-page.php">Home</a>
            <a href="generate.php">Create Summary</a>
            <a href="study-cards.php" class="active">Study Cards</a>
        </div>
        <div>
            <?php if ($isLoggedIn): ?>
                <span>Witaj, <?php echo htmlspecialchars($email); ?></span>
                <a href="logout.php" class="login-btn">Wyloguj się</a>
            <?php else: ?>
                <a href="login.html" class="login-btn">Log in</a>
                <a href="register.html" class="signup-btn">Sign up</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <div class="white-block">
            <div class="left-section">
                <h1>Your Folders</h1>
                <div class="file-list" id="fileList">
                    <?php if (!empty($folders)): ?>
                        <?php foreach ($folders as $folder): ?>
                            <div class="folder-item">
                                <div class="folder-header">
                                    <i class="fas fa-folder"></i> <!-- Folder icon -->
                                    <label class="folder-label">
                                        <?php echo htmlspecialchars($folder['folder_name']); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-folders">No folders available.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="right-section">
                <h2>Study Cards</h2>
                <p>Select a folder to view or create study cards.</p>
            </div>
        </div>
    </div>

    <div class="background-image"></div>
</body>
</html>