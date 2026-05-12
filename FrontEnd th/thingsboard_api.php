<?php
/**
 * thingsboard_api.php
 * Proxy server-side per leggere i dati dai device ThingsBoard.
 * Viene chiamato via fetch() dal JavaScript in index.php.
 */

ob_start();

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ob_end_clean();
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Non autenticato']);
    exit;
}

header('Content-Type: application/json');

require 'config.php';

// ── Helper: esegue una richiesta HTTP con cURL ────────────────
function tb_curl(string $url, ?string $jwt = null, string $method = 'GET', ?string $body = null): array
{
    $ch      = curl_init($url);
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

    if ($curlErr) {
        return ['code' => 0, 'data' => null, 'error' => $curlErr];
    }
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

// ── 1. Ottieni (o rinnova) il JWT ThingsBoard ─────────────────
function get_tb_jwt(): ?string
{
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

            if ($name === 'Primo Robot')        $ids['tempo']       = $id;
            elseif ($name === 'Secondo Robot')  $ids['rilevazione'] = $id;
            elseif ($name === 'Terzo Robot')    $ids['colore']      = $id;
        }
    }

    if (!empty($ids)) {
        $_SESSION['tb_device_ids'] = $ids;
    }

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

// ── Esecuzione principale ─────────────────────────────────────

unset($_SESSION['tb_device_ids']);

$jwt = get_tb_jwt();
if (!$jwt) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Login ThingsBoard fallito. Controlla le credenziali in config.php'
    ]);
    exit;
}

$ids = get_device_ids($jwt);

if (empty($ids)) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Nessun device trovato. Nomi attesi: "Primo Robot", "Secondo Robot", "Terzo Robot"'
    ]);
    exit;
}

$result = ['status' => 'ok', 'devices' => []];

// Primo Robot  → chiave: time
$result['devices']['tempo'] = isset($ids['tempo'])
    ? get_telemetry($jwt, $ids['tempo'], 'time')
    : null;

// Secondo Robot → chiave: infrared_sensor_event
$result['devices']['rilevazione'] = isset($ids['rilevazione'])
    ? get_telemetry($jwt, $ids['rilevazione'], 'infrared_sensor_event')
    : null;

// Terzo Robot → chiave: color_sensor_event
$result['devices']['colore'] = isset($ids['colore'])
    ? get_telemetry($jwt, $ids['colore'], 'color_sensor_event')
    : null;

ob_end_clean();
echo json_encode($result);