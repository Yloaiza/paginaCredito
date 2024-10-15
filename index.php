<?php
// Iniciar una sesión si aún no está iniciada
session_start();

// Incluir la conexión a la base de datos
include '../includes/db_connect.php';

// Verificar si el usuario está logueado
if (isset($_SESSION['user_id'])) {
    // Si está logueado, redirigir al dashboard
    header('Location: public/dashboard.php');
    exit();
}

// Si no está logueado, mostrar el formulario de inicio de sesión
header('Location: public/login.html');
exit();
?>
