<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['userID']) || !isset($_GET['folderid'])) {
    echo json_encode(['success' => false, 'message' => 'Brak dostępu']);
    exit;
}

$userID = $_SESSION['userID'];
$folderID = intval($_GET['folderid']);

try {
    $conn = new PDO("pgsql:host=db;dbname=db", "docker", "docker");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT 1 FROM folders WHERE folderid = :folderID AND userid = :userID");
    $stmt->execute([
        ':folderID' => $folderID,
        ':userID' => $userID
    ]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Nie masz dostępu do tego folderu.']);
        exit;
    }

    $delete = $conn->prepare("DELETE FROM studycards WHERE folderid = :folderID");
    $delete->execute([':folderID' => $folderID]);

    echo json_encode(['success' => true, 'message' => 'Wszystkie fiszki zostały usunięte.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
