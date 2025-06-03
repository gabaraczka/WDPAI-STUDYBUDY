<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log errors
function logError($message) {
    $logFile = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

$maxRetries = 3;
$retryDelay = 2; // seconds

for ($i = 0; $i < $maxRetries; $i++) {
    try {
        // Log connection attempt
        logError("Attempting database connection (attempt " . ($i + 1) . " of $maxRetries)");
        
        $conn = new PDO(
            "pgsql:host=db;port=5432;dbname=db;user=docker;password=docker",
            "docker",
            "docker",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Log successful connection
        logError("Database connection successful");
        break;
    } catch (PDOException $e) {
        logError("Connection attempt " . ($i + 1) . " failed: " . $e->getMessage());
        
        if ($i === $maxRetries - 1) {
            // Log final failure
            logError("All connection attempts failed. Last error: " . $e->getMessage());
            echo json_encode(['error' => 'Błąd połączenia z bazą danych']);
            exit;
        }
        sleep($retryDelay);
    }
}
