<?php
session_start();
require_once 'conexion.php';

$error = '';
$mensaje_recuperacion = '';
$tipo_alerta_recuperacion = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. LOGIN DE USUARIO
    if (isset($_POST['login'])) {
        $email_ingresado = trim($_POST['usuario'] ?? '');
        $clave_ingresada = $_POST['clave'] ?? '';

        if (!empty($email_ingresado) && !empty($clave_ingresada)) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $email_ingresado]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // 🛑 VALIDAR SI LA CUENTA ESTÁ SUSPENDIDA ANTES DE VERIFICAR CLAVE
                    $estado_cuenta = $user['estado'] ?? 'activo';
                    if ($estado_cuenta === 'suspendido') {
                        $error = 'Su cuenta se encuentra suspendida temporalmente. Contacte al administrador.';
                    } else {
                        $password_db = $user['password'] ?? '';
                        $clave_valida = false;

                        if (!empty($password_db)) {
                            if (password_verify($clave_ingresada, $password_db) || $clave_ingresada === $password_db) {
                                $clave_valida = true;
                            }
                        }

                        if ($clave_valida) {
                            $_SESSION['usuario_id'] = $user['id'];
                            $_SESSION['usuario_nombre'] = $user['nombre'] ?? 'Administrador';
                            $_SESSION['usuario_rol'] = strtolower(trim($user['rol'] ?? 'admin'));

                            header("Location: dashboard.php");
                            exit();
                        } else {
                            $error = 'Contraseña incorrecta.';
                        }
                    }
                } else {
                    $error = 'No se encontró ningún usuario con ese correo.';
                }
            } catch (PDOException $e) {
                $error = 'Error en el sistema: ' . $e->getMessage();
            }
        } else {
            $error = 'Por favor complete todos los campos.';
        }
    }

    // 2. RECUPERACIÓN DE CONTRASEÑA (PERSONAS NATURALES: V / E)
    if (isset($_POST['recuperar'])) {
        $correo_rec   = trim($_POST['correo_recuperacion'] ?? '');
        $tipo_ced_rec = trim($_POST['tipo_cedula_rec'] ?? 'V');
        $num_ced_rec  = trim($_POST['cedula_recuperacion'] ?? '');
        
        // Formar variantes de la cédula para asegurar coincidencia en la BD
        $cedula_completa = $tipo_ced_rec . '-' . $num_ced_rec;
        $cedula_minuscula = strtolower($tipo_ced_rec) . '-' . $num_ced_rec;

        if (!empty($correo_rec) && !empty($num_ced_rec)) {
            try {
                // Consulta flexible que busca por formato V-123, v-123 o solo números (123)
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email AND (cedula = :cedula1 OR cedula = :cedula2 OR cedula = :cedula3) LIMIT 1");
                $stmt->execute([
                    'email'   => $correo_rec, 
                    'cedula1' => $cedula_completa,
                    'cedula2' => $cedula_minuscula,
                    'cedula3' => $num_ced_rec
                ]);
                $user_rec = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user_rec) {
                    $token = bin2hex(random_bytes(32));
                    $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    $update = $pdo->prepare("UPDATE usuarios SET token_recuperacion = ?, token_expiracion = ? WHERE id = ?");
                    $update->execute([$token, $expiracion, $user_rec['id']]);

                    $enlace = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/restablecer_clave.php?token=" . $token;

                    require_once 'PHPMailer/Exception.php';
                    require_once 'PHPMailer/PHPMailer.php';
                    require_once 'PHPMailer/SMTP.php';

                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';             
                        $mail->SMTPAuth   = true;                           
                        $mail->Username   = 'leonernesto609@gmail.com';     
                        $mail->Password   = 'bsybhvuvimjqmbgr';             
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; 
                        $mail->Port       = 587;                            

                        // 🛑 CONFIGURACIÓN ANTI-CONGELAMIENTO EN LOCAL
                        $mail->Timeout    = 15; // Corta el intento tras 15 segundos si no hay respuesta de red
                        $mail->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            )
                        );

                        $mail->setFrom('leonernesto609@gmail.com', 'Laboratorio Criminalístico N° 11'); 
                        $mail->addAddress($correo_rec, $user_rec['nombre']);     

                        $mail->isHTML(true);
                        $mail->Subject = 'Recuperacion de Contrasena - Laboratorio Criminalistico';
                        $mail->Body    = "
                            <div style='font-family: Arial, sans-serif; padding: 25px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #f8fafc;'>
                                <h2 style='color: #0f172a; margin-top: 0;'>Laboratorio Criminalístico N° 11 - Recuperación de Acceso</h2>
                                <p style='color: #334155;'>Hola <b>{$user_rec['nombre']}</b>,</p>
                                <p style='color: #334155;'>Hemos verificado su identidad mediante su cédula. Haga clic en el siguiente botón para restablecer su contraseña:</p>
                                <p style='margin: 30px 0;'>
                                    <a href='{$enlace}' style='background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; padding: 12px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; box-shadow: 0 4px 12px rgba(37,99,235,0.3);'>Restablecer Contraseña</a>
                                </p>
                                <p style='color: #64748b; font-size: 13px;'>Este enlace expirará en 1 hora por seguridad.</p>
                                <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                                <p style='font-size: 11px; color: #94a3b8;'>Si no solicitó este cambio, ignore este mensaje.</p>
                            </div>
                        ";

                        $mail->send();
                        $mensaje_recuperacion = '¡Verificación exitosa! Se ha enviado un enlace de recuperación a su correo electrónico.';
                        $tipo_alerta_recuperacion = 'success';

                    } catch (Exception $e) {
                        $mensaje_recuperacion = 'Error al enviar el correo mediante el servidor SMTP: ' . $mail->ErrorInfo;
                        $tipo_alerta_recuperacion = 'danger';
                    }

                } else {
                    $mensaje_recuperacion = 'Error: El correo o la cédula ingresados no coinciden con ningún registro.';
                    $tipo_alerta_recuperacion = 'danger';
                }
            } catch (PDOException $e) {
                $mensaje_recuperacion = 'Error en el sistema de base de datos.';
                $tipo_alerta_recuperacion = 'danger';
            }
        } else {
            $mensaje_recuperacion = 'Por favor, ingrese tanto su correo como su cédula.';
            $tipo_alerta_recuperacion = 'danger';
        }
    }

    // 3. ENVÍO DE REPORTE DE INCIDENCIA AL ADMINISTRADOR
    if (isset($_POST['reportar_incidencia'])) {
        $nombre_inc   = trim($_POST['nombre_incidencia'] ?? '');
        $cedula_inc   = trim($_POST['cedula_incidencia'] ?? '');
        $correo_inc   = trim($_POST['correo_incidencia'] ?? '');
        $tipo_inc     = trim($_POST['tipo_incidencia'] ?? '');
        $mensaje_inc  = trim($_POST['mensaje_incidencia'] ?? '');

        if (!empty($nombre_inc) && !empty($correo_inc) && !empty($mensaje_inc)) {
            require_once 'PHPMailer/Exception.php';
            require_once 'PHPMailer/PHPMailer.php';
            require_once 'PHPMailer/SMTP.php';

            $mailInc = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                $mailInc->isSMTP();
                $mailInc->Host       = 'smtp.gmail.com';             
                $mailInc->SMTPAuth   = true;                           
                $mailInc->Username   = 'leonernesto609@gmail.com';     
                $mailInc->Password   = 'bsybhvuvimjqmbgr';             
                $mailInc->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; 
                $mailInc->Port       = 587;                            

                // 🛑 CONFIGURACIÓN ANTI-CONGELAMIENTO EN LOCAL
                $mailInc->Timeout    = 15; // Corta el intento tras 15 segundos si no hay respuesta de red
                $mailInc->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                $mailInc->setFrom('leonernesto609@gmail.com', 'Soporte Laboratorio Criminalístico'); 
                $mailInc->addAddress('leonernesto609@gmail.com', 'Administrador Laboratorio'); 
                $mailInc->addReplyTo($correo_inc, $nombre_inc);

                $mailInc->isHTML(true);
                $mailInc->Subject = "Reporte de Incidencia: $tipo_inc - Laboratorio Criminalístico";
                $mailInc->Body    = "
                    <div style='font-family: Arial, sans-serif; padding: 25px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff;'>
                        <h2 style='color: #dc2626; margin-top: 0;'>⚠️ Nuevo Reporte de Incidencia de Acceso</h2>
                        <p>Se ha recibido una solicitud de asistencia técnica desde el portal de inicio de sesión:</p>
                        <table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;'>Nombre Completo:</td><td style='padding: 8px; border-bottom: 1px solid #eee;'>{$nombre_inc}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;'>Cédula de Identidad:</td><td style='padding: 8px; border-bottom: 1px solid #eee;'>{$cedula_inc}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;'>Correo de Contacto:</td><td style='padding: 8px; border-bottom: 1px solid #eee;'>{$correo_inc}</td></tr>
                            <tr><td style='padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;'>Tipo de Fallo:</td><td style='padding: 8px; border-bottom: 1px solid #eee; color: #dc2626;'>{$tipo_inc}</td></tr>
                        </table>
                        <h4 style='color: #0f172a; margin-top: 20px;'>Descripción de la Incidencia:</h4>
                        <div style='padding: 15px; background-color: #f8fafc; border-radius: 8px; border-left: 4px solid #2563eb;'>
                            " . nl2br(htmlspecialchars($mensaje_inc)) . "
                        </div>
                    </div>
                ";

                $mailInc->send();
                $mensaje_recuperacion = '¡Reporte enviado exitosamente! El equipo de soporte técnico procesará su solicitud a la brevedad.';
                $tipo_alerta_recuperacion = 'success';

            } catch (Exception $e) {
                $mensaje_recuperacion = 'Error al enviar el reporte: ' . $mailInc->ErrorInfo;
                $tipo_alerta_recuperacion = 'danger';
            }
        } else {
            $mensaje_recuperacion = 'Por favor, complete los campos obligatorios del reporte.';
            $tipo_alerta_recuperacion = 'warning';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Criminalístico N° 11 (Zulia) - Iniciar Sesión</title>
    <!-- ENLACES LOCALES (Funcionan 100% sin internet) -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #090d16 0%, #111827 50%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            position: relative;
            overflow-x: hidden;
            margin: 0;
            padding: 40px 15px;
        }

        body::before {
            content: '';
            position: absolute;
            width: 450px; height: 450px;
            background: rgba(37, 99, 235, 0.12);
            top: -100px; left: -100px;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
        }
        body::after {
            content: '';
            position: absolute;
            width: 450px; height: 450px;
            background: rgba(16, 185, 129, 0.08);
            bottom: -100px; right: -100px;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
        }

        .top-buttons {
            position: absolute;
            top: 25px;
            left: 25px;
            right: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .btn-custom-glass {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #f8fafc;
            backdrop-filter: blur(12px);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            border-radius: 50rem;
        }
        .btn-custom-glass:hover {
            background: #2563eb;
            color: #ffffff;
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37,99,235,0.4);
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.75rem;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            overflow: hidden;
            z-index: 5;
            animation: fadeInCard 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeInCard {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 2.5rem 1.5rem;
            text-align: center;
            border-bottom: 4px solid #2563eb;
            position: relative;
        }
        .login-header img {
            width: 130px;
            height: 130px;
            object-fit: contain;
            filter: drop-shadow(0 6px 15px rgba(0,0,0,0.6));
            margin-bottom: 0.75rem;
        }

        .form-control, .form-select {
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            border-color: #2563eb;
            background-color: #fff;
        }
        .input-group-text {
            border-radius: 0.75rem 0 0 0.75rem;
            background-color: #f1f5f9;
            border-color: #cbd5e1;
            color: #64748b;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            padding: 0.8rem;
            font-weight: 600;
            border-radius: 0.75rem;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.35);
            transition: all 0.3s;
            color: white;
        }
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5);
            color: white;
        }

        .recuperar-link {
            font-size: 0.875rem;
            color: #475569;
            text-decoration: none;
            transition: color 0.2s;
            cursor: pointer;
            font-weight: 500;
        }
        .recuperar-link:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        .social-footer-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 25px;
            z-index: 5;
        }
        .btn-social {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #ffffff;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .btn-social:hover {
            transform: scale(1.15) translateY(-3px);
            color: #ffffff;
        }
        .btn-instagram:hover { background: #e1306c; border-color: #e1306c; box-shadow: 0 8px 20px rgba(225,48,108,0.5); }
        .btn-facebook:hover { background: #1877f2; border-color: #1877f2; box-shadow: 0 8px 20px rgba(24,119,242,0.5); }

        @media (max-width: 576px) {
            .top-buttons {
                top: 15px; left: 15px; right: 15px;
            }
            .btn-custom-glass {
                font-size: 0.75rem; padding: 0.4rem 0.8rem;
            }
        }
    </style>
</head>
<body>

    <!-- BOTONES SUPERIORES (Misión/Visión y Soporte) -->
    <div class="top-buttons">
        <button type="button" class="btn btn-custom-glass px-3 py-2 small" data-bs-toggle="modal" data-bs-target="#modalMisionVision">
            <i class="bi bi-info-circle-fill me-1"></i> Misión y Visión
        </button>
        <button type="button" class="btn btn-custom-glass px-3 py-2 small" data-bs-toggle="modal" data-bs-target="#modalSoporte">
            <i class="bi bi-headset me-1"></i> Soporte
        </button>
    </div>

    <!-- CONTENEDOR PRINCIPAL DE LOGIN -->
    <div class="container px-3 d-flex flex-column align-items-center justify-content-center">
        <div class="card login-card">
            
            <div class="login-header">
                <img src="imagen/logo_gnb.png?v=<?php echo time(); ?>" alt="Escudo Laboratorio">
                <h5 class="fw-bold mb-0">Laboratorio Criminalístico</h5>
                <p class="text-white-50 small mb-0 mt-1">N° 11 (Zulia) &bull; Sistema de Gestión</p>
            </div>

            <div class="card-body p-4 p-md-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show py-2 small rounded-3 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo $error; ?>
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($mensaje_recuperacion)): ?>
                    <div class="alert alert-<?php echo $tipo_alerta_recuperacion; ?> alert-dismissible fade show py-2 small rounded-3 shadow-sm" role="alert">
                        <i class="bi bi-info-circle-fill me-1"></i><?php echo $mensaje_recuperacion; ?>
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="POST">
                    <input type="hidden" name="login" value="1">
                    
                    <div class="mb-3">
                        <label for="usuario" class="form-label fw-semibold small text-secondary">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text border-end-0"><i class="bi bi-envelope-fill"></i></span>
                            <input type="email" class="form-control border-start-0" id="usuario" name="usuario" placeholder="correo@ejemplo.com" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="clave" class="form-label fw-semibold small text-secondary">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text border-end-0"><i class="bi bi-key-fill"></i></span>
                            <input type="password" class="form-control border-start-0 border-end-0" id="clave" name="clave" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary border text-muted bg-light" type="button" id="togglePassword" style="border-top-right-radius: 0.75rem; border-bottom-right-radius: 0.75rem;">
                                <i class="bi bi-eye-slash" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mb-4">
                        <a class="recuperar-link" data-bs-toggle="modal" data-bs-target="#modalRecuperar">
                            <i class="bi bi-question-circle me-1 text-primary"></i>¿Olvidó sus datos o contraseña?
                        </a>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary-custom shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar Sesión
                        </button>
                    </div>
                </form>
            </div>

            <div class="card-footer bg-light text-center py-3 border-0 border-top">
                <small class="text-muted fw-medium"><i class="bi bi-shield-check text-success me-1"></i> Acceso Restringido &bull; Guardia Nacional Bolivariana</small>
            </div>

        </div>

        <!-- BOTONES DE REDES SOCIALES INFERIORES -->
        <div class="social-footer-container">
            <a href="https://www.instagram.com" target="_blank" class="btn-social btn-instagram" title="Síguenos en Instagram">
                <i class="bi bi-instagram"></i>
            </a>
            <a href="https://www.facebook.com" target="_blank" class="btn-social btn-facebook" title="Síguenos en Facebook">
                <i class="bi bi-facebook"></i>
            </a>
        </div>
    </div>

    <!-- MODAL DE SOPORTE TÉCNICO E INCIDENCIAS -->
    <div class="modal fade" id="modalSoporte" tabindex="-1" aria-labelledby="modalSoporteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold" id="modalSoporteLabel">
                        <i class="bi bi-headset text-primary me-2"></i> Centro de Soporte Técnico
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <ul class="nav nav-pills mb-3 justify-content-center" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-semibold small px-4" id="pills-reporte-tab" data-bs-toggle="pill" data-bs-target="#pills-reporte" type="button" role="tab">
                                <i class="bi bi-exclamation-octagon-fill me-1"></i> Reportar Incidencia
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-semibold small px-4" id="pills-contacto-tab" data-bs-toggle="pill" data-bs-target="#pills-contacto" type="button" role="tab">
                                <i class="bi bi-telephone-inbound-fill me-1"></i> Canales de Atención
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-reporte" role="tabpanel">
                            <form action="index.php" method="POST" class="bg-white p-3 p-md-4 rounded-4 shadow-sm">
                                <input type="hidden" name="reportar_incidencia" value="1">
                                <p class="text-muted small mb-3">Envíe un mensaje directo al administrador en caso de que su cuenta esté bloqueada o presente problemas de acceso.</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nombre_incidencia" class="form-label fw-semibold small text-secondary">Nombre Completo</label>
                                        <input type="text" class="form-control bg-light" id="nombre_incidencia" name="nombre_incidencia" placeholder="Ej: Pedro Pérez" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cedula_incidencia" class="form-label fw-semibold small text-secondary">Cédula de Identidad</label>
                                        <input type="text" class="form-control bg-light" id="cedula_incidencia" name="cedula_incidencia" placeholder="Ej: V-12345678" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="correo_incidencia" class="form-label fw-semibold small text-secondary">Correo Electrónico de Contacto</label>
                                        <input type="email" class="form-control bg-light" id="correo_incidencia" name="correo_incidencia" placeholder="tu_correo@ejemplo.com" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tipo_incidencia" class="form-label fw-semibold small text-secondary">Tipo de Falla / Incidencia</label>
                                        <select class="form-select bg-light" id="tipo_incidencia" name="tipo_incidencia" required>
                                            <option value="" selected disabled>Seleccione una opción...</option>
                                            <option value="Cuenta Bloqueada">Cuenta Bloqueada</option>
                                            <option value="Cédula No Registrada">Cédula No Registrada en la BD</option>
                                            <option value="Correo no Coincide">El Correo no coincide con mi Cédula</option>
                                            <option value="Otro Problema">Otro Problema Técnico</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="mensaje_incidencia" class="form-label fw-semibold small text-secondary">Descripción de la Incidencia</label>
                                        <textarea class="form-control bg-light" id="mensaje_incidencia" name="mensaje_incidencia" rows="3" placeholder="Detalle brevemente el inconveniente..." required></textarea>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button type="submit" class="btn btn-primary-custom btn-sm px-4 rounded-pill fw-bold">
                                        <i class="bi bi-send-fill me-1"></i> Enviar Reporte
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="pills-contacto" role="tabpanel">
                            <div class="p-3 bg-white rounded-4 shadow-sm">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check text-primary me-2"></i>Información Directa de Soporte</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 border-start border-primary border-4 h-100">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-envelope-fill text-primary fs-3 me-3"></i>
                                                <div>
                                                    <span class="fw-bold d-block text-dark small">Correo de Soporte</span>
                                                    <a href="mailto:leonernesto609@gmail.com" class="text-decoration-none small text-primary fw-semibold">leonernesto609@gmail.com</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 border-start border-success border-4 h-100">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-whatsapp text-success fs-3 me-3"></i>
                                                <div>
                                                    <span class="fw-bold d-block text-dark small">WhatsApp</span>
                                                    <a href="https://wa.me/584246521645" target="_blank" class="text-decoration-none small text-success fw-semibold">0424-6521645</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0 py-3">
                    <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill fw-semibold" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE MISIÓN Y VISIÓN -->
    <div class="modal fade" id="modalMisionVision" tabindex="-1" aria-labelledby="modalMisionVisionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold" id="modalMisionVisionLabel">
                        <i class="bi bi-shield-fill-check text-primary me-2"></i> Misión y Visión - Laboratorio Criminalístico
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="mb-4 p-3 bg-white rounded-4 shadow-sm border-start border-primary border-4">
                        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-compass-fill me-1"></i> Misión</h6>
                        <p class="text-secondary small mb-0 lh-base">Conducir operaciones militares para el orden interno, cooperar en la defensa nacional, ejercer funciones de policía administrativa y de investigación penal[cite: 1, 2].</p>
                    </div>
                    <div class="p-3 bg-white rounded-4 shadow-sm border-start border-success border-4">
                        <h6 class="fw-bold text-success mb-2"><i class="bi bi-eye-fill me-1"></i> Visión</h6>
                        <p class="text-secondary small mb-0 lh-base">Ser una institución fortalecida y respetada, al servicio del pueblo venezolano, garante de la Constitución y las leyes, con altos estándares de excelencia operacional.</p>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0 py-3">
                    <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill fw-semibold" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PARA RECUPERAR CONTRASEÑA (SOLO V Y E PARA PERSONAS NATURALES) -->
    <div class="modal fade" id="modalRecuperar" tabindex="-1" aria-labelledby="modalRecuperarLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
                <div class="modal-header text-white py-3" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                    <h5 class="modal-title fw-bold" id="modalRecuperarLabel">
                        <i class="bi bi-key-fill me-2"></i> Recuperación de Credenciales
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="index.php" method="POST">
                    <input type="hidden" name="recuperar" value="1">
                    <div class="modal-body p-4 bg-light">
                        <p class="text-muted small mb-3">Ingrese su correo electrónico registrado y su número de cédula para enviarle el enlace de restablecimiento.</p>
                        
                        <div class="mb-3">
                            <label for="correo_recuperacion" class="form-label fw-semibold small text-secondary">Correo Electrónico</label>
                            <input type="email" class="form-control bg-white" id="correo_recuperacion" name="correo_recuperacion" placeholder="correo@ejemplo.com" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-secondary">Cédula de Identidad</label>
                            <div class="input-group">
                                <select class="form-select fw-bold text-center bg-white" name="tipo_cedula_rec" style="max-width: 75px;">
                                    <option value="V" selected>V</option>
                                    <option value="E">E</option>
                                </select>
                                <input type="text" class="form-control bg-white" id="cedula_recuperacion" name="cedula_recuperacion" placeholder="12345678" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="9" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-0 py-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary-custom btn-sm px-4 rounded-pill fw-bold">Enviar Enlace</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const claveInput = document.getElementById('clave');
        const eyeIcon = document.getElementById('eyeIcon');

        togglePassword.addEventListener('click', function () {
            const type = claveInput.getAttribute('type') === 'password' ? 'text' : 'password';
            claveInput.setAttribute('type', type);
            eyeIcon.classList.toggle('bi-eye');
            eyeIcon.classList.toggle('bi-eye-slash');
        });
    </script>

    <!-- SCRIPT LOCAL (Funciona 100% sin internet) -->
    <script src="js/bootstrap.bundle.min.js"></script>
    
</body>
</html>