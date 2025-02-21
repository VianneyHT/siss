<?php
// public/index.php
session_start();

// Si el usuario ya está autenticado, redirige según su rol
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['rol'] === 'alumno') {
        header("Location: alumno.php");
        exit;
    } elseif ($_SESSION['rol'] === 'administrador') {
        header("Location: admin.php");
        exit;
    }
}

// Si no hay sesión activa, redirige al login
header("Location: login.php");
exit;
