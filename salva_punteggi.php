<?php
header('Content-Type: application/json; charset=utf-8');

// Accetta solo richieste POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metodo non consentito']);
    exit;
}

// Legge il corpo della richiesta JSON inviata da JavaScript
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Verifica che i dati ricevuti siano un array valido
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Dati JSON non validi']);
    exit;
}

// Salva i dati formattati nel file punteggi.json
$file = __DIR__ . '/punteggi.json';
$jsonFormatted = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (file_put_contents($file, $jsonFormatted, LOCK_EX) !== false) {
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Impossibile scrivere su punteggi.json. Controlla i permessi del file.']);
}
?>