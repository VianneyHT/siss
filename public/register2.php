<?php
// public/register.php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST['usuario']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Validar que las contraseñas coincidan
    if ($password !== $password_confirm) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Verificar si el correo ya existe
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $error = "Ya existe un usuario con ese correo.";
        } else {
            // Registrar el nuevo usuario (rol por defecto: alumno)
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, email, password, rol) VALUES (:usuario, :email, :password, 'alumno')");
            if ($stmt->execute(['usuario' => $usuario, 'email' => $email, 'password' => $password_hash])) {
                header("Location: login.php");
                exit;
            } else {
                $error = "Error al registrar el usuario.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/styles.css">
  <title>Registro de Alumno</title>
</head>
<body class="system-page">
    <br>
  <center><h2>Registro</h2></center>
  <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
  <form action="" method="POST">
      <input type="text" name="usuario" placeholder="Numero de Control" required>
      <br>
      <input type="email" name="email" placeholder="Correo electrónico" required>
      <br>
      <input type="password" name="password" placeholder="Contraseña" required>
      <br>
      <input type="password" name="password_confirm" placeholder="Confirmar Contraseña" required>
      <br>
      <button type="submit">Registrarse</button>
  </form>
  <center><p><a href="login.php">Volver a Iniciar Sesión</a></p></center>
</body>
</html>
