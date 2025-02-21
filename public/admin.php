<?php
// public/admin.php
session_start();
require_once '../config/db.php';

// Verificar que el usuario esté logueado y sea administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

// Incluir PHPMailer (asegúrate de haber instalado PHPMailer mediante Composer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

// Procesar la actualización de la revisión de un archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se esperan: archivo_id, estado (aceptado o rechazado) y, si es rechazado, observaciones
    $archivo_id = $_POST['archivo_id'];
    $estado = $_POST['estado'];
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : null;

    if ($estado === 'rechazado' && empty($observaciones)) {
        $admin_error = "Debe ingresar un motivo de rechazo.";
    } else {
        // Actualizar el registro del archivo
        $stmt = $pdo->prepare("UPDATE archivos SET estado = :estado, observaciones = :observaciones WHERE id = :archivo_id");
        $stmt->execute([
            'estado'       => $estado,
            'observaciones'=> $observaciones,
            'archivo_id'   => $archivo_id
        ]);

        // Obtener la solicitud asociada a este archivo
        $stmt = $pdo->prepare("SELECT solicitud_id FROM archivos WHERE id = :archivo_id");
        $stmt->execute(['archivo_id' => $archivo_id]);
        $archivo = $stmt->fetch();
        $solicitud_id = $archivo['solicitud_id'];

        // Verificar si aún hay archivos pendientes en la solicitud
        $stmt = $pdo->prepare("SELECT COUNT(*) as pendientes FROM archivos WHERE solicitud_id = :solicitud_id AND estado = 'pendiente'");
        $stmt->execute(['solicitud_id' => $solicitud_id]);
        $result = $stmt->fetch();

        if ($result['pendientes'] == 0) {
            // Determinar el estado final de la solicitud
            $stmt = $pdo->prepare("SELECT COUNT(*) as rechazados FROM archivos WHERE solicitud_id = :solicitud_id AND estado = 'rechazado'");
            $stmt->execute(['solicitud_id' => $solicitud_id]);
            $rechazados = $stmt->fetch()['rechazados'];

            $nuevo_estado = ($rechazados > 0) ? 'rechazado' : 'aceptado';
            $stmt = $pdo->prepare("UPDATE solicitudes SET estado = :nuevo_estado WHERE id = :solicitud_id");
            $stmt->execute(['nuevo_estado' => $nuevo_estado, 'solicitud_id' => $solicitud_id]);

            // Obtener el email y nombre del alumno para enviar la notificación
            $stmt = $pdo->prepare("SELECT u.email, u.nombre_usuario 
                                   FROM usuarios u 
                                   JOIN solicitudes s ON u.id = s.usuario_id 
                                   WHERE s.id = :solicitud_id");
            $stmt->execute(['solicitud_id' => $solicitud_id]);
            $usuario = $stmt->fetch();

            // Enviar notificación usando PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Configuración del servidor SMTP (ejemplo con Gmail)
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'monsecoronaluna04@gmail.com';      // Reemplaza con tu correo
                $mail->Password   = 'jqiz xraw yqmm komb';           // Reemplaza con tu contraseña de aplicación
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Configurar remitente y destinatario
                $mail->setFrom('monsecoronaluna04@gmail.com', 'Sistema de Cambio de Turno - TESCI');
                $mail->addAddress($usuario['email']);

                // Contenido del correo
                $mail->isHTML(false);
                $mail->Subject = "Solicitud de cambio de turno";
                $mail->Body    = "Hola " . $usuario['nombre_usuario'] . ",\n\nTu solicitud ha sido revisada. Ingresa al sistema para ver el estado de tus documentos.\n\nSaludos.";

                $mail->send();
            } catch (Exception $e) {
                echo "No se pudo enviar el mensaje. Error de Mailer: {$mail->ErrorInfo}";
            }
        }
    }
}

// Procesar la búsqueda por nombre de usuario
$searchTerm = "";
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);
    $stmt = $pdo->prepare("SELECT s.*, u.nombre_usuario, u.email 
                           FROM solicitudes s 
                           JOIN usuarios u ON s.usuario_id = u.id 
                           WHERE u.nombre_usuario LIKE :search
                           ORDER BY s.fecha_solicitud DESC");
    $stmt->execute(['search' => '%' . $searchTerm . '%']);
    $solicitudes = $stmt->fetchAll();
} else {
    // Consulta para obtener todas las solicitudes si no hay búsqueda
    $stmt = $pdo->query("SELECT s.*, u.nombre_usuario, u.email 
                         FROM solicitudes s 
                         JOIN usuarios u ON s.usuario_id = u.id 
                         ORDER BY s.fecha_solicitud DESC");
    $solicitudes = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Administrador</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="system-page">
    <header>
        <div style="float:left;">Usuario: <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></div>
        <div style="float:right;"><a href="logout.php">Cerrar sesión</a></div>
    </header>

    <div class="container">
        <center><h2>Solicitudes de Cambio de Turno</h2></center>
        
        <!-- Formulario de búsqueda -->
        <form action="" method="GET" style="margin-bottom: 20px; text-align: center;">
            <input type="text" name="search" placeholder="Buscar por No. de Control / Usuario" value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit">Buscar</button>
        </form>
        
        <?php
        if ($solicitudes) {
            foreach ($solicitudes as $solicitud) {
                echo "<hr>";
                echo "<center><h3>Solicitud ID: " . $solicitud['id'] . " | Alumno: " . $solicitud['nombre_usuario'] . " | Fecha: " . $solicitud['fecha_solicitud'] . " | Estado: " . ucfirst($solicitud['estado']) . "</h3></center>";

                // Obtener los archivos asociados a la solicitud
                $stmt = $pdo->prepare("SELECT * FROM archivos WHERE solicitud_id = :solicitud_id");
                $stmt->execute(['solicitud_id' => $solicitud['id']]);
                $archivos = $stmt->fetchAll();

                if ($archivos) {
                    echo "<table border='1' cellpadding='5'>";
                    echo "<tr><th>Tipo</th><th>Archivo</th><th>Estado</th><th>Observaciones</th><th>Acción</th></tr>";
                    foreach ($archivos as $archivo) {
                        echo "<tr>";
                        echo "<td>" . str_replace('_', ' ', $archivo['tipo_archivo']) . "</td>";
                        echo "<td><a href='" . $archivo['ruta_archivo'] . "' target='_blank'>Ver PDF</a></td>";
                        echo "<td>" . ucfirst($archivo['estado']) . "</td>";
                        echo "<td>" . ($archivo['observaciones'] ? $archivo['observaciones'] : '-') . "</td>";
                        echo "<td>";
                        if ($archivo['estado'] == 'pendiente') {
                            ?>
                            <form action="" method="POST" style="display:inline;">
                                <input type="hidden" name="archivo_id" value="<?php echo $archivo['id']; ?>">
                                <select name="estado" required>
                                    <option value="">Selecciona</option>
                                    <option value="aceptado">Aceptar</option>
                                    <option value="rechazado">Rechazar</option>
                                </select>
                                <input type="text" name="observaciones" placeholder="Motivo (si rechaza)">
                                <button type="submit">Actualizar</button>
                            </form>
                            <?php
                        } else {
                            echo "-";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No hay archivos para esta solicitud.</p>";
                }
            }
        } else {
            echo "<p>No se encontraron solicitudes para el término de búsqueda.</p>";
        }
        ?>
    </div>
</body>
</html>