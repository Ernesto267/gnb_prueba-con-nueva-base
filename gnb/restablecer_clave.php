<?php
session_start();
require_once 'conexion.php';

$mensaje = '';
$tipo_alerta = '';
$token_valido = false;
$usuario_id = null;

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);

    try {
        $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE token_recuperacion = ? AND token_expiracion > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $token_valido = true;
            $usuario_id = $usuario['id'];
        } else {
            $mensaje = "El enlace de recuperación es inválido o ha expirado (tiempo límite: 1 hora).";
            $tipo_alerta = "danger";
        }
    } catch (PDOException $e) {
        $mensaje = "Error en el sistema: " . $e->getMessage();
        $tipo_alerta = "danger";
    }
} else {
    $mensaje = "No se ha proporcionado ningún token de recuperación.";
    $tipo_alerta = "danger";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_clave'])) {
    $user_id_post = $_POST['usuario_id'] ?? null;
    $nueva_clave = $_POST['nueva_clave'] ?? '';
    $confirma_clave = $_POST['confirma_clave'] ?? '';

    if (!empty($nueva_clave) && !empty($confirma_clave)) {
        if ($nueva_clave === $confirma_clave) {
            if (strlen($nueva_clave) >= 6) {
                try {
                    $password_encriptada = password_hash($nueva_clave, PASSWORD_DEFAULT);

                    $update = $pdo->prepare("UPDATE usuarios SET password = ?, token_recuperacion = NULL, token_expiracion = NULL WHERE id = ?");
                    $update->execute([$password_encriptada, $user_id_post]);

                    $mensaje = "¡Contraseña actualizada con éxito! Ya puedes iniciar sesión de forma segura.";
                    $tipo_alerta = "success";
                    $token_valido = false; 
                } catch (PDOException $e) {
                    $mensaje = "Error al actualizar la contraseña en la base de datos.";
                    $tipo_alerta = "danger";
                }
            } else {
                $mensaje = "La contraseña debe tener al menos 6 caracteres.";
                $tipo_alerta = "warning";
                $token_valido = true;
                $usuario_id = $user_id_post;
            }
        } else {
            $mensaje = "Las contraseñas no coinciden.";
            $tipo_alerta = "warning";
            $token_valido = true;
            $usuario_id = $user_id_post;
        }
    } else {
        $mensaje = "Por favor complete ambos campos.";
        $tipo_alerta = "warning";
        $token_valido = true;
        $usuario_id = $user_id_post;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GNB - Restablecer Contraseña</title>
    <!-- ENLACES LOCALES (Funcionan 100% sin internet) -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(circle at center, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .card-restablecer {
            width: 100%;
            max-width: 440px;
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            background: #ffffff;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            border-color: #2563eb;
            background-color: #fff !important;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            font-weight: 600;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            transition: all 0.3s;
            color: white;
        }
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #1d4ed8 100%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
            color: white;
        }
        .password-strength-bar {
            height: 4px;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
    </style>
</head>
<body>

    <div class="container px-3">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card card-restablecer p-4 p-md-4">
                    
                    <div class="text-center mb-4">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 75px; height: 75px;">
                            <i class="bi bi-shield-lock-fill fs-2"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">Restablecer Clave</h4>
                        <p class="text-muted small">Configure su nueva contraseña de acceso seguro</p>
                    </div>

                    <?php if (!empty($mensaje)): ?>
                        <div class="alert alert-<?php echo $tipo_alerta; ?> small text-start mb-3 rounded-3 shadow-sm" role="alert">
                            <i class="bi bi-info-circle-fill me-1"></i><?php echo $mensaje; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($token_valido): ?>
                        <form action="restablecer_clave.php?token=<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>" method="POST">
                            <input type="hidden" name="actualizar_clave" value="1">
                            <input type="hidden" name="usuario_id" value="<?php echo $usuario_id; ?>">

                            <!-- NUEVA CONTRASEÑA CON BOTÓN DE VISIBILIDAD -->
                            <div class="mb-3">
                                <label for="nueva_clave" class="form-label fw-semibold small text-secondary">Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control border-start-0 border-end-0 bg-light" id="nueva_clave" name="nueva_clave" placeholder="Mínimo 6 caracteres" required>
                                    <button class="btn btn-outline-light border text-muted bg-light" type="button" id="toggleNuevaClave">
                                        <i class="bi bi-eye-slash" id="eyeIconNueva"></i>
                                    </button>
                                </div>
                                <!-- Indicador visual de seguridad -->
                                <div class="progress mt-2" style="height: 4px;">
                                    <div id="strengthBar" class="progress-bar password-strength-bar bg-danger" role="progressbar" style="width: 0%;"></div>
                                </div>
                                <small id="strengthText" class="text-muted" style="font-size: 0.75rem;">Seguridad de la clave</small>
                            </div>

                            <!-- CONFIRMAR CONTRASEÑA CON BOTÓN DE VISIBILIDAD -->
                            <div class="mb-4">
                                <label for="confirma_clave" class="form-label fw-semibold small text-secondary">Confirmar Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key-fill"></i></span>
                                    <input type="password" class="form-control border-start-0 border-end-0 bg-light" id="confirma_clave" name="confirma_clave" placeholder="Repite la contraseña" required>
                                    <button class="btn btn-outline-light border text-muted bg-light" type="button" id="toggleConfirmaClave">
                                        <i class="bi bi-eye-slash" id="eyeIconConfirma"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary-custom fw-bold py-2.5">
                                    <i class="bi bi-check-circle me-1"></i> Guardar Nueva Contraseña
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="d-grid mt-3">
                            <a href="index.php" class="btn btn-outline-secondary fw-bold py-2.5 rounded-3">
                                <i class="bi bi-arrow-left me-1"></i> Volver al Inicio de Sesión
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <!-- Script JavaScript para Ver/Ocultar y Medidor de Fuerza -->
    <script>
        function configurarOcultarMostrar(toggleId, inputId, iconId) {
            const btn = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (btn && input && icon) {
                btn.addEventListener('click', function () {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    
                    if (type === 'text') {
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    } else {
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    }
                });
            }
        }

        configurarOcultarMostrar('toggleNuevaClave', 'nueva_clave', 'eyeIconNueva');
        configurarOcultarMostrar('toggleConfirmaClave', 'confirma_clave', 'eyeIconConfirma');

        // Medidor de seguridad de contraseña interactivo
        const nuevaClaveInput = document.getElementById('nueva_clave');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        if (nuevaClaveInput) {
            nuevaClaveInput.addEventListener('input', function() {
                const val = nuevaClaveInput.value;
                let strength = 0;
                
                if (val.length >= 6) strength += 33;
                if (val.length >= 10) strength += 33;
                if (/[A-Z]/.test(val) && /[0-9]/.test(val)) strength += 34;

                strengthBar.style.width = strength + '%';
                
                if (strength <= 33) {
                    strengthBar.className = 'progress-bar password-strength-bar bg-danger';
                    strengthText.innerText = 'Seguridad baja';
                } else if (strength <= 66) {
                    strengthBar.className = 'progress-bar password-strength-bar bg-warning';
                    strengthText.innerText = 'Seguridad media';
                } else {
                    strengthBar.className = 'progress-bar password-strength-bar bg-success';
                    strengthText.innerText = 'Seguridad alta';
                }
            });
        }
    </script>

    <!-- SCRIPT LOCAL (Funciona 100% sin internet) -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <?php include 'inactivity_check.php'; ?>
</body>
</html>