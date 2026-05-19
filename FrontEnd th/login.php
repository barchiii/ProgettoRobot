<?php
// Avvio della sessione
session_start();

// Se l'utente è già loggato, viene reindirizzato alla dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
header("Location: index.php");
exit;
}

require 'config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = trim($_POST['password']);

    $sql = "SELECT id, username, password FROM utenti WHERE username = '$username'";
    $result = $conn->query($sql);

    // Controlla se l'utente eiste cercando un utente con lo username inserito
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Verifica della password usando password_verify
        if (password_verify($password, $row['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $row['username'];

            // Se il login è avvenuto con successo l'utente viene reindirizzato alla dashboard
            header("Location: index.php");
            exit;
        // Errore della password errata
        } else {
            $error = "Password errata.";
        }
    // Errore dell'utente non trovato       
    } else {
            $error = "Utente non trovato.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom py-3">
<div class="container-fluid">
<div class="ms-auto">
<!-- Pulsante cambiare il tema da scuro a chiaro -->
<button class="btn btn-custom rounded-circle p-2 px-3" onclick="toggleTheme()" title="Cambia Tema">
<i id="themeIcon" class="fa-solid fa-sun"></i>
</button>
</div>
</div>
</nav>

<div class="login-container">
<div class="custom-card text-center">
<h3 class="mb-4">Login</h3>

<!-- visualizzazione degli errori del login -->
<?php if (!empty($error)): ?>
<div class="alert alert-danger" role="alert">
<?php echo $error; ?>
</div>
<?php endif; ?>

<!-- Campo del nome utente -->
<form method="POST" action="login.php">
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

<!-- Campo della password -->
<div class="mb-4 text-start">
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

<!-- Pulsante per accedere -->
<button
type="submit"
class="btn w-100 fw-bold"
style="background-color: var(--highlight-color); color: white; border-radius: 20px; padding: 10px;"
>
Accedi
</button>
</form>

<!-- Link alla pagina di registrazione -->
<p class="mt-4 mb-0">
Non hai un account?
<a href="register.php" style="color: var(--text-color); font-weight: 600; text-decoration: none;">
Registrati qui
</a>
</p>
</div>
</div>

<script>
function toggleTheme() {
    const body = document.body;
    const icon = document.getElementById("themeIcon");
    // Controlla il tema del body, se è light lo rimuove, mettendo così il tema di default(scuro), altrimenti lo imposta a light
    // Cambiando tema cambia anche l'icona del pulsante, sole per il tema chiaro e luna per il tema scuro
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