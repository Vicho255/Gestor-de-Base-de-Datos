<?php
ob_start();
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            $db = getDB();
            // Consulta directa a la tabla usuarios (sin usar la función)
            $stmt = $db->prepare("SELECT id, username, password, rol FROM usuarios WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch();

            // Verificar si el usuario existe y la contraseña coincide con el hash
            if ($user && password_verify($password, $user['password'])) {
                // Guardar datos en sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['rol'] = $user['rol']; // Guardar rol para autorización

                header("Location: dashboard.php");
                exit();
            } else {
                $message = 'Credenciales incorrectas.';
            }
        } catch (Exception $e) {
            // Registrar el error real en los logs (no mostrarlo al usuario)
            error_log("Error en login: " . $e->getMessage());
            $message = 'Error al conectar con la base de datos.';
        }
    } else {
        $message = 'Por favor, complete todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <?php if (!empty($message)): ?>
            <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form class="login-form" method="POST" action="login.php">
            <label for="username">Usuario:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>

            <button class="login-button" type="submit">Ingresar</button>
        </form>
    </div>
</body>
</html>
<?php
ob_end_flush();
?>