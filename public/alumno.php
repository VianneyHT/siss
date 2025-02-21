<?php
// public/alumno.php
session_start();
require_once '../config/db.php';

// Verificar que el usuario esté logueado y sea alumno
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'alumno') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Procesar el formulario de subida de archivos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Solo se permiten archivos PDF
    $allowed_ext = ['pdf'];
    $upload_dir = __DIR__ . '/uploads/';

    // Definir los campos y sus correspondientes tipos en la base de datos
    $file_fields = [
        'carta'             => 'carta_exposicion_motivos',
        'boleta_semestral'  => 'boleta_semestral',
        'boleta_ingles'     => 'boleta_ingles',
        'otros'             => 'otros'
    ];

    // Crear una nueva solicitud
    $stmt = $pdo->prepare("INSERT INTO solicitudes (usuario_id) VALUES (:usuario_id)");
    $stmt->execute(['usuario_id' => $user_id]);
    $solicitud_id = $pdo->lastInsertId();

    // Procesar cada archivo
    foreach ($file_fields as $field_name => $tipo_archivo) {
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES[$field_name]['tmp_name'];
            $file_name = $_FILES[$field_name]['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($file_ext, $allowed_ext)) {
                $error = "El archivo para " . str_replace('_', ' ', $tipo_archivo) . " debe ser un PDF.";
                break;
            }

            // Generar un nombre único para evitar colisiones
            $new_file_name = uniqid() . "_" . $tipo_archivo . "." . $file_ext;
            $destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                // Insertar registro en la tabla de archivos
                $stmt = $pdo->prepare("INSERT INTO archivos (solicitud_id, tipo_archivo, ruta_archivo) VALUES (:solicitud_id, :tipo_archivo, :ruta_archivo)");
                $stmt->execute([
                    'solicitud_id' => $solicitud_id,
                    'tipo_archivo' => $tipo_archivo,
                    'ruta_archivo' => 'uploads/' . $new_file_name
                ]);
            } else {
                $error = "Error al subir el archivo: " . str_replace('_', ' ', $tipo_archivo);
                break;
            }
        } else {
            $error = "Por favor, sube el archivo para " . str_replace('_', ' ', $tipo_archivo);
            break;
        }
    }

    if (!isset($error)) {
        $success = "Archivos subidos correctamente. Tu solicitud está en revisión.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <title>Panel del Alumno</title>
</head>
<body class="system-page">
    <header>
        <div style="float:left;">Usuario: <b><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></b></div>
        <div style="float:right;"><a href="logout.php">Cerrar sesión</a></div>
    </header>
<br>
    <center><h2>Subir Documentos</h2></center>

    <?php 
        if (isset($error)) { echo "<p style='color:red;'>$error</p>"; }
        if (isset($success)) { echo "<p style='color:green;'>$success</p>"; }
    ?>
    <form action="" method="POST" enctype="multipart/form-data">
        <label>Carta de exposición de motivos:</label>
        <input type="file" name="carta" accept="application/pdf" required><br><br>

        <label>Boleta semestral:</label>
        <input type="file" name="boleta_semestral" accept="application/pdf" required><br><br>

        <label>Boleta de inglés:</label>
        <input type="file" name="boleta_ingles" accept="application/pdf" required><br><br>

        <label>Otros motivos:</label>
        <input type="file" name="otros" accept="application/pdf" required><br><br>

        <button type="submit">Subir archivos</button>
    </form>

    <center><h2>Historial de Solicitudes</h2></center>
    <?php
    // Obtener todas las solicitudes del alumno
    $stmt = $pdo->prepare("SELECT s.id, s.fecha_solicitud, s.estado FROM solicitudes s WHERE s.usuario_id = :usuario_id ORDER BY s.fecha_solicitud DESC");
    $stmt->execute(['usuario_id' => $user_id]);
    $solicitudes = $stmt->fetchAll();

    if ($solicitudes) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID Solicitud</th><th>Fecha</th><th>Estado</th><th>Documentos</th></tr>";
        foreach ($solicitudes as $sol) {
            echo "<tr>";
            echo "<td>" . $sol['id'] . "</td>";
            echo "<td>" . $sol['fecha_solicitud'] . "</td>";
            echo "<td>" . ucfirst($sol['estado']) . "</td>";
            echo "<td>";

            // Obtener los archivos asociados a la solicitud
            $stmt_files = $pdo->prepare("SELECT * FROM archivos WHERE solicitud_id = :solicitud_id");
            $stmt_files->execute(['solicitud_id' => $sol['id']]);
            $archivos = $stmt_files->fetchAll();

            if ($archivos) {
                echo "<table border='1' cellpadding='3'>";
                echo "<tr><th>Tipo</th><th>Archivo</th><th>Estado</th><th>Observaciones</th></tr>";
                foreach ($archivos as $archivo) {
                    echo "<tr>";
                    echo "<td>" . str_replace('_', ' ', $archivo['tipo_archivo']) . "</td>";
                    echo "<td><a href='" . $archivo['ruta_archivo'] . "' target='_blank'>Ver PDF</a></td>";
                    echo "<td>" . ucfirst($archivo['estado']) . "</td>";
                    echo "<td>" . ($archivo['observaciones'] ? $archivo['observaciones'] : '-') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "No hay documentos.";
            }

            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No has realizado solicitudes aún.</p>";
    }
    ?>
</body>
</html>
