<?php
include('../includes/db_connect.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_usuario = $_POST['username'];
    $contrasena = md5($_POST['password']); // Encriptar la contraseña con MD5

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_usuario = :nombre_usuario AND contrasena = :contrasena");
    $stmt->execute(['nombre_usuario' => $nombre_usuario, 'contrasena' => $contrasena]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['username'] = $nombre_usuario;
        header("Location: dashboard.php");
        exit();
    } else {
        // Mostrar SweetAlert2 si el login falla
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            
            <title>Error de Inicio de Sesión</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Nombre de usuario o contraseña incorrectos.'
                }).then(function() {
                    window.location = '../public/login.html'; // Redirige de nuevo al formulario de login
                });
            </script>
        </body>
        </html>";
    }
}
