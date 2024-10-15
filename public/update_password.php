<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require  '../includes/db_connect.php';

header('Content-Type: application/json');

// Leer los datos JSON enviados por la solicitud
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['userId']) || !isset($data['newPassword'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$userId = $data['userId'];
$newPassword = $data['newPassword'];

if (empty($userId) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario o nueva contraseña no proporcionados']);
    exit;
}

$newPasswordHash = md5($newPassword);

try {
    // Verificar si el usuario existe antes de intentar actualizar
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE id = :userId");
    $checkStmt->execute(['userId' => $userId]);
    $userExists = $checkStmt->fetchColumn();

    if (!$userExists) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE usuarios SET contrasena = :newPassword WHERE id = :userId");
    $stmt->execute(['newPassword' => $newPasswordHash, 'userId' => $userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Contraseña actualizada exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la contraseña. Puede que no haya habido cambios.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
