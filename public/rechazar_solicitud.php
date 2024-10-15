<?php
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $motivo = $_POST['motivo'];

    // Verificar que los datos han sido recibidos correctamente
    if (!$id || !$motivo) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
        exit;
    }

    try {
        // Iniciar una transacción
        $pdo->beginTransaction();

        // Obtener los créditos restantes y el usuario_id de la solicitud rechazada
        $stmt = $pdo->prepare("SELECT usuario_id, creditos_restantes FROM solicitudes WHERE id = ?");
        $stmt->execute([$id]);
        $solicitud = $stmt->fetch();

        if (!$solicitud) {
            throw new Exception('Solicitud no encontrada');
        }

        $usuario_id = $solicitud['usuario_id'];
        $creditos_restantes = $solicitud['creditos_restantes'];

        // Sumar los créditos restantes al usuario correspondiente
        $stmt = $pdo->prepare("UPDATE usuarios SET creditos = creditos + ? WHERE id = ?");
        $stmt->execute([$creditos_restantes, $usuario_id]);

        // Actualizar el estatus de la solicitud a 'rechazado' (estatus = 2) y guardar el motivo
        $stmt = $pdo->prepare("UPDATE solicitudes SET estatus = 2, motivo = ? WHERE id = ?");
        $stmt->execute([$motivo, $id]);

        // Confirmar la transacción
        $pdo->commit();

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Error en la transacción: ' . $e->getMessage()]);
    }
}
?>
