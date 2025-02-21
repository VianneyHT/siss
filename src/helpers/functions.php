<?php
// src/helpers/functions.php

/**
 * Función para enviar un correo electrónico.
 * Puedes mejorar esta función o integrar PHPMailer para mayor robustez.
 *
 * @param string $to      Correo destino.
 * @param string $subject Asunto del mensaje.
 * @param string $message Cuerpo del mensaje.
 * @param string $from    Correo de origen.
 * @return bool           Resultado del envío.
 */
function sendEmail($to, $subject, $message, $from = 'no-reply@tu_dominio.com') {
    $headers = "From: " . $from;
    return mail($to, $subject, $message, $headers);
}
