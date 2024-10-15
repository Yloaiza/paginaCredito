<?php
// Iniciar sesión y conectar a la base de datos
session_start();
include('../includes/db_connect.php');

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Obtener el nombre del administrador desde la sesión
$adminUserName = $_SESSION['nombre_usuario'];

// Establecer el tipo de contenido de la respuesta a JSON
header('Content-Type: application/json');

// Inicializar la respuesta
$response = ['success' => false, 'message' => ''];

// Obtener datos de la petición AJAX
$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['userId'] ?? null;
$creditAmount = $data['creditAmount'] ?? null;

if ($userId === null || $creditAmount === null) {
    $response['message'] = 'Datos incompletos proporcionados.';
    echo json_encode($response);
    exit();
}

try {
    // Obtener créditos actuales y nombre del usuario
    $stmt = $pdo->prepare("SELECT creditos, nombre_usuario FROM usuarios WHERE id = :userId");
    $stmt->execute(['userId' => $userId]);
    $user = $stmt->fetch();

    // Verificar si se encontró el usuario
    if ($user) {
        $userName = $user['nombre_usuario'];
        $newCredits = $user['creditos'] + $creditAmount;

        // Iniciar una transacción para garantizar la atomicidad
        $pdo->beginTransaction();

        // Actualizar créditos en la base de datos
        $stmt = $pdo->prepare("UPDATE usuarios SET creditos = :newCredits WHERE id = :userId");
        $success = $stmt->execute(['newCredits' => $newCredits, 'userId' => $userId]);

        if ($success) {
            // Confirmar transacción
            $pdo->commit();

            $telegram_token = '6845776951:AAFdC8gBR_83s5WCQpjE1xVFIpO7j7SaduU'; // Reemplaza con tu token de Telegram
            $chat_id = '7403127440'; // Reemplaza con tu ID de chat
            $message = "Créditos agregados:\n";
            $message .= "Usuario: $userName\n";
            $message .= "Cantidad: $creditAmount\n";
            $message .= "Actualizado por: $adminUserName\n"; // Usa la variable de sesión aquí

            // URL para enviar el mensaje
            $url = "https://api.telegram.org/bot$telegram_token/sendMessage?chat_id=$chat_id&text=" . urlencode($message);

            // Envía el mensaje
            file_get_contents($url);

            $response['success'] = true;
            $response['message'] = 'Créditos actualizados correctamente.';
        } else {
            // Revertir transacción en caso de fallo
            $pdo->rollBack();
            $response['message'] = 'Error al actualizar los créditos.';
        }
    } else {
        $response['message'] = 'Usuario no encontrado.';
    }
} catch (Exception $e) {
    // Revertir transacción si se ha iniciado y hay un error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Manejar excepciones y errores de base de datos
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Responder a la petición AJAX
echo json_encode($response);
?>
