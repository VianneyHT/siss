<?php
// public/login.php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];

    // Consulta para obtener el usuario por su nombre
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_usuario = :usuario");
    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Credenciales correctas: guardar datos en la sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
        $_SESSION['rol'] = $user['rol'];

        // Redirigir según el rol
        if ($user['rol'] === 'alumno') {
            header("Location: alumno.php");
            exit;
        } elseif ($user['rol'] === 'administrador') {
            header("Location: admin.php");
            exit;
        }
    } else {
        $error = "Usuario o contraseña incorrecta.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión</title>
  <link rel="stylesheet" href="css/styles.css">
</head>
<body class="login-page">
    <div class="login-container">
  <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
  <form action="" method="POST">
    <center>
        <h3>Sistema de Cambio de Turno - TESCI</h3>
        <h4>Iniciar Sesión</h4>
    </center>
      <input type="text" name="usuario" placeholder="Usuario" required>
      <br>
      <input type="password" name="password" placeholder="Contraseña" required>
      <br>
      <button type="submit">Entrar</button>
      <br><br>
      <center>
      <p>
        <a href="register.php">Registrarse</a> | 
        <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>
        </p>
    </center>
  </form>
  </div>
</body>
</html>