<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $conn = new PDO("pgsql:host=db;dbname=db", "docker", "docker");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Błąd połączenia z bazą danych']);
    exit;
}
