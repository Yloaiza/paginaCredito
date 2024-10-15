<?php
include('../includes/db_connect.php'); // Asegúrate de incluir tu archivo de conexión

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $solicitud_id = $_POST['solicitud_id'];
    $file = $_FILES['archivo'];

    // Verificar si hay errores con el archivo
    if ($file['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../public/uploads/'; // Asegúrate de que esta ruta sea correcta y tenga permisos de escritura
        $file_name = basename($file['name']);
        $file_path = $upload_dir . $file_name;

        // Mover el archivo a la carpeta de subidas
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Actualizar la base de datos con el nombre del archivo y cambiar el estado
            $stmt = $pdo->prepare("UPDATE solicitudes SET documento_pdf = :documento_pdf, estatus = 1 WHERE id = :id");
            $stmt->execute(['documento_pdf' => $file_name, 'id' => $solicitud_id]);

            echo "<!DOCTYPE html>
                <html lang='es'>
                <head>
                    <meta charset='UTF-8'>
                    <title>Éxito</title>
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head>
                <body>
                    <script>
                    Swal.fire({
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 3000
                    }).then(() => {
                        window.history.back();
                    });
                    </script>
                </body>
                </html>";
        } else {
            echo "Error al mover el archivo.";
        }
    } else {
        echo "Error en la subida del archivo: " . $file['error'];
    }
}
?>
