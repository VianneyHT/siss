<?php
// public/forgot_password.php
session_start();
require_once '../config/db.php';

// Incluir PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    // Buscar usuario por email
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generar token único para resetear la contraseña
        $token = bin2hex(random_bytes(50));

        // Insertar token en la tabla password_resets
        $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token) VALUES (:user_id, :token)");
        $stmt->execute(['user_id' => $user['id'], 'token' => $token]);

        // Generar el enlace para restablecer la contraseña
        $resetLink = "http://localhost/sistema_cambio_turno/public/reset_password.php?token=" . $token;
        
        // Enviar correo usando PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Configuración del servidor SMTP (ejemplo con Gmail)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'monsecoronaluna04@gmail.com';       // Reemplaza con tu correo
            $mail->Password   = 'jqiz xraw yqmm komb';            // Reemplaza con tu contraseña de aplicación
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Configurar remitente y destinatario
            $mail->setFrom('monsecoronaluna04@gmail.com', 'Sistema de Cambio de Turno - TESCI');
            $mail->addAddress($email);

            // Contenido del correo
            $mail->isHTML(false); // Enviamos el correo en texto plano
            $mail->Subject = "Recupera tu Password";
            $mail->Body    = "Hola,\n\nHaz clic en el siguiente enlace para restablecer tu contraseña:\n$resetLink\n\nSi no solicitaste el restablecimiento, ignora este mensaje.";

            $mail->send();
            $success = "Revisa tu correo electrónico para restablecer tu contraseña.";
        } catch (Exception $e) {
            $error = "No se pudo enviar el correo. Error de Mailer: {$mail->ErrorInfo}";
        }
    } else {
        $error = "No se encontró un usuario con ese correo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/styles.css">
  <title>Recuperar Contraseña</title>
</head>
<body class="system-page">
    <br>
  <center><h2>Recuperar Contraseña</h2></center>
  <?php
    if(isset($error)) {
        echo "<p style='color:red;'>$error</p>";
    }
    if(isset($success)) {
        echo "<p style='color:green;'>$success</p>";
    }
  ?>
  <form action="" method="POST">
      <input type="email" name="email" placeholder="Ingresa tu correo electrónico" required>
      <br><br>
      <button type="submit">Enviar</button>
  </form>
  <center><p><a href="login.php">Volver a Iniciar Sesión</a></p></center>
</body>
</html>
