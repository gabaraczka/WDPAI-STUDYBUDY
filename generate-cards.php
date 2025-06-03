<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json');

class CardGenerator
{
    private PDO $conn;
    private string $apiKey;
    private int $folderID;
    private int $generatedCount = 0;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->connectToDatabase();
        $this->getFolderID();
    }

    private function connectToDatabase(): void
    {
        try {
            $this->conn = new PDO("pgsql:host=db;dbname=db", "docker", "docker");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->respond(false, 'Błąd połączenia z bazą danych: ' . $e->getMessage());
        }
    }

    private function getFolderID(): void
    {
        $this->folderID = isset($_GET['folderid']) ? intval($_GET['folderid']) : 0;
        if ($this->folderID <= 0) {
            $this->respond(false, 'Brak folderu');
        }
    }

    public function generateCards(): void
    {
        $materials = $this->fetchMaterials();

        if (empty($materials)) {
            $this->respond(false, 'Brak materiałów przypisanych do folderu.');
        }

        $insertStmt = $this->conn->prepare("
            INSERT INTO studycards (folderID, title, content, back_content)
            VALUES (:folderID, :title, :content, :back_content)
        ");

        $checkStmt = $this->conn->prepare("
            SELECT 1 FROM studycards
            WHERE folderid = :folderID AND content = :content AND back_content = :back_content
        ");

        foreach ($materials as $material) {
            $text = $this->extractText($material);
            if (!$text) continue;

            $cards = $this->callOpenAI($text);
            if (!is_array($cards)) continue;

            foreach ($cards as $idx => $card) {
                $q = trim($card['question'] ?? '');
                $a = trim($card['answer'] ?? '');
                if (!$q || !$a) continue;

                $checkStmt->execute([
                    ':folderID' => $this->folderID,
                    ':content' => $q,
                    ':back_content' => $a
                ]);

                if ($checkStmt->rowCount() === 0) {
                    $insertStmt->execute([
                        ':folderID' => $this->folderID,
                        ':title' => "Z: " . $material['material_name'] . " (#" . ($idx + 1) . ")",
                        ':content' => $q,
                        ':back_content' => $a
                    ]);
                    $this->generatedCount++;
                }
            }
        }

        if ($this->generatedCount === 0) {
            $this->respond(false, 'Brak nowych fiszek do wygenerowania.');
        } else {
            $this->respond(true, "Wygenerowano {$this->generatedCount} fiszek z pomocą AI.");
        }
    }

    private function fetchMaterials(): array
    {
        $stmt = $this->conn->prepare("
            SELECT materialID, material_name, material, material_path
            FROM materials
            WHERE folderID = :folderID
        ");
        $stmt->execute([':folderID' => $this->folderID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function extractText(array $material): string
    {
        $text = strip_tags(trim($material['material'] ?? ''));

        if (empty($text) && !empty($material['material_path'])) {
            $path = $material['material_path'];
            if (file_exists($path) && is_readable($path)) {
                $text = strip_tags(trim(file_get_contents($path)));
            }
        }

        return $text;
    }

    private function callOpenAI(string $text): ?array
    {
        $prompt = <<<EOT
Na podstawie poniższego tekstu wygeneruj maksymalnie 10 fiszek w formacie JSON, gdzie każda fiszka zawiera "question" i "answer".

Fiszki mają być edukacyjne, zrozumiałe i kontekstowe. Unikaj przepisania tekstu – przekształć go w pytania i krótkie odpowiedzi.

Zwróć wyłącznie poprawny JSON. możesz dodać swoją wiedzę, ale nie przepisuj tekstu.

MATERIAŁ:
\"\"\" 
$text
\"\"\"
EOT;

        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'Jesteś pomocnym nauczycielem, który tworzy fiszki edukacyjne.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);
        return json_decode($json['choices'][0]['message']['content'] ?? '', true);
    }

    private function respond(bool $success, string $message): void
    {
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }
}

$openai_api_key = "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";

$generator = new CardGenerator($openai_api_key);
$generator->generateCards();
