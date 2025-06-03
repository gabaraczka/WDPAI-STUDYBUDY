<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID']) || !isset($_GET['folderid'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Brak dostępu']);
    exit;
}

$userID = $_SESSION['userID'];
$folderID = intval($_GET['folderid']);

try {
    $conn = new PDO("pgsql:host=db;dbname=db", "docker", "docker");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $checkStmt = $conn->prepare("SELECT 1 FROM folders WHERE folderid = :folderid AND userid = :userid");
    $checkStmt->execute([
        ':folderid' => $folderID,
        ':userid' => $userID
    ]);

    if ($checkStmt->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Folder nie należy do użytkownika']);
        exit;
    }

    $stmt = $conn->prepare("SELECT title, content, back_content FROM studycards WHERE folderid = :folderid");
    $stmt->bindParam(':folderid', $folderID, PDO::PARAM_INT);
    $stmt->execute();
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($cards);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd bazy danych']);
}
