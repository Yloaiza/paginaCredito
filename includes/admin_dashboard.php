<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/stylesadmin.css">
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

</head>

<body>
    <script>
        let sidebarVisible = true; 
        function toggleSidebar() {
            sidebarVisible = !sidebarVisible; 

            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleButton = document.querySelector('.toggle-sidebar');
            const form = document.getElementById('estadoServidorForm');

            if (sidebarVisible) {
                sidebar.classList.remove('hidden'); 
                mainContent.classList.remove('full-width'); 
                mainContent.classList.add('normal');
                toggleButton.textContent = '☰'; 
                form.classList.remove('hidden-form'); 
            } else {
                sidebar.classList.add('hidden'); 
                mainContent.classList.add('full-width'); 
                mainContent.classList.remove('normal');
                toggleButton.textContent = '☰'; 
                form.classList.add('hidden-form'); 
            }
        }
        document.querySelector('.toggle-sidebar').addEventListener('click', toggleSidebar);
    </script>


    <div class="container">
        <button class="toggle-sidebar" onclick="toggleSidebar()">☰</button>
        <!-- Sidebar -->
        <div class="sidebar">
            <h1>BIENVENIDO</h1>
            <p><?php echo $_SESSION['username']; ?></p>
            <button class="gray" onclick="showForm()">PENDIENTES</button>
            <button class="gray" onclick="showCreditsForm()">AGREGAR CREDITOS</button>
            <button class="gray">NOTARIAS</button>
            <button class="red" onclick="cerrarSesion()">CERRAR SESION</button>
        </div>

        <!-- Select para cambiar estado del servidor (siempre centrado en la parte superior) -->
        <form id="estadoServidorForm" action="" method="POST">
            <label for="estado_servidor">Estado del Servidor:</label>
            <select name="estado_servidor" id="estado_servidor">
                <option value="1">SERVIDOR EN MANTENIMIENTO</option>
                <option value="2">SERVIDOR FUERA DE LÍNEA</option>
                <option value="3">SERVIDOR EN PAUSA</option>
                <option value="4">SERVIDOR EN LINEA</option>

            </select>
            <button type="submit" name="enviar_estado">Enviar</button>
        </form>

        <?php
        // Insertar el estado seleccionado en la tabla `estado_servidor`
        if (isset($_POST['enviar_estado'])) {
            $estado_seleccionado = $_POST['estado_servidor'];

            // Consulta para insertar el estado en la tabla `estado_servidor`
            $stmt_estado = $pdo->prepare("INSERT INTO estado_servidor (estado) VALUES (:estado)");
            $stmt_estado->bindParam(':estado', $estado_seleccionado);
            $stmt_estado->execute();
        }
        ?>


        <!-- Main Content -->
        <div class="main-content">
            <!-- Contenedor para los pendientes -->
            <div id="formContainer" class="table-container">
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
                    echo "<p class='estatus-finalizado'><strong >{$count['Tipo']}</strong> Finalizadas: <span>{$count['finalizadas']}</span></p>";
                }
                ?>
                <h3>Solicitudes del Día Actual</h3>
                <p><strong class="estatus-finalizado">Total Finalizadas:</strong> <span class="estatus-finalizado"><?php echo $finalizadas_total; ?></span> |
                    <strong class="estatus-rechazado">Total Rechazadas:</strong> <span><?php echo $rechazadas_total; ?></span>
                </p>

                <h3>Estado de Solicitudes</h3>
                <div class="table-container2">
                    <div class="table-responsive">
                        <table class="table2">
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
                                <input class='upload' type='file' name='archivo'>
                                <input type='hidden' name='solicitud_id' value='{$row['id']}'>
                                <button type='submit' class='btn-load'>Subir Archivo</button>
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
       

        <!-- Contenedor para agregar créditos -->
        <div id="creditsContainer" class="table-container" style="display: none;">
            <h3>Usuarios</h3>
            <!-- Barra de búsqueda -->
            <input type="text" id="searchInput" class="search" placeholder="Buscar usuario..." onkeyup="searchUser()">

            <!-- Botón para agregar un nuevo usuario -->
            <button class="btn btn-success mb-3" onclick="showAddUserForm()"> <img class="add-user" src="../assets/images/add_user.png" />Agregar Usuario</button>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Rol</th>
                            <th>Nombre de Usuario</th>
                            <th>Créditos</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php
                        // Obtener todos los usuarios
                        $stmt = $pdo->prepare("SELECT * FROM usuarios");
                        $stmt->execute();

                        while ($row = $stmt->fetch()) {
                            echo "<tr>
            <td>{$row['rol']}</td>
            <td>{$row['nombre_usuario']}</td>
            <td id='credits-{$row['id']}'>{$row['creditos']}</td>
            <td>
                <div class='buttons-action'>
                   <button title='Agregar creditos' class='btn-add' onclick='addCredits({$row['id']})'><img src='../assets/images/add_credits.png'/></button>
                <button  title='Cambiar contraseña' class='btn-update' onclick='updatePassword({$row['id']})'><img src='../assets/images/update_user.png'/></button>
                  <button  title='Eliminar usuario' class='btn-delete' onclick='deleteUser({$row['id']})'><img src='../assets/images/delete_user.png'/></button>
                </div>
            </td>
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
    <!-- #region 
    
   
   <-- Modal para motivo de rechazo -->
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
    </div>



    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function showForm() {
            document.getElementById('formContainer').style.display = 'block';
            document.getElementById('creditsContainer').style.display = 'none';
        }

        function showCreditsForm() {
            document.getElementById('formContainer').style.display = 'none';
            document.getElementById('creditsContainer').style.display = 'block';
        }


        function cerrarSesion() {
            alert("Cerrando sesión");
            window.location.href = 'logout.php';
        }

        function addCredits(userId) {
            Swal.fire({
                title: 'Agregar Créditos',
                input: 'number',
                inputLabel: 'Cantidad de créditos a agregar',
                inputPlaceholder: 'Ingresa la cantidad de créditos',
                showCancelButton: true,
                confirmButtonText: 'Agregar',
                cancelButtonText: 'Cancelar',
                preConfirm: (credits) => {
                    if (credits <= 0) {
                        Swal.showValidationMessage('Por favor, ingresa una cantidad mayor a 0');
                        return false;
                    }
                    return credits;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const creditAmount = parseInt(result.value);
                    updateCredits(userId, creditAmount);
                }
            });
        }

        function updateCredits(userId, creditAmount) {
            fetch('update_credits.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        userId,
                        creditAmount
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log(data); // Para depuración, muestra la respuesta en la consola
                    if (data.success) {
                        const currentCreditsElement = document.getElementById(`credits-${userId}`);
                        const currentCredits = parseInt(currentCreditsElement.textContent);
                        currentCreditsElement.textContent = currentCredits + creditAmount;

                        Swal.fire('Éxito', data.message, 'success'); // Usa el mensaje del servidor
                    } else {
                        Swal.fire('Error', data.message, 'error'); // Usa el mensaje del servidor
                    }
                })
                .catch(error => {
                    console.error('Error al actualizar créditos:', error);
                    Swal.fire('Error', 'Ocurrió un error al intentar actualizar los créditos', 'error');
                });
        }

        // Función de búsqueda de usuario
        function searchUser() {
            let input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("userTableBody");
            tr = table.getElementsByTagName("tr");
            for (i = 0; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td")[1];
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        // Mostrar formulario para agregar un nuevo usuario
        function showAddUserForm() {
            Swal.fire({
                title: 'Agregar Usuario',
                html: `
                <input type="text" id="username" class="swal2-input" placeholder="Usuario">
                <input type="password" id="password" class="swal2-input" placeholder="Contraseña">
                <select id="role" class="swal2-input">
                    <option value="admin">Admin</option>
                    <option value="cliente">Cliente</option>
                </select>
            `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const username = Swal.getPopup().querySelector('#username').value;
                    const password = Swal.getPopup().querySelector('#password').value;
                    const role = Swal.getPopup().querySelector('#role').value;
                    if (!username || !password) {
                        Swal.showValidationMessage('Por favor, llena todos los campos');
                        return false;
                    }
                    return {
                        username,
                        password,
                        role
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const {
                        username,
                        password,
                        role
                    } = result.value;
                    addUser(username, password, role);
                }
            });
        }

        // Función para agregar usuario
        function addUser(username, password, role) {
            fetch('add_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username,
                        password,
                        role
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Éxito', 'Usuario agregado correctamente', 'success');
                        // Actualizar la lista de usuarios después de agregar uno nuevo
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error al agregar usuario:', error);
                    Swal.fire('Error', 'Ocurrió un error al intentar agregar el usuario', 'error');
                });
        }

        function deleteUser(userId) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "No podrás revertir esto",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    return fetch('delete_user.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json' // Asegúra de enviar el tipo correcto
                            },
                            body: JSON.stringify({
                                userId: userId // Enviando el userId como un JSON
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Error en la respuesta del servidor');
                            }
                            return response.json(); // Convertir la respuesta a JSON
                        })
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Éxito', 'Usuario eliminado correctamente', 'success')
                                    .then(() => {
                                        location.reload(); // Recargar la página después de eliminar
                                    });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error al eliminar usuario:', error);
                            Swal.fire('Error', 'Ocurrió un error al intentar eliminar el usuario', 'error');
                        });
                }
            });
        }


        function updatePassword(userId) {
            Swal.fire({
                title: 'Actualizar Contraseña',
                input: 'password',
                inputLabel: 'Nueva Contraseña',
                inputPlaceholder: 'Ingresa la nueva contraseña',
                showCancelButton: true,
                confirmButtonText: 'Actualizar',
                cancelButtonText: 'Cancelar',
                preConfirm: (password) => {
                    if (!password) {
                        Swal.showValidationMessage('Por favor, ingresa una contraseña');
                        return false;
                    }
                    return password; // Devolvemos la nueva contraseña
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const newPassword = result.value;
                    // Llama a la función de actualización de contraseña
                    sendPasswordUpdateRequest(userId, newPassword);
                }
            });
        }

        function sendPasswordUpdateRequest(userId, newPassword) {
            // Verificar que los campos no estén vacíos
            if (!userId || !newPassword) {
                Swal.fire('Error', 'El ID de usuario y la nueva contraseña son obligatorios', 'error');
                return; // Salir de la función si hay un error
            }

            // Mostrar confirmación antes de proceder
            Swal.fire({
                title: '¿Estás seguro?',
                text: "No podrás revertir esto",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cambiar',
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('update_password.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                userId,
                                newPassword
                            })
                        })
                        .then(response => {
                            // Verificar si la respuesta es un JSON
                            if (!response.ok) {
                                throw new Error('Error en la respuesta del servidor');
                            }
                            return response.json(); // Intentar convertir a JSON
                        })
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Éxito', 'Contraseña actualizada correctamente', 'success');
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error al actualizar contraseña:', error);
                            Swal.fire('Error', 'Ocurrió un error al intentar actualizar la contraseña', 'error');
                        });
                }
            });
        }

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