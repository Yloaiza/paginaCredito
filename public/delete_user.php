<?php

include '../includes/db_connect.php';


header('Content-Type: application/json');


$response = ['success' => false, 'message' => ''];

$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['userId'];

// Validar que el ID de usuario no esté vacío
if (!empty($userId)) {
    try {
        // Primero, eliminar las solicitudes relacionadas con el usuario
        $stmt = $pdo->prepare("DELETE FROM solicitudes WHERE usuario_id = :userId");
        $stmt->execute(['userId' => $userId]);

        // Luego, eliminar el usuario
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :userId");
        $success = $stmt->execute(['userId' => $userId]);

        if ($success) {
            $response['success'] = true;
            $response['message'] = 'Usuario eliminado correctamente junto con sus solicitudes.';
        } else {
            $response['message'] = 'Error al eliminar el usuario.';
        }
    } catch (PDOException $e) {

        $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
    } catch (Exception $e) {

        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'El ID del usuario es requerido.';
}


echo json_encode($response);
?>
