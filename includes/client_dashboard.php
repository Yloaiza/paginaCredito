<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <link rel="stylesheet" href="../assets/css/stylescliente.css">
</head>

<body>

    <?php
    // Leer el último estado del servidor
    $stmt_ultimo_estado = $pdo->prepare("SELECT estado FROM estado_servidor ORDER BY id DESC LIMIT 1");
    $stmt_ultimo_estado->execute();
    $ultimo_estado = $stmt_ultimo_estado->fetchColumn();

    // Definir mensaje y color según el estado
    $estado_servidor = '';
    $color = '';
    $icono = '';

    switch ($ultimo_estado) {
        case 1:
            $estado_servidor = 'SERVIDOR EN MANTENIMIENTO';
            $color = 'yellow';
            $icono = '⚠️'; // Ícono de advertencia
            break;
        case 2:
            $estado_servidor = 'SERVIDOR FUERA DE LÍNEA';
            $color = ' #e74c3c';
            $icono = '❌'; // Ícono de error
            break;
        case 3:
            $estado_servidor = 'SERVIDOR EN PAUSA';
            $color = 'purple';
            $icono = '⏸️'; // Ícono de pausa
            break;
        case 4:
            $estado_servidor = 'SERVIDOR EN LÍNEA';
            $color = 'green';
            $icono = '✅'; // Ícono de éxito
            break;
        default:
            $estado_servidor = 'ESTADO DESCONOCIDO';
            $color = '#7f8c8d';
            $icono = '❓'; // Ícono de desconocido
            break;
    }


    $creditClass = $creditos >= 100 ? 'green' : 'red';

    ?>
    <script>
let sidebarVisible = true; // Estado inicial del sidebar

function toggleSidebar() {
    sidebarVisible = !sidebarVisible; // Cambia el estado

    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const toggleButton = document.querySelector('.toggle-sidebar');

    if (sidebarVisible) {
        sidebar.classList.remove('hidden'); // Muestra el sidebar
        mainContent.classList.remove('full-width'); // Ajusta el contenido a la normalidad
        mainContent.classList.add('normal');
        toggleButton.textContent = '☰'; // Cambia el texto del botón
        toggleButton.style.left = '25%'; // Mueve el botón a su posición original
    } else {
        sidebar.classList.add('hidden'); // Oculta el sidebar
        mainContent.classList.add('full-width'); // Centra el contenido
        mainContent.classList.remove('normal');
        toggleButton.textContent = '☰'; // Cambia el texto del botón
        toggleButton.style.left = '5%'; // Mueve el botón hacia la izquierda
    }
}

    </script>

    <div class="container">
    <button class="toggle-sidebar" onclick="toggleSidebar()">☰</button>
        <div class="sidebar">
            <h1>BIENVENIDO</h1>
            <p><?php echo $_SESSION['username']; ?></p>
            <button class="gray" onclick="showForm()">TRAMITAR ACTA</button>
            <button class="gray" onclick="showStatus()">VERIFICAR ESTATUS</button>
            <button class="gray" onclick="showPaymentInfo()">INFORMACIÓN DE PAGO Y ESTADOS DISPONIBLES</button>
            <button class="red" onclick="cerrarsesion()">CERRAR SESION</button>

            <!-- Botón para ocultar el sidebar -->


            <div class="credits <?php echo $creditClass; ?>">
                TOTAL DE CRÉDITOS DISPONIBLES<br>$<?php echo $creditos; ?>
            </div>

            <div id="estadoServidorNotificacion" class="online" style="background-color: <?php echo $color; ?>;">
                <span style="font-size: 24px;"><?php echo $icono; ?></span>
                <span style="font-size: 18px;"><?php echo $estado_servidor; ?></span>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            
            <div id="formContainer">
                <form action="solicitud.php" method="POST">

                    <label for="curp">INGRESA LA CURP O LA HOMOCLAVE O LA CADENA</label>
                    <input type="text" id="curp" name="curp" class="form-control" maxlength="20" pattern=".{0,20}" title="Debe tener hasta 20 caracteres">

                    <label for="acta">TIPO DE ACTA</label>
                    <select id="acta" name="tipo" class="form-control" required>
                        <option value="nacimiento-con-marco">Actas de nacimiento verificadas Rapidas 30 creditos con marco (tiempo de espera 5 a 10 minutos)</option>
                        <option value="nacimiento-sin-marco">Actas de nacimiento verificadas Rapidas 25 creditos sin marco (tiempo de espera 5 a 10 minutos)</option>
                        <option value="RFC">RFC 5 creditos (tiempo de espera 5 a 10 minutos)</option>
                        <option value="matrimonio-con-marco">Matrimonio 30 creditos con marco (tiempo de espera 5 a 10 minutos)</option>
                        <option value="matrimonio-sin-marco">Matrimonio 25 creditos sin marco (tiempo de espera 5 a 10 minutos)</option>
                        <option value="divorcios-con-marco">Divorcio 30 creditos con marco (tiempo de espera 5 a 10 minutos)</option>
                        <option value="divorcios-sin-marco">Divorcio 25 creditos sin marco (tiempo de espera 5 a 10 minutos)</option>
                        <option value="defunción-con-marco">Defunción 30 creditos con marco (tiempo de espera 5 a 10 minutos)</option>
                        <option value="defunción-sin-marco">Defunción 25 creditos sin marco (tiempo de espera 5 a 10 minutos)</option>

                    </select>

                    <button>ENVIAR</button>
            </div>
        </div>
        <!-- Contenedor de información de pago y estados disponibles -->
        <div id="infoContainer" class="payment-information">
            <h3>Información de Pago</h3>
            <p><strong>Número de Cuenta:</strong> 1276 2200 1381 0338 78</p>
            <p><strong>Banco:</strong> Azteca</p>
            <p>Por favor, después de realizar tu pago, manda un mensaje vía WhatsApp al <strong>9541340534</strong> para agregar tus créditos. El monto mínimo es de 100 pesos.</p>

            <h3>HORARIOS DE ATENCION</h3>
            <ul>
                <li>9 AM A 5 PM </li>
                <li>! DE LUNES A VIERNES !</li>
            </ul>
        </div>
        <!-- Contenedor de la tabla de estado de solicitudes -->
        <div id="statusContainer" class="status-container">
            <!-- Mostrar el conteo de procesos finalizados hoy -->
            <h3>Procesos Finalizados Hoy</h3>
            <?php
            // Contar solicitudes finalizadas del día actual
            $stmt = $pdo->prepare("SELECT COUNT(*) as finalizados_hoy 
                           FROM solicitudes 
                           WHERE usuario_id = :usuario_id 
                           AND estatus = 1
                           AND DATE(fecha_solicitud) = CURDATE()");
            $stmt->execute(['usuario_id' => $user['id']]);
            $finalizados_hoy = $stmt->fetchColumn();

            // Mostrar el número de procesos finalizados hoy
            echo "<p class='estatus-finalizado' style='color: #2ecc71'><strong>Total de procesos finalizados hoy:</strong> {$finalizados_hoy}</p>";
            ?>

            <!-- Mostrar solicitudes del día actual -->
            <h3>Estado de Solicitudes de Hoy</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>CURP</th>
                            <th>Estatus</th>
                            <th>Motivo</th>
                            <th>Tipo de Acta</th>
                            <th>Documento</th>
                            <th>Hora y Día</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Obtener solo los campos necesarios de las solicitudes del usuario actual realizadas hoy
                        $stmt = $pdo->prepare("SELECT id, curp, estatus, tipo, motivo, documento_pdf, fecha_solicitud 
                                   FROM solicitudes 
                                   WHERE usuario_id = :usuario_id 
                                   AND DATE(fecha_solicitud) = CURDATE() 
                                   ORDER BY estatus ASC, id DESC");
                        $stmt->execute(['usuario_id' => $user['id']]);
                        while ($row = $stmt->fetch()) {
                            $estatus_text = '';
                            switch ($row['estatus']) {
                                case 0:
                                    $estatus_text = '<span class="estatus-pendiente">Pendiente</span>';
                                    break;
                                case 1:
                                    $estatus_text = '<span class="estatus-finalizado">Finalizado</span>';
                                    break;
                                case 2:
                                    $estatus_text = '<span class="estatus-rechazado">Rechazado</span>';
                                    break;
                            }
                            $documento_text = $row['documento_pdf']
                                ? "<a href='../public/uploads/{$row['documento_pdf']}' download class='btn btn-success'>Descargar</a>"
                                : "En Proceso";

                            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['curp']}</td>
                    <td>{$estatus_text}</td>
                    <td>{$row['motivo']}</td>
                    <td>{$row['tipo']}</td>
                    <td>{$documento_text}</td>
                    <td>{$row['fecha_solicitud']}</td>
                </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
    </div>

    <script>
        function showForm() {
            document.getElementById('formContainer').style.display = 'block';
            document.getElementById('statusContainer').style.display = 'none';
            document.getElementById('infoContainer').style.display = 'none';
        }

        function showStatus() {
            document.getElementById('formContainer').style.display = 'none';
            document.getElementById('statusContainer').style.display = 'block';
            document.getElementById('infoContainer').style.display = 'none';
        }

        function showPaymentInfo() {
            document.getElementById('formContainer').style.display = 'none';
            document.getElementById('statusContainer').style.display = 'none';
            document.getElementById('infoContainer').style.display = 'block';
        }

        function cerrarsesion() {
            window.location.href = '../public/logout.php';
        }
    </script>

</body>

</html>