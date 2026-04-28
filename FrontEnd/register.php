<?php
session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
header("Location: index.php");
exit;
}

require 'config.php';

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
$username = trim($_POST['username']);
$password = trim($_POST['password']);
$confirmPassword = trim($_POST['confirm_password']);

if (empty($username) || empty($password) || empty($confirmPassword)) {
$message = "Compila tutti i campi.";
$messageType = "danger";
} elseif ($password !== $confirmPassword) {
$message = "Le password non coincidono.";
$messageType = "danger";
} else {
$username = $conn->real_escape_string($username);

$checkSql = "SELECT id FROM utenti WHERE username = '$username'";
$checkResult = $conn->query($checkSql);

if ($checkResult && $checkResult->num_rows > 0) {
$message = "Username già esistente.";
$messageType = "danger";
} else {
$hashedPassword = md5($password);
$insertSql = "INSERT INTO utenti (username, password) VALUES ('$username', '$hashedPassword')";

if ($conn->query($insertSql) === TRUE) {
$message = "Registrazione completata con successo. Ora puoi accedere.";
$messageType = "success";
} else {
$message = "Errore durante la registrazione: " . $conn->error;
$messageType = "danger";
}
}
}
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrazione</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom py-3">
<div class="container-fluid">
<div class="ms-auto">
<button class="btn btn-custom rounded-circle p-2 px-3" onclick="toggleTheme()" title="Cambia Tema">
<i id="themeIcon" class="fa-solid fa-sun"></i>
</button>
</div>
</div>
</nav>

<div class="login-container">
<div class="custom-card text-center">
<h3 class="mb-4">Registrazione Utente</h3>

<?php if (!empty($message)): ?>
<div class="alert alert-<?php echo $messageType; ?>" role="alert">
<?php echo $message; ?>
</div>
<?php endif; ?>

<form method="POST" action="register.php">
<div class="mb-3 text-start">
<label for="username" class="form-label">Nome Utente</label>
<input
type="text"
class="form-control"
id="username"
name="username"
placeholder="Inserisci username"
required
style="background-color: var(--bg-color); color: var(--text-color); border: none;"
>
</div>

<div class="mb-3 text-start">
<label for="password" class="form-label">Password</label>
<input
type="password"
class="form-control"
id="password"
name="password"
placeholder="Inserisci password"
required
style="background-color: var(--bg-color); color: var(--text-color); border: none;"
>
</div>

<div class="mb-4 text-start">
<label for="confirm_password" class="form-label">Conferma Password</label>
<input
type="password"
class="form-control"
id="confirm_password"
name="confirm_password"
placeholder="Conferma password"
required
style="background-color: var(--bg-color); color: var(--text-color); border: none;"
>
</div>

<button
type="submit"
class="btn w-100 fw-bold"
style="background-color: var(--highlight-color); color: white; border-radius: 20px; padding: 10px;"
>
Registrati
</button>
</form>

<p class="mt-4 mb-0">
Hai già un account?
<a href="login.php" style="color: var(--text-color); font-weight: 600; text-decoration: none;">
Accedi
</a>
</p>
</div>
</div>

<script>
function toggleTheme() {
const body = document.body;
const icon = document.getElementById("themeIcon");

if (body.getAttribute("data-theme") === "light") {
body.removeAttribute("data-theme");
icon.className = "fa-solid fa-sun";
} else {
body.setAttribute("data-theme", "light");
icon.className = "fa-solid fa-moon";
}
}
</script>

</body>
</html>
