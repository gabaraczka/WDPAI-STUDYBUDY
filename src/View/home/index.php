<!DOCTYPE html>
<?php
    $isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    $email = $isLoggedIn ? $_SESSION['email'] ?? '' : '';
?>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page - StudyBuddy</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/navbar.css">
    <link rel="stylesheet" href="/assets/css/styles.css">

    <script src="/assets/js/script.js" defer></script>
</head>
<body>
    <div class="navbar">
        <div class="nav-logo">
            <img src="/assets/images/logo.png" alt="Study Buddy">
        </div>
        <div class="nav-menu" id="nav-menu">
            <div class="nav-links">
                <a href="/" class="active">Home</a>
                <a href="/generate">Create Summary</a>
                <a href="/study-cards">Study Cards</a>
            </div>
            <div>
                <?php if ($isLoggedIn): ?>
                    <span>Witaj, <?php echo htmlspecialchars($email); ?></span>
                    <a href="/logout" class="login-btn">Wyloguj się</a>
                <?php else: ?>
                    <a href="/login" class="login-btn">Log in</a>
                    <a href="/register" class="signup-btn">Sign up</a> 
                <?php endif; ?>
            </div>
        </div>
        <button class="hamburger" id="hamburger">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <div class="container">
        <div class="text-box">
            <h1 class="header-text">
                <span class="highlight-border">Your Intelligent</span>
                     <br>
                <span class="highlight">Study</span> Assistant
                <span class="small">Buddy</span>
            </h1>

            <p>AI Study Buddy is an innovative learning companion designed to help students study smarter, not harder.</p>
            <p>It generates quizzes, summaries based on your study materials and answers questions in real-time, making learning more efficient and engaging.</p>

            <a href="/generate" class="generate-btn">Generate summary</a>

        </div>

        <div class="image-box">
            <div class="circles">
                <div class="circle circle1"></div>
                <div class="circle circle2"></div>
                <div class="circle circle3"></div>
                <div class="circle circle4"></div> 
                <div class="circle circle5"></div> 
                <div class="circle circle6"></div> 
            </div>
            <img src="/assets/images/image-WOMAN.png" alt="Study Assistant">
            <div class="vector">
                <div class="boost-text">🚀 Boost your learning experience with AI-powered assistance!</div>
            </div> 
        </div>
    </div>

    <script>
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('nav-menu');

        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    </script>
</body>
</html> 