<?php
session_start();
header('Content-Type: application/json');

class FolderCreator
{
    private PDO $conn;
    private int $userID;
    private string $folderName;

    public function __construct()
    {
        if (!isset($_SESSION['userID'])) {
            $this->sendError('Nie jesteś zalogowany.');
        }

        $this->userID = $_SESSION['userID'];
        $this->connectToDatabase();
        $this->parseInput();
    }

    private function connectToDatabase(): void
    {
        try {
            $this->conn = new PDO("pgsql:host=db;dbname=db", "docker", "docker");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->sendError('Błąd połączenia z bazą danych: ' . $e->getMessage());
        }
    }

    private function parseInput(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $this->folderName = trim($data['folderName'] ?? '');

        if ($this->folderName === '') {
            $this->sendError('Nazwa folderu jest wymagana.');
        }
    }

    public function createFolder(): void
    {
        try {
            $stmt = $this->conn->prepare("INSERT INTO folders (userid, folder_name) VALUES (:userID, :folderName)");
            $stmt->bindParam(':userID', $this->userID, PDO::PARAM_INT);
            $stmt->bindParam(':folderName', $this->folderName, PDO::PARAM_STR);
            $stmt->execute();

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            $this->sendError('Błąd bazy danych: ' . $e->getMessage());
        }
    }

    private function sendError(string $message): void
    {
        echo json_encode(['error' => $message]);
        exit;
    }
}

$folderCreator = new FolderCreator();
$folderCreator->createFolder();
