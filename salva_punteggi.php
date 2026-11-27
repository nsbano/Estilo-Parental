<?php
// IMPOSTA QUI LA TUA PASSWORD SEGRETA PER SCARICARE IL JSON
$ADMIN_PASSWORD = 'Humana2811!';
$file = __DIR__ . '/punteggi.json';

// 1. DOWNLOAD RISERVATO SOLO ALL'AMMINISTRATORE (GET ?download=1&key=...)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['download'])) {
    $providedKey = $_GET['key'] ?? '';

    if (!hash_equals($ADMIN_PASSWORD, $providedKey)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Accesso negato: password non valida.';
        exit;
    }

    if (!file_exists($file)) {
        file_put_contents($file, "[]", LOCK_EX);
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="punteggi.json"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

// 2. SALVATAGGIO DI UN SINGOLO NUOVO PARTECIPANTE (POST)
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metodo non consentito']);
    exit;
}

$rawData = file_get_contents('php://input');
$newRecord = json_decode($rawData, true);

if (!is_array($newRecord) || empty($newRecord['name']) || empty($newRecord['email'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nome o email mancanti']);
    exit;
}

$email = filter_var(trim($newRecord['email']), FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email non valida']);
    exit;
}

$entry = [
    'name'         => trim(strip_tags($newRecord['name'])),
    'email'        => strtolower($email),
    'score'        => isset($newRecord['score']) ? (int)$newRecord['score'] : 0,
    'profileTitle' => isset($newRecord['profileTitle']) ? trim(strip_tags($newRecord['profileTitle'])) : '',
    'date'         => isset($newRecord['date']) ? trim(strip_tags($newRecord['date'])) : date('d/m/Y')
];

// Legge i record esistenti senza esporli al client e accoda il nuovo risultato
$existing = [];
if (file_exists($file)) {
    $decoded = json_decode(file_get_contents($file), true);
    if (is_array($decoded)) {
        $existing = $decoded;
    }
}

$existing[] = $entry;
$jsonFormatted = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (file_put_contents($file, $jsonFormatted, LOCK_EX) !== false) {
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Errore di scrittura su punteggi.json']);
}
?>
