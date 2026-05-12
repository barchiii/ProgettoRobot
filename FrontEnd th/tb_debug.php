<?php
/**
 * tb_debug.php
 * ─────────────────────────────────────────────────────────────────
 * File di DIAGNOSTICA per la connessione ThingsBoard.
 * Mettilo nella stessa cartella di thingsboard_api.php ed aprilo
 * dal browser una volta sola. CANCELLALO dopo aver risolto il problema.
 * ─────────────────────────────────────────────────────────────────
 */

ob_start();
session_start();
require 'config.php';
ob_end_clean();

header('Content-Type: text/html; charset=utf-8');

function ok(string $msg)  { echo "<p style='color:#00c853'>✅ $msg</p>"; }
function err(string $msg) { echo "<p style='color:#f44336'>❌ $msg</p>"; }
function info(string $msg){ echo "<p style='color:#888'>ℹ️ $msg</p>"; }
function dump(mixed $v)   { echo "<pre style='background:#111;color:#eee;padding:10px;border-radius:6px;font-size:13px'>" . htmlspecialchars(print_r($v, true)) . "</pre>"; }

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<title>ThingsBoard Debug</title>
<style>body{font-family:monospace;background:#1a1a1a;color:#eee;padding:30px;max-width:900px;margin:auto}h2{color:#ffd700}</style>
</head><body>";

echo "<h2>🔍 ThingsBoard — Diagnostica connessione</h2>";

// ─────────────────────────────────────────────────────────────────
// STEP 1 — Controlla le costanti di config.php
// ─────────────────────────────────────────────────────────────────
echo "<h2>STEP 1 — config.php</h2>";

$missingConst = false;
foreach (['TB_URL', 'TB_USERNAME', 'TB_PASSWORD'] as $c) {
    if (!defined($c)) {
        err("Costante <b>$c</b> NON definita in config.php");
        $missingConst = true;
    } else {
        $val = constant($c);
        if ($c === 'TB_PASSWORD') {
            ok("$c = <b>" . str_repeat('*', strlen($val)) . "</b> (" . strlen($val) . " caratteri)");
        } else {
            ok("$c = <b>" . htmlspecialchars($val) . "</b>");
        }
    }
}

if ($missingConst) {
    err("Impossibile continuare senza le costanti. Correggi config.php.");
    echo "</body></html>"; exit;
}

$tbUrl = rtrim(TB_URL, '/');

// ─────────────────────────────────────────────────────────────────
// STEP 2 — Verifica che cURL sia disponibile
// ─────────────────────────────────────────────────────────────────
echo "<h2>STEP 2 — Estensione cURL PHP</h2>";
if (!function_exists('curl_init')) {
    err("cURL NON disponibile. Abilitalo in php.ini (extension=curl).");
    echo "</body></html>"; exit;
}
ok("cURL disponibile — versione: " . curl_version()['version']);

// ─────────────────────────────────────────────────────────────────
// STEP 3 — Raggiungibilità del server ThingsBoard
// ─────────────────────────────────────────────────────────────────
echo "<h2>STEP 3 — Raggiungibilità di TB_URL</h2>";
info("URL testato: <b>" . htmlspecialchars($tbUrl) . "</b>");

$ch = curl_init($tbUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOBODY         => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
]);
curl_exec($ch);
$reachCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$reachErr  = curl_error($ch);

if ($reachErr) {
    err("Server NON raggiungibile: <b>$reachErr</b>");
} elseif ($reachCode === 0) {
    err("Nessuna risposta HTTP (timeout o host sconosciuto).");
} else {
    ok("Server raggiungibile — HTTP $reachCode");
}

// ─────────────────────────────────────────────────────────────────
// STEP 4 — Login ThingsBoard (ottieni JWT)
// ─────────────────────────────────────────────────────────────────
echo "<h2>STEP 4 — Login ThingsBoard (JWT)</h2>";

$loginUrl  = $tbUrl . '/api/auth/login';
$loginBody = json_encode(['username' => TB_USERNAME, 'password' => TB_PASSWORD]);
info("POST → " . htmlspecialchars($loginUrl));

$ch = curl_init($loginUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $loginBody,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 10,
]);
$loginResp = curl_exec($ch);
$loginCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$loginErr  = curl_error($ch);

if ($loginErr) {
    err("cURL error durante il login: <b>$loginErr</b>");
    echo "</body></html>"; exit;
}

info("HTTP response code: <b>$loginCode</b>");
$loginData = json_decode($loginResp, true);

if ($loginCode === 200 && !empty($loginData['token'])) {
    $jwt = $loginData['token'];
    ok("Login riuscito — JWT ottenuto (" . strlen($jwt) . " caratteri)");
} elseif ($loginCode === 401) {
    err("Login FALLITO (401) — Username o password errati in config.php.");
    dump($loginData ?? $loginResp);
    echo "</body></html>"; exit;
} else {
    err("Login FALLITO — HTTP $loginCode");
    dump($loginData ?? $loginResp);
    echo "</body></html>"; exit;
}

// ─────────────────────────────────────────────────────────────────
// STEP 5 — Lista device
// ─────────────────────────────────────────────────────────────────
echo "<h2>STEP 5 — Lista device ThingsBoard</h2>";

$devUrl = $tbUrl . '/api/tenant/devices?pageSize=50&page=0';
info("GET → " . htmlspecialchars($devUrl));

$ch = curl_init($devUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        "X-Authorization: Bearer $jwt",
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 10,
]);
$devResp = curl_exec($ch);
$devCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$devErr  = curl_error($ch);

if ($devErr) {
    err("cURL error lista device: <b>$devErr</b>");
    echo "</body></html>"; exit;
}

info("HTTP response code: <b>$devCode</b>");
$devData = json_decode($devResp, true);

if ($devCode !== 200) {
    err("Impossibile ottenere lista device — HTTP $devCode");
    dump($devData ?? $devResp);
    echo "</body></html>"; exit;
}

$devices     = $devData['data'] ?? [];
$targetNames = ['Primo Robot', 'Secondo Robot', 'Terzo Robot'];
$foundIds    = [];

ok("Trovati <b>" . count($devices) . "</b> device nel tenant");
info("Nomi device trovati su ThingsBoard:");
echo "<ul style='color:#ccc'>";
foreach ($devices as $d) {
    $dName = $d['name'] ?? '(senza nome)';
    $dId   = $d['id']['id'] ?? '?';
    $match = in_array($dName, $targetNames) ? " ← <span style='color:#00fa9a'>MATCH</span>" : "";
    echo "<li><b>" . htmlspecialchars($dName) . "</b> — ID: $dId$match</li>";
    if (in_array($dName, $targetNames)) $foundIds[$dName] = $dId;
}
echo "</ul>";

foreach ($targetNames as $tName) {
    if (isset($foundIds[$tName])) {
        ok("Device trovato: <b>\"$tName\"</b>");
    } else {
        err("Device NON trovato: <b>\"$tName\"</b>");
    }
}

// ─────────────────────────────────────────────────────────────────
// STEP 6 — Test telemetria per ogni device trovato
// ─────────────────────────────────────────────────────────────────
echo "<h2>STEP 6 — Telemetria device</h2>";

$keyMap = [
    'Primo Robot'   => 'time',
    'Secondo Robot' => 'infrared_sensor_event',
    'Terzo Robot'   => 'color_sensor_event',
];

foreach ($foundIds as $name => $id) {
    $keys   = $keyMap[$name] ?? 'time';
    $telUrl = $tbUrl . "/api/plugins/telemetry/DEVICE/{$id}/values/timeseries?keys={$keys}";
    info("GET telemetria \"$name\" → " . htmlspecialchars($telUrl));

    $ch = curl_init($telUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            "X-Authorization: Bearer $jwt",
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $telResp = curl_exec($ch);
    $telCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $telErr  = curl_error($ch);

    if ($telErr) {
        err("cURL error telemetria \"$name\": <b>$telErr</b>");
        continue;
    }

    $telData = json_decode($telResp, true);

    if ($telCode === 200) {
        ok("Telemetria \"<b>$name</b>\" — HTTP $telCode");
        if (empty($telData)) {
            info("⚠️ Nessun dato disponibile (chiave: <b>$keys</b>) — il robot non ha ancora inviato telemetria.");
        } else {
            dump($telData);
        }
    } else {
        err("Telemetria \"$name\" — HTTP $telCode");
        dump($telData ?? $telResp);
    }
}

echo "<hr style='border-color:#333;margin-top:30px'>
<p style='color:#555;font-size:12px'>⚠️ Ricorda di eliminare <b>tb_debug.php</b> dal server dopo aver risolto il problema.</p>
</body></html>";