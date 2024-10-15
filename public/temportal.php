<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .table-container {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 2rem;
        }
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 250px;
            background-color: #f8f9fa;
            padding: 1rem;
        }
        .main-content {
            margin-left: 250px;
            padding: 1rem;
        }
        .estatus-finalizado {
            color: green;
            font-weight: bold;
        }
        .estatus-rechazado {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="sidebar">
            <p class="font-weight-bold">BIENVENIDO<br><?php echo $_SESSION['username']; ?></p>
            <button class="btn btn-primary btn-block mb-3" onclick="showForm()">PENDIENTES</button>
            <button class="btn btn-secondary btn-block mb-3" onclick="showCreditsForm()">AGREGAR CREDITOS</button>
            <button class="btn btn-secondary btn-block mb-3" onclick="showUserForm()">AGREGAR USUARIOS</button>
            <button class="btn btn-danger btn-block mb-3" onclick="cerrarSesion()">CERRAR SESION</button>
        </div>

        <!-- Main Content -->
        <div id="yesterdayContainer" class="table-container">
        <div class="main-content">
            <!-- Resumen de actas finalizadas y rechazadas del día actual -->
            <?php
            // Contar actas finalizadas y rechazadas del día actual por tipo de acta
            $stmt_count = $pdo->prepare("SELECT Tipo, 
                                          SUM(estatus = 1) AS finalizadas, 
                                          SUM(estatus = 2) AS rechazadas 
                                          FROM solicitudes 
                                          WHERE DATE(fecha_solicitud) = CURDATE()
                                          GROUP BY Tipo");
            $stmt_count->execute();
            $counts = $stmt_count->fetchAll();

            $finalizadas_total = 0;
            $rechazadas_total = 0;

            foreach ($counts as $count) {
                $finalizadas_total += $count['finalizadas'];
                $rechazadas_total += $count['rechazadas'];
                echo "<p><strong>{$count['Tipo']}</strong>: Finalizadas: <span class='estatus-finalizado'>{$count['finalizadas']}</span> | Rechazadas: <span class='estatus-rechazado'>{$count['rechazadas']}</span></p>";
            }
            ?>
            <h3>Solicitudes del Día Actual</h3>
            <p><strong>Total Finalizadas:</strong> <span class="estatus-finalizado"><?php echo $finalizadas_total; ?></span> | <strong>Total Rechazadas:</strong> <span class="estatus-rechazado"><?php echo $rechazadas_total; ?></span></p>
            
            <!-- Contenedor para los pendientes del día actual -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>CURP</th>
                        <th>Tipo de Acta</th>
                        <th>Estatus</th>
                        <th>Documento</th>
                        <th>Rechazar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Obtener las solicitudes del día actual
                    $stmt_today = $pdo->prepare("SELECT * FROM solicitudes WHERE DATE(fecha_solicitud) = CURDATE() ORDER BY estatus ASC, id DESC");
                    $stmt_today->execute();

                    while ($row = $stmt_today->fetch()) {
                        $estatus_text = '';
                        $estatus_class = '';
                        switch ($row['estatus']) {
                            case 0:
                                $estatus_text = 'Pendiente';
                                break;
                            case 1:
                                $estatus_text = 'Finalizado';
                                $estatus_class = 'estatus-finalizado';
                                break;
                            case 2:
                                $estatus_text = 'Rechazado';
                                $estatus_class = 'estatus-rechazado';
                                break;
                        }

                        $documento_text = $row['documento_pdf'] 
                            ? "<a href='../public/uploads/{$row['documento_pdf']}' download class='btn btn-success'>Descargar</a>" 
                            : "<form action='upload_file.php' method='POST' enctype='multipart/form-data'>
                                <input type='file' name='archivo'>
                                <input type='hidden' name='solicitud_id' value='{$row['id']}'>
                                <button type='submit' class='btn btn-warning'>Subir Archivo</button>
                              </form>";

                        $tipo_acta = isset($row['Tipo']) ? $row['Tipo'] : 'Tipo no disponible';

                        echo "<tr>
                            <td>{$row['id']}</td>
                            <td>{$row['curp']}</td>
                            <td>{$tipo_acta}</td>
                            <td class='{$estatus_class}'>{$estatus_text}</td>
                            <td>{$documento_text}</td>
                            <td><button class='btn btn-danger' onclick='rechazarSolicitud({$row['id']})'>Rechazar</button></td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para motivo de rechazo -->
<div class="modal fade" id="rechazoModal" tabindex="-1" role="dialog" aria-labelledby="rechazoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rechazoModalLabel">Motivo de Rechazo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <textarea id="motivoRechazo" class="form-control" rows="3" placeholder="Escribe el motivo del rechazo..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmarRechazo()">Confirmar Rechazo</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    let solicitudRechazoId = null;

    function rechazarSolicitud(id) {
        solicitudRechazoId = id;
        $('#rechazoModal').modal('show');
    }

    function confirmarRechazo() {
    const motivo = $('#motivoRechazo').val();
    if (!motivo) {
        Swal.fire('Error', 'Debes proporcionar un motivo para el rechazo.', 'error');
        return;
    }

    $.post('rechazar_solicitud.php', {
        id: solicitudRechazoId,
        motivo: motivo
    }, function(response) {
        console.log(response); // Aquí es donde se agrega el console.log

        if (response.success) {
            Swal.fire('Éxito', 'La solicitud ha sido rechazada.', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', 'Ocurrió un error: ' + response.error, 'error');
        }
    }, 'json');

    $('#rechazoModal').modal('hide');
}

</script>
</body>
</html>
