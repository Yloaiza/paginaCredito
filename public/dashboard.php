<?php
// Incluir el archivo de conexi¨®n y empezar la sesi¨®n
include('../includes/db_connect.php');
session_start();

// Verificar si el usuario est¨¢ autenticado
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// Obtener los datos del usuario desde la base de datos
$stmt = $pdo->prepare("SELECT id, rol, creditos, nombre_usuario FROM usuarios WHERE nombre_usuario = :nombre_usuario");
$stmt->execute(['nombre_usuario' => $_SESSION['username']]);
$user = $stmt->fetch();

// Verificar si se obtuvieron los datos del usuario
if ($user) {
    // Guardar datos relevantes en la sesi¨®n para acceso f¨¢cil
    $_SESSION['userId'] = $user['id'];
    $_SESSION['role'] = $user['rol'];
    $_SESSION['creditos'] = $user['creditos'];
    $_SESSION['nombre_usuario'] = $user['nombre_usuario']; // Nombre del usuario autenticado

    $role = $user['rol'];
    $creditos = $user['creditos']; // Aqu¨ª se define la variable $creditos
} else {
    echo "Error al obtener los datos del usuario.";
    exit();
}

include('../includes/header.php');

// Mostrar el dashboard basado en el rol del usuario
if ($role === 'cliente' || $role === 'especial') {
    include('../includes/client_dashboard.php');
} elseif ($role === 'admin') {
    include('../includes/admin_dashboard.php');
}

include('../includes/footer.php');
?>
