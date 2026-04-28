<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ── Database ──────────────────────────────────────────────────
$host     = "192.168.60.144";
$user     = "filippo_barchi";
$password = "inviterei.fregatura.";
$dbname   = "filippo_barchi_DashboardRobot";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connessione al database fallita: " . $conn->connect_error);
}

// ── ThingsBoard Cloud ─────────────────────────────────────────
define('TB_URL',      'https://eu.thingsboard.cloud');
define('TB_USERNAME', 'tua_email@example.com');   // ← sostituisci con la tua email ThingsBoard
define('TB_PASSWORD', 'tua_password');             // ← sostituisci con la tua password ThingsBoard
