<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wylogowano</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- STYLE -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="navbar">
        <div class="nav-logo">
            <img src="logo.png" alt="Study Buddy">
        </div>
        <div class="nav-menu" id="nav-menu">
            <div class="nav-links">
                <a href="home-page.php" class="active">Home</a>
                <a href="generate.php">Create Summary</a>
                <a href="study-cards.php">Study Cards</a>
            </div>
            <div>
                <a href="login.html" class="login-btn">Log in</a>
                <a href="register.html" class="signup-btn">Sign up</a>
            </div>
        </div>
        <button class="hamburger" id="hamburger">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <div class="login-container">
        <div class="login-box">
            <h1>👋 Wylogowano!</h1>
            <p style="font-size: 1.1rem; color: var(--highlight); margin-top: 15px;">
                Mamy nadzieję, że wkrótce wrócisz do nauki z Study Buddy 😊
            </p>
            <a href="login.html" class="generate-btn" style="margin-top: 30px;">Zaloguj się ponownie</a>
        </div>
    </div>
</body>
</html>
