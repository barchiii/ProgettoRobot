<?php
/**
 * thingsboard_api.php
 * Proxy server-side per leggere i dati dai device ThingsBoard.
 * Viene chiamato via fetch() dal JavaScript in index.php.
 */

session_start();

// Solo utenti loggati possono chiamare questo endpoint
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autenticato']);
    exit;
}

require 'config.php';

header('Content-Type: application/json');

// ── Helper: esegue una richiesta HTTP con cURL ────────────────
function tb_curl(string $url, ?string $jwt = null, string $method = 'GET', ?string $body = null): array
{
    $ch = curl_init($url);
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($jwt) {
        $headers[] = "X-Authorization: Bearer $jwt";
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST,       true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body ?? '{}');
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['code' => 0, 'data' => null, 'error' => $curlErr];
    }
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

// ── 1. Ottieni (o rinnova) il JWT ThingsBoard ─────────────────
function get_tb_jwt(): ?string
{
    // Usa il token in sessione se ancora valido (scade dopo ~8000 s)
    if (
        !empty($_SESSION['tb_jwt']) &&
        !empty($_SESSION['tb_jwt_expiry']) &&
        $_SESSION['tb_jwt_expiry'] > time()
    ) {
        return $_SESSION['tb_jwt'];
    }

    $resp = tb_curl(
        TB_URL . '/api/auth/login',
        null,
        'POST',
        json_encode(['username' => TB_USERNAME, 'password' => TB_PASSWORD])
    );

    if ($resp['code'] === 200 && !empty($resp['data']['token'])) {
        $_SESSION['tb_jwt']        = $resp['data']['token'];
        $_SESSION['tb_jwt_expiry'] = time() + 8000;
        return $_SESSION['tb_jwt'];
    }

    return null;
}

// ── 2. Ottieni gli ID dei device (con cache in sessione) ──────
function get_device_ids(string $jwt): array
{
    if (!empty($_SESSION['tb_device_ids'])) {
        return $_SESSION['tb_device_ids'];
    }

    $resp = tb_curl(TB_URL . '/api/tenant/devices?pageSize=50&page=0', $jwt);
    $ids  = [];

    if ($resp['code'] === 200 && !empty($resp['data']['data'])) {
        foreach ($resp['data']['data'] as $device) {
            $name = $device['name']     ?? '';
            $id   = $device['id']['id'] ?? '';
            if ($name === 'Tempo di Esecuzione') $ids['tempo']       = $id;
            elseif ($name === 'Rilevazione')     $ids['rilevazione'] = $id;
            elseif ($name === 'Colore')           $ids['colore']      = $id;
        }
    }

    $_SESSION['tb_device_ids'] = $ids;
    return $ids;
}

// ── 3. Leggi ultimo valore telemetria per un device ───────────
function get_telemetry(string $jwt, string $deviceId, string $keys): ?array
{
    $resp = tb_curl(
        TB_URL . "/api/plugins/telemetry/DEVICE/{$deviceId}/values/timeseries?keys={$keys}",
        $jwt
    );
    return $resp['code'] === 200 ? $resp['data'] : null;
}

// ── Esecuzione ────────────────────────────────────────────────
$jwt = get_tb_jwt();
if (!$jwt) {
    echo json_encode(['status' => 'error', 'message' => 'Login ThingsBoard fallito. Controlla le credenziali in config.php']);
    exit;
}

$ids = get_device_ids($jwt);

$result = ['status' => 'ok', 'devices' => []];

// Primo Robot  → Tempo di Esecuzione → chiave: time
$result['devices']['tempo'] = isset($ids['tempo'])
    ? get_telemetry($jwt, $ids['tempo'], 'time')
    : null;

// Secondo Robot → Rilevazione → chiavi: infrared_sensor_event, time
$result['devices']['rilevazione'] = isset($ids['rilevazione'])
    ? get_telemetry($jwt, $ids['rilevazione'], 'infrared_sensor_event,time')
    : null;

// Terzo Robot  → Colore → chiavi: color_sensor_event, time
$result['devices']['colore'] = isset($ids['colore'])
    ? get_telemetry($jwt, $ids['colore'], 'color_sensor_event,time')
    : null;

echo json_encode($result);
