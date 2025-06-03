<?php
session_start();

$isLoggedIn = isset($_SESSION['userID']);
$email = $_SESSION['email'] ?? null;
$userID = $_SESSION['userID'] ?? null;

$folders = [];
$studycards = [];

if ($isLoggedIn) {
    try {
        $conn = new PDO("pgsql:host=db;dbname=db", "docker", "docker");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $conn->exec("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");

        $stmt = $conn->prepare("SELECT folderid, folder_name FROM folders WHERE userid = :userID");
        $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
        $stmt->execute();
        $folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($folders)) {
            $firstFolderID = $folders[0]['folderid'];
            $stmt = $conn->prepare("SELECT title, content, back_content FROM studycards WHERE folderid = :folderID");
            $stmt->bindParam(':folderID', $firstFolderID, PDO::PARAM_INT);
            $stmt->execute();
            $studycards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        die("❌ Błąd połączenia z bazą danych: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Cards</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="navbar.css">

    <link rel="stylesheet" href="styles-study-cards.css">

    <script src="script_study_cards.js" defer></script>
    <style>
        .btn-reverse {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 18px;
            line-height: 1;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body style="background: url('image.png') no-repeat center center/cover;">
    <div class="navbar">
        <div class="nav-logo">
            <img src="logo.png" alt="Study Buddy">
        </div>
        <div class="nav-menu" id="nav-menu">
            <div class="nav-links">
                <a href="home-page.php">Home</a>
                <a href="generate.php">Create Summary</a>
                <a href="study-cards.php" class="active">Study Cards</a>
            </div>
            <div>
                <?php if ($isLoggedIn): ?>
                    <span>Witaj, <?php echo htmlspecialchars($email); ?></span>
                    <a href="logout.php" class="login-btn" >Wyloguj się</a>
                <?php else: ?>
                    <a href="login.html" class="login-btn">Log in</a>
                    <a href="register.html" class="signup-btn">Sign up</a> 
                <?php endif; ?>
            </div>
        </div>
        <button class="hamburger" id="hamburger">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
    <div class="study-cards-container">
        <div class="sidebar">
            <h2>Folders</h2>
            <ul>
                <?php if (!empty($folders)): ?>
                    <?php foreach ($folders as $index => $folder): ?>
                        <li 
                            class="<?php echo $index === 0 ? 'active' : ''; ?>" 
                            data-folderid="<?php echo $folder['folderid']; ?>">
                            <?php echo htmlspecialchars($folder['folder_name']); ?>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>No folders available</li>
                <?php endif; ?>
            </ul>

            <?php if (!empty($folders)): ?>
                <button class="btn-next" id="generateBtn" data-folderid="<?php echo $folders[0]['folderid']; ?>">
                    Generate Cards
                </button>
            <?php if (!empty($folders)): ?>
                 <button class="btn-delete" id="deleteBtn" data-folderid="<?php echo $folders[0]['folderid']; ?>">
                      Delete Cards
                 </button>
<?php endif; ?>

            <?php endif; ?>
        </div>

        <div class="study-card">
            <?php if (!empty($studycards)): ?>
                <p><strong id="card-title"><?php echo htmlspecialchars($studycards[0]['title']); ?></strong></p>
                <p id="card-content"><?php echo nl2br(htmlspecialchars($studycards[0]['content'])); ?></p>
                <p id="card-back" style="display:none;">"><?php echo nl2br(htmlspecialchars($studycards[0]['back_content'])); ?></p>
            <?php else: ?>
                <p><strong id="card-title">Brak fiszek</strong></p>
                <p id="card-content"></p>
                <p id="card-back" style="display:none;"></p>
            <?php endif; ?>
        </div>

        <div class="buttons">
            <button class="btn-prev">Previous</button>
            <button class="btn-reverse" id="reverseBtn" title="Reverse"><i class="fas fa-sync-alt"></i></button>
            <button class="btn-next">Next</button>
        </div>
    </div>
</body>
</html>
