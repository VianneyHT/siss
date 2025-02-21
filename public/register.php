<?php
// public/register.php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST['usuario']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $rol = trim($_POST['rol']); // "alumno" o "administrador"

    // Validar que las contraseñas coincidan
    if ($password !== $password_confirm) {
        $error = "Las contraseñas no coinciden.";
    } elseif (!in_array($rol, ['alumno', 'administrador'])) {
        $error = "Rol inválido seleccionado.";
    } else {
        // Verificar si el correo ya existe
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $error = "Ya existe un usuario con ese correo.";
        } else {
            // Registrar el nuevo usuario con el rol seleccionado
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, email, password, rol) VALUES (:usuario, :email, :password, :rol)");
            $result = $stmt->execute([
                'usuario'  => $usuario,
                'email'    => $email,
                'password' => $password_hash,
                'rol'      => $rol
            ]);
            if ($result) {
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
  <title>Registro de Usuario</title>
  <link rel="stylesheet" href="css/styles.css">
</head>
<body class="system-page">
  <br>
  <center><h2>Registro</h2></center>
  <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
  <form action="" method="POST">
      <input type="text" name="usuario" placeholder="No. de Control / Usuario" required>
      <br>
      <input type="email" name="email" placeholder="Correo electrónico" required>
      <br>
      <input type="password" name="password" placeholder="Contraseña" required>
      <br>
      <input type="password" name="password_confirm" placeholder="Confirmar Contraseña" required>
      <br>
      <!-- Campo para seleccionar el rol -->
      <label for="rol">Registrarse como:</label>
      <select name="rol" id="rol" required>
          <option value="alumno">Alumno</option>
          <option value="administrador">Administrador</option>
      </select>
      <br><br>
      <button type="submit">Registrarse</button>
  </form>
  <center><p><a href="login.php">Volver a Iniciar Sesión</a></p></center>
</body>
</html>