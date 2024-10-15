<?php
// Incluir la conexión a la base de datos
include('../includes/db_connect.php');
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Verificar si se enviaron los datos del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $curp = $_POST['curp'];
    $tipo = $_POST['tipo']; 
    // Validar que los datos no estén vacíos
    if (empty($curp) || empty($tipo)) {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Error</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
            <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor, rellena todos los campos.',
            }).then(() => {
                window.history.back();
            });
            </script>
        </body>
        </html>";
        exit();
    }

    // Obtener el usuario ID desde la sesión
    $nombre_usuario = $_SESSION['username'];
    $stmt = $pdo->prepare("SELECT id, creditos FROM usuarios WHERE nombre_usuario = :nombre_usuario");
    $stmt->execute(['nombre_usuario' => $nombre_usuario]);
    $user = $stmt->fetch();

    if ($user) {
        $usuario_id = $user['id'];
        $creditos = $user['creditos'];

        // Verificar si el usuario tiene créditos suficientes
        if ($user) {
            $usuario_id = $user['id'];
            $creditos = $user['creditos'];
        
            if ($tipo === 'escuela') {
                $creditos_a_deducir = 30;
            } elseif ($tipo === 'nacimiento-sin-marco') {
                $creditos_a_deducir = 25;
            } elseif ($tipo === 'nacimiento-con-marco') {
                $creditos_a_deducir = 30; // Asigna el valor de crédito para matrimonio
            } elseif ($tipo === 'RFC') {
                $creditos_a_deducir = 5; // Asigna el valor de crédito para divorcio
            } elseif ($tipo === 'matrimonio-con-marco') {
                $creditos_a_deducir = 30; // Asigna el valor de crédito para defunción
            }elseif ($tipo === 'matrimonio-sin-marco') {
                $creditos_a_deducir = 25;
            }elseif ($tipo === 'divorcios-con-marco') {
                $creditos_a_deducir = 30;
                
            }elseif ($tipo === 'divorcios-sin-marco') {
                $creditos_a_deducir = 25;
                
            }elseif ($tipo === 'defunción-con-marco') {
                $creditos_a_deducir = 30;
                
            }elseif ($tipo === 'defunción-sin-marco') {
                $creditos_a_deducir = 25;
                
            }else
            
            {
                // Maneja el caso de tipo no válido
                echo "<!DOCTYPE html>
                <html lang='es'>
                <head>
                    <meta charset='UTF-8'>
                    <title>Error</title>
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head>
                <body>
                    <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Tipo de solicitud no válido',
                        text: 'El tipo de solicitud especificado no es válido.',
                    }).then(() => {
                        window.history.back();
                    });
                    </script>
                </body>
                </html>";
                exit; // Salir para evitar más procesamiento
            }
        
            // Verificar si el usuario tiene créditos suficientes
            if ($creditos >= $creditos_a_deducir) {
                // Calcular créditos restantes
                $creditos_restantes = $creditos - $creditos_a_deducir;
        
                // Iniciar transacción
                $pdo->beginTransaction();
        
                try {
                    // Insertar la nueva solicitud
                    $stmt = $pdo->prepare("INSERT INTO solicitudes (usuario_id, curp, tipo, estatus, creditos_restantes) VALUES (:usuario_id, :curp, :tipo, 0, :creditos_restantes)");
                    $stmt->execute([
                        'usuario_id' => $usuario_id,
                        'curp' => $curp,
                        'tipo' => $tipo,
                        'creditos_restantes' => $creditos_a_deducir // Aquí solo se muestra los créditos descontados
                    ]);
        
                    // Actualizar los créditos en la tabla de usuarios
                    $stmt = $pdo->prepare("UPDATE usuarios SET creditos = :creditos_restantes WHERE id = :usuario_id");
                    $stmt->execute(['creditos_restantes' => $creditos_restantes, 'usuario_id' => $usuario_id]);
        
                    // Confirmar transacción
                    $pdo->commit();
        
                    // Aquí se envía la notificación a Telegram
                    $telegram_token = '6845776951:AAFdC8gBR_83s5WCQpjE1xVFIpO7j7SaduU'; // Reemplaza con tu token de Telegram
                    $chat_id = '-1002207825269'; // Reemplaza con tu ID de chat
                    $message = "Nuevo registro en proceso:\n";
                    $message .= "CURP: $curp\n";
                    $message .= "Tipo De Acta: $tipo\n";
                    $message .= "Usuario: $nombre_usuario";
        
                    // URL para enviar el mensaje
                    $url = "https://api.telegram.org/bot$telegram_token/sendMessage?chat_id=$chat_id&text=" . urlencode($message);
        
                    // Envía el mensaje
                    file_get_contents($url);
        
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
                            title: 'Solicitud enviada con éxito. Créditos restantes: $creditos_restantes',
                            showConfirmButton: false,
                            timer: 3500
                        }).then(() => {
                            window.history.back();
                        });
                        </script>
                    </body>
                    </html>";
        
                } catch (Exception $e) {
                    // Revertir cambios si hay un error
                    $pdo->rollBack();
        
                    echo "<!DOCTYPE html>
                    <html lang='es'>
                    <head>
                        <meta charset='UTF-8'>
                        <title>Error</title>
                        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    </head>
                    <body>
                        <script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al procesar la solicitud: " . $e->getMessage() . "',
                        }).then(() => {
                            window.history.back();
                        });
                        </script>
                    </body>
                    </html>";
                }
            } else {
                echo "<!DOCTYPE html>
                <html lang='es'>
                <head>
                    <meta charset='UTF-8'>
                    <title>Error</title>
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                </head>
                <body>
                    <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'No tienes créditos suficientes.',
                    }).then(() => {
                        window.history.back();
                    });
                    </script>
                </body>
                </html>";
            }
        } else {
            echo "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>Error</title>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            </head>
            <body>
                <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'No tienes créditos suficientes.',
                }).then(() => {
                    window.history.back();
                });
                </script>
            </body>
            </html>";
        }
    } else {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Error</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
            <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Usuario no encontrado.',
            }).then(() => {
                window.history.back();
            });
            </script>
        </body>
        </html>";
    }
} else {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Advertencia</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
        Swal.fire({
            icon: 'warning',
            title: 'Advertencia',
            text: 'No se enviaron datos.',
        }).then(() => {
            window.history.back();
        });
        </script>
    </body>
    </html>";
}
?>
