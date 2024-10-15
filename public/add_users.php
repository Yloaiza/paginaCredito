<?php
include 'config.php';

$stmt = $pdo->query('SELECT * FROM usuarios');
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Usuarios</title>
</head>
<body>
    <h1>Usuarios</h1>
    <a href="crear.php">Agregar Usuario</a>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Rol</th>
            <th>Acciones</th>
        </tr>
        <?php foreach ($usuarios as $usuario): ?>
        <tr>
            <td><?= $usuario['id'] ?></td>
            <td><?= htmlspecialchars($usuario['usuario']) ?></td>
            <td><?= $usuario['rol'] ?></td>
            <td>
                <a href="editar.php?id=<?= $usuario['id'] ?>">Editar</a>
                <a href="eliminar.php?id=<?= $usuario['id'] ?>" onclick="return confirm('¿Seguro que deseas eliminar este usuario?');">Eliminar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
