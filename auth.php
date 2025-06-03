<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'db.php';

// Możesz wyłączyć te dwie linijki na produkcji
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

class AuthHandler
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError("❌ Nieprawidłowe żądanie!");
        }

        $action = $_POST['action'] ?? '';

        match ($action) {
            'register' => $this->register(),
            'login' => $this->login(),
            default => $this->sendError("❌ Nieprawidłowa akcja!")
        };
    }

    private function register(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        if ($password !== $confirm_password) {
            $this->sendError("❌ Hasła nie są identyczne!");
        }

        try {
            $stmt = $this->conn->prepare("
                INSERT INTO users (login, password_hash, email)
                SELECT ?, ?, ?
                WHERE NOT EXISTS (
                    SELECT 1 FROM users WHERE email = ?
                )
                RETURNING userid;
            ");

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([$email, $password_hash, $email, $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION['userID'] = $user['userid'];
                $_SESSION['email'] = $email;
                $this->sendSuccess("✅ Rejestracja zakończona sukcesem!");
            } else {
                $this->sendError("❌ Użytkownik już istnieje!");
            }
        } catch (PDOException $e) {
            $this->sendError("❌ Błąd bazy danych: " . $e->getMessage());
        }
    }

    private function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        try {
            $stmt = $this->conn->prepare("SELECT userid, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $this->sendError("❌ Błędny e-mail lub hasło!");
            }

            $_SESSION['userID'] = $user['userid'];
            $_SESSION['email'] = $email;

            $this->sendSuccess("✅ Zalogowano pomyślnie!");
        } catch (PDOException $e) {
            $this->sendError("❌ Błąd bazy danych: " . $e->getMessage());
        }
    }

    private function sendError(string $message): void
    {
        echo json_encode(['error' => $message]);
        exit;
    }

    private function sendSuccess(string $message): void
    {
        echo json_encode(['success' => $message]);
        exit;
    }
}

$auth = new AuthHandler($conn);
$auth->handleRequest();
