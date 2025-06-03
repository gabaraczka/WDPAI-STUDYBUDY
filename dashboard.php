<?php
session_start();
if (!isset($_SESSION['userID'])) {
    header("Location: login.html");
    exit();
}

echo "<h1>Witaj, " . $_SESSION['email'] . "!</h1>";
echo "<p><a href='logout.php'>Wyloguj się</a></p>";
?>
