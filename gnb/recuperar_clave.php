<?php
session_start();
require_once 'conexion.php';

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';

// Verificar si se reciben los datos desde el modal o formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo_rec = trim($_POST['correo_recuperacion'] ?? $_POST['correo'] ?? '');
    $cedula_rec = trim($_POST['cedula_recuperacion'] ?? $_POST['cedula'] ?? '');

    if (!empty($correo_rec)) {
        try {
            // Verificar si el correo (y cédula si fue proporcionada) existen en la BD
            if (!empty($cedula_rec)) {
                $stmt = $pdo->prepare("SELECT id, nombre, email FROM usuarios WHERE email = :email AND cedula = :cedula LIMIT 1");
                $stmt->execute(['email' => $correo_rec, 'cedula' => $cedula_rec]);
            } else {
                $stmt = $pdo->prepare("SELECT id, nombre, email FROM usuarios WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $correo_rec]);
            }
            
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                // 1. Generar token único y fecha de expiración (1 hora de validez)
                $token = bin2hex(random_bytes(32));
                $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // 2. Guardar token en la base de datos
                $update = $pdo->prepare("UPDATE usuarios SET token_recuperacion = ?, token_expiracion = ? WHERE id = ?");
                $update->execute([$token, $expiracion, $usuario['id']]);

                // 3. Crear enlace dinámico de restablecimiento
                $enlace = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/restablecer_clave.php?token=" . $token;

                // 4. Intentar enviar correo con PHPMailer si la librería está instalada
                $phpmailer_path = __DIR__ . '/PHPMailer/PHPMailer.php';
                
                if (file_exists($phpmailer_path)) {
                    require_once __DIR__ . '/PHPMailer/Exception.php';
                    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
                    require_once __DIR__ . '/PHPMailer/SMTP.php';

                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

                    try {
                        // Configuración SMTP de Gmail / Servidor
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';             
                        $mail->SMTPAuth   = true;                           
                        $mail->Username   = 'tucorreo@gmail.com';           // <-- Ingresa tu correo real
                        $mail->Password   = 'tu_contrasena_de_aplicacion';  // <-- Ingresa la clave de aplicación de Google
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; 
                        $mail->Port       = 587;                            

                        // Remitente y destinatario
                        $mail->setFrom('tucorreo@gmail.com', 'GNB System');
                        $mail->addAddress($correo_rec, $usuario['nombre']);     

                        // Asunto y Contenido en HTML
                        $mail->isHTML(true);
                        $mail->Subject = 'Recuperacion de Contrasena - GNB System';
                        $mail->Body    = "
                            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #e9ecef; border-radius: 10px;'>
                                <h2 style='color: #0d6efd;'>GNB System - Recuperación de Acceso</h2>
                                <p>Hola <b>{$usuario['nombre']}</b>,</p>
                                <p>Has solicitado restablecer tu contraseña de acceso al sistema. Haz clic en el siguiente botón para continuar:</p>
                                <p style='margin: 25px 0;'>
                                    <a href='{$enlace}' style='background-color: #0d6efd; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Restablecer Contraseña</a>
                                </p>
                                <p>Este enlace expirará en 1 hora por razones de seguridad.</p>
                                <hr style='border: none; border-top: 1px solid #eee;'>
                                <p style='font-size: 12px; color: #6c757d;'>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
                            </div>
                        ";

                        $mail->send();
                        $mensaje = "Se ha enviado un correo electrónico a <strong>" . htmlspecialchars($correo_rec) . "</strong> con el enlace para restablecer tu contraseña.";
                        $tipo_mensaje = "success";

                    } catch (Exception $e) {
                        $mensaje = "Error al enviar el correo por SMTP: " . $mail->ErrorInfo;
                        $tipo_mensaje = "danger";
                    }

                } else {
                    // Si aún no has colocado la carpeta PHPMailer, muestra el enlace para no bloquear el sistema en desarrollo:
                    $mensaje = "<strong>Modo de Desarrollo (Librería PHPMailer no detectada):</strong><br>El token ha sido generado en la base de datos.<br><br>Enlace directo: <a href='{$enlace}' class='alert-link'>{$enlace}</a>";
                    $tipo_mensaje = "warning";
                }

            } else {
                $mensaje = "Los datos ingresados no coinciden con ningún usuario registrado.";
                $tipo_mensaje = "danger";
            }
        } catch (PDOException $e) {
            $mensaje = "Error de conexión a la base de datos: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    } else {
        $mensaje = "Por favor ingresa un correo electrónico válido.";
        $tipo_mensaje = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GNB - Estado de Recuperación</title>
    <!-- ENLACES LOCALES (Funcionan 100% sin internet) -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card-estado {
            width: 100%;
            max-width: 480px;
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            background: #ffffff;
            overflow: hidden;
        }
    </style>
</head>
<body>

    <div class="container px-3">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card card-estado p-4 p-md-5 text-center">
                    
                    <div class="mb-4">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <i class="bi bi-shield-lock-fill fs-1"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">Solicitud de Recuperación</h4>
                        <p class="text-muted small">Estado de procesamiento de credenciales</p>
                    </div>

                    <?php if (!empty($mensaje)): ?>
                        <div class="alert alert-<?php echo $tipo_mensaje; ?> text-start small mb-4" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i><?php echo $mensaje; ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <a href="index.php" class="btn btn-primary fw-bold py-2">
                            <i class="bi bi-arrow-left me-1"></i> Volver al Inicio de Sesión
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT LOCAL (Funciona 100% sin internet) -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <?php include 'inactivity_check.php'; ?>
</body>
</html>