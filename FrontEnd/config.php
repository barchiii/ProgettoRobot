<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$host = "192.168.60.144";
$user = "filippo_barchi"; // Utente di default di XAMPP/MAMP
$password = "inviterei.fregatura."; // Password di default vuota su XAMPP
$dbname = "filippo_barchi_DashboardRobot";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connessione al database fallita: " . $conn->connect_error);
}
?>