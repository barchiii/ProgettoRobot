<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Credenziali per la connessione al database
$host     = "localhost";
$user     = "root";
$password = "";
$dbname   = "login";

// Connessione al database
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connessione al database fallita: " . $conn->connect_error);
}

// Credenziali per la connessione a ThingsBoard Cloud
define('TB_URL',      'https://eu.thingsboard.cloud');
define('TB_USERNAME', 'filippo.barchi@iisviolamarchesini.edu.it');
define('TB_PASSWORD', 'Barchi_Gaba_Vise');