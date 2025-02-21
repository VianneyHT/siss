<?php
// public/login.php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];
    $rol = trim($_POST['rol']); // El rol seleccionado al iniciar sesión

    // Consulta para obtener el usuario por su nombre y rol
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_usuario = :usuario AND rol = :rol");
    $stmt->execute([
        'usuario' => $usuario,
        'rol'     => $rol
    ]);
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
        $error = "Usuario, contraseña o rol incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Iniciar Sesión</title>
  <link rel="stylesheet" href="css/styles.css">
</head>
<body class="login-page">
  
  <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
  <form action="" method="POST">
    <center>
        <h3>Sistema de Cambio de Turno - TESCI</h3>
        <h4>Iniciar Sesión</h4>
    </center>
      <input type="text" name="usuario" placeholder="No. de Control / Usuario" required>
      <br>
      <input type="password" name="password" placeholder="Contraseña" required>
      <br>
      <!-- Campo para seleccionar el rol con el que se va a iniciar sesión -->
      <label for="rol">Ingresar como:</label>
      <select name="rol" id="rol" required>
          <option value="alumno">Alumno</option>
          <option value="administrador">Administrador</option>
      </select>
      <br><br>
      <button type="submit">Entrar</button>
       <br><br>
      <center>
      <p>
        <a href="register.php">Registrarse</a> | 
        <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>
        </p>
    </center>
  </form>
</body>
</html>