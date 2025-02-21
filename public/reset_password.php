<?php
// public/reset_password.php
session_start();
require_once '../config/db.php';

// Inicializar variables para mensajes
$error = '';
$success = '';

// Obtener el token de la URL
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (!$token) {
    die("Token no válido.");
}

// Buscar el token en la tabla password_resets
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :token");
$stmt->execute(['token' => $token]);
$resetRequest = $stmt->fetch();

if (!$resetRequest) {
    die("Token inválido o expirado.");
}

// (Opcional) Puedes agregar una verificación de expiración del token aquí.
// Por ejemplo, si el token tiene más de 1 hora, lo consideras expirado.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Validar que las contraseñas coincidan y cumplan con requisitos mínimos
    if ($password !== $password_confirm) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        // Crear el hash de la nueva contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Actualizar la contraseña en la tabla de usuarios
        $stmt = $pdo->prepare("UPDATE usuarios SET password = :password WHERE id = :user_id");
        $stmt->execute([
            'password' => $password_hash,
            'user_id'  => $resetRequest['user_id']
        ]);

        // Eliminar el token ya que ha sido utilizado
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE id = :id");
        $stmt->execute(['id' => $resetRequest['id']]);

        $success = "Tu contraseña ha sido actualizada. <a href='login.php'>Inicia sesión</a>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <title>Restablecer Contraseña</title>
</head>
<body class="system-page">
    <h2>Restablecer Contraseña</h2>
    <?php
        if ($error) {
            echo "<p style='color:red;'>$error</p>";
        }
        if ($success) {
            echo "<p style='color:green;'>$success</p>";
        } else {
    ?>
    <form action="" method="POST">
        <input type="password" name="password" placeholder="Nueva contraseña" required>
        <br>
        <input type="password" name="password_confirm" placeholder="Confirmar contraseña" required>
        <br>
        <button type="submit">Restablecer contraseña</button>
    </form>
    <?php } ?>
</body>
</html>
