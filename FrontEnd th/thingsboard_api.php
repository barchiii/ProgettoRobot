<?php
ob_start();

// Avvia la sessione di php
session_start();

// Verifica se l'utente è autenticato, altrimenti restituisce un errore 401
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Pulisce il buffer
    ob_end_clean();
    // Restituisce un errore 401 con un messaggio in formato JSON
    http_response_code(401);
    // Dice al browser che la risposta è in formato JSON
    header('Content-Type: application/json');
    // Restituisce un messaggio di errore in formato JSON
    echo json_encode(['status' => 'error', 'message' => 'Non autenticato']);
    exit;
}

// Dice al browser che la risposta è in formato JSON
header('Content-Type: application/json');

require 'config.php';

function tb_curl(string $url, ?string $jwt = null, string $method = 'GET', ?string $body = null): array
{
    //crea una sessione cURL
    $ch = curl_init($url);
    // Dichiara che l'invio e la ricezione dei dati avverrà solo in formato JSON
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    // Se è presente un token lo aggiorna nell'header della richiesta
    if ($jwt) {
        $headers[] = "X-Authorization: Bearer $jwt";
    }
    // Configura la sessione cURL
    curl_setopt_array($ch, [
        // Restituisce la risposta come stringa invece di stamparla direttamente
        CURLOPT_RETURNTRANSFER => true,
        // Imposta gli header della richiesta
        CURLOPT_HTTPHEADER => $headers,
        // Verifica il certificato SSL
        CURLOPT_SSL_VERIFYPEER => true,
        // Imposta un timeout la richiesta
        CURLOPT_TIMEOUT => 10,
    ]);

    // Se il metodo è POST, aggiunge il corpo della richiesta

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body ?? '{}');
    }
    // Raccoglie le informazioni della richiesta cURL
    // Contenuto della risposta, codice HTTP(200,401 e 500) e eventuali errori di cURL
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);

    // Se è presente un errore di cURL, restituisce un array con il codice 0 e l'errore
    // altrimenti restituisce un array con il codice HTTP e i dati decodificati in formato JSON
    if ($curlErr) {
        return ['code' => 0, 'data' => null, 'error' => $curlErr];
    }
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}


function get_tb_jwt(): ?string
{
    // Controlla se è presente un token JWT valido in sessione, se sì lo restituisce
    if (
        !empty($_SESSION['tb_jwt']) &&
        !empty($_SESSION['tb_jwt_expiry']) &&
        $_SESSION['tb_jwt_expiry'] > time()
    ) {
        return $_SESSION['tb_jwt'];
    }

    // Effettua il login a ThingsBoard Cloud 
    $resp = tb_curl(
        TB_URL . '/api/auth/login',
        null,
        'POST',
        json_encode(['username' => TB_USERNAME, 'password' => TB_PASSWORD])
    );

    // Se il login è avvenuto con successo, salva il token JWT e la sua scadenza in sessione, altrimenti restituisce null
    if ($resp['code'] === 200 && !empty($resp['data']['token'])) {
        $_SESSION['tb_jwt'] = $resp['data']['token'];
        $_SESSION['tb_jwt_expiry'] = time() + 8000;
        return $_SESSION['tb_jwt'];
    }

    return null;
}


function get_device_ids(string $jwt): array
{
    // Controlla se sono già presenti gli ID dei device, se ci sono li restituisce
    if (!empty($_SESSION['tb_device_ids'])) {
        return $_SESSION['tb_device_ids'];
    }

    // Effettua una richiesta a ThingsBoard Cloud per ottenere la lista dei device e i loro ID
    // poi salva in sessione gli ID dei device con i nomi attesi (Primo Robot, Secondo Robot, Terzo Robot) e li restituisce come array
    // altrimenti restituisce un array vuoto
    $resp = tb_curl(TB_URL . '/api/tenant/devices?pageSize=50&page=0', $jwt);
    $ids  = [];

    if ($resp['code'] === 200 && !empty($resp['data']['data'])) {
        foreach ($resp['data']['data'] as $device) {
            $name = $device['name']     ?? '';
            $id   = $device['id']['id'] ?? '';

            if ($name === 'Primo Robot')        $ids['tempo'] = $id;
            elseif ($name === 'Secondo Robot')  $ids['rilevazione'] = $id;
            elseif ($name === 'Terzo Robot')    $ids['colore'] = $id;
        }
    }

    // Se ha trovato un ID lo salva in sessione altrimenti restituisce un array vuoto
    if (!empty($ids)) {
        $_SESSION['tb_device_ids'] = $ids;
    }

    return $ids;
}

// Funzione per ottenere gli ultimi dati di telemetria di un device specifico in base al suo ID e alla sua telemetria
function get_telemetry(string $jwt, string $deviceId, string $keys): ?array
{
    $resp = tb_curl(
        TB_URL . "/api/plugins/telemetry/DEVICE/{$deviceId}/values/timeseries?keys={$keys}",
        $jwt
    );
    return $resp['code'] === 200 ? $resp['data'] : null;
}

// Rimuove gli id già presenti
unset($_SESSION['tb_device_ids']);

// Ottiene un token JWT valido per autenticarsi a ThingsBoard Cloud, se non riesce restituisce un messaggio di errore in formato JSON
$jwt = get_tb_jwt();
if (!$jwt) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Login ThingsBoard fallito.'
    ]);
    exit;
}

// Ottiene gli ID dei device, se non riesce restituisce un messaggio di errore in formato JSON
$ids = get_device_ids($jwt);
if (empty($ids)) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Nessun device trovato.'
    ]);
    exit;
}

// Array in formato json per il risultato finale
$result = ['status' => 'ok', 'devices' => []];

// Salva i dati del primo device
$result['devices']['tempo'] = isset($ids['tempo'])
    ? get_telemetry($jwt, $ids['tempo'], 'time')
    : null;

// Salva i dati del secondo device
$result['devices']['rilevazione'] = isset($ids['rilevazione'])
    ? get_telemetry($jwt, $ids['rilevazione'], 'infrared_sensor_event')
    : null;

// Salva i dati del terzo device
$result['devices']['colore'] = isset($ids['colore'])
    ? get_telemetry($jwt, $ids['colore'], 'color_sensor_event')
    : null;

// Pulisce il buffer 
ob_end_clean();
// Restituisce il risultato in formato JSON
echo json_encode($result);