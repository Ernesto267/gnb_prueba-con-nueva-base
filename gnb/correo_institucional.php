<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$rol_actual = $_SESSION['usuario_rol'] ?? 'usuario';

if ($rol_actual !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Asegurar tabla del historial
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS comunicados_enviados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        asunto VARCHAR(255) NOT NULL,
        cuerpo TEXT NOT NULL,
        total_destinatarios INT NOT NULL,
        incluye_adjunto TINYINT(1) DEFAULT 0,
        fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_comunicado_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
} catch (PDOException $e) {}

$mensaje_alerta = "";
$tipo_alerta = "info";

// Procesar Eliminación de Comunicado del Historial
if (isset($_GET['eliminar_id'])) {
    $id_eliminar = intval($_GET['eliminar_id']);
    try {
        $stmt_del = $pdo->prepare("DELETE FROM comunicados_enviados WHERE id = ?");
        $stmt_del->execute([$id_eliminar]);
        header("Location: correo_institucional.php?eliminado=1");
        exit();
    } catch (PDOException $e) {
        $mensaje_alerta = "Error al eliminar el registro del historial.";
        $tipo_alerta = "danger";
    }
}

if (isset($_GET['eliminado'])) {
    $mensaje_alerta = "Registro eliminado de la bandeja de salida con éxito.";
    $tipo_alerta = "success";
}

if (isset($_POST['enviar_comunicado'])) {
    $asunto_comunicado  = trim($_POST['asunto_comunicado'] ?? '');
    $mensaje_comunicado = trim($_POST['mensaje_comunicado'] ?? '');
    $destinatario_tipo  = trim($_POST['destinatario_tipo'] ?? 'todos'); 
    $funcionarios_ids   = $_POST['funcionarios_ids'] ?? [];

    if (!empty($asunto_comunicado) && !empty($mensaje_comunicado)) {
        try {
            if ($destinatario_tipo === 'todos') {
                $stmt_corr = $pdo->query("SELECT correo, CONCAT(nombres, ' ', apellidos) AS nombre_completo FROM personal WHERE correo IS NOT NULL AND correo != ''");
                $destinatarios = $stmt_corr->fetchAll(PDO::FETCH_ASSOC);
            } else {
                if (!empty($funcionarios_ids) && is_array($funcionarios_ids)) {
                    $funcionarios_ids = array_map('intval', $funcionarios_ids);
                    $placeholders = implode(',', array_fill(0, count($funcionarios_ids), '?'));
                    $stmt_corr = $pdo->prepare("SELECT correo, CONCAT(nombres, ' ', apellidos) AS nombre_completo FROM personal WHERE id IN ($placeholders) AND correo IS NOT NULL AND correo != ''");
                    $stmt_corr->execute($funcionarios_ids);
                    $destinatarios = $stmt_corr->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $destinatarios = [];
                }
            }

            if (count($destinatarios) > 0) {
                $ruta_archivo_adjunto = '';
                $tiene_adjunto = 0;
                if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] === UPLOAD_ERR_OK) {
                    $nombre_temporal = $_FILES['archivo_adjunto']['tmp_name'];
                    $nombre_original = basename($_FILES['archivo_adjunto']['name']);
                    
                    $directorio_subidas = 'uploads_temp/';
                    if (!is_dir($directorio_subidas)) {
                        mkdir($directorio_subidas, 0755, true);
                    }
                    
                    $ruta_archivo_adjunto = $directorio_subidas . time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "", $nombre_original);
                    move_uploaded_file($nombre_temporal, $ruta_archivo_adjunto);
                    $tiene_adjunto = 1;
                }

                require_once 'PHPMailer/Exception.php';
                require_once 'PHPMailer/PHPMailer.php';
                require_once 'PHPMailer/SMTP.php';

                $enviados_exito = 0;
                $usuario_id_actual = $_SESSION['usuario_id'];
                $error_smtp = "";

                foreach ($destinatarios as $dest) {
                    $mail_aviso = new PHPMailer\PHPMailer\PHPMailer(true);
                    try {
                        $mail_aviso->isSMTP();
                        $mail_aviso->Host       = 'smtp.gmail.com';             
                        $mail_aviso->SMTPAuth   = true;                           
                        $mail_aviso->Username   = 'leonernesto609@gmail.com';     
                        $mail_aviso->Password   = 'bsybhvuvimjqmbgr';             
                        $mail_aviso->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; 
                        $mail_aviso->Port       = 587;                            
                        $mail_aviso->Timeout    = 15; 

                        $mail_aviso->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            )
                        );

                        $mail_aviso->setFrom('leonernesto609@gmail.com', 'Laboratorio Criminalístico N° 11'); 
                        $mail_aviso->addAddress($dest['correo'], $dest['nombre_completo'] ?? 'Funcionario');     

                        if (!empty($ruta_archivo_adjunto) && file_exists($ruta_archivo_adjunto)) {
                            $mail_aviso->addAttachment($ruta_archivo_adjunto, basename($nombre_original));
                        }

                        $mail_aviso->isHTML(true);
                        $mail_aviso->Subject = 'Comunicado Oficial: ' . $asunto_comunicado;
                        $mail_aviso->Body    = "
                            <div style='font-family: 'Segoe UI', Arial, sans-serif; max-width: 650px; margin: 0 auto; padding: 30px; border: 1px solid #cbd5e1; border-radius: 16px; background-color: #f8fafc; box-shadow: 0 10px 25px rgba(0,0,0,0.05);'>
                                <div style='text-align: center; margin-bottom: 25px;'>
                                    <div style='display: inline-block; padding: 10px; background: #ffffff; border-radius: 50%; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 12px;'>
                                        <img src='http://localhost/gnb/imagen/logo_gnb.png' alt='Logo Laboratorio' style='width: 110px; height: 110px; object-fit: contain; display: block;'>
                                    </div>
                                    <h2 style='color: #1e3a8a; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.5px;'>Laboratorio Criminalístico N° 11</h2>
                                    <p style='color: #64748b; font-size: 13px; margin: 4px 0 0 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;'>Estado Zulia &bull; Comunicación Oficial</p>
                                </div>
                                <div style='border-top: 2px solid #e2e8f0; margin: 20px 0;'></div>
                                <div style='background: #ffffff; padding: 22px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px;'>
                                    <span style='background: #eff6ff; color: #1d4ed8; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 50rem; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; margin-bottom: 8px;'>Asunto Oficial</span>
                                    <h3 style='color: #0f172a; margin: 0 0 12px 0; font-size: 18px;'>" . htmlspecialchars($asunto_comunicado) . "</h3>
                                    <div style='color: #334155; line-height: 1.7; font-size: 14px;'>
                                        " . nl2br(htmlspecialchars($mensaje_comunicado)) . "
                                    </div>
                                </div>
                                " . (!empty($ruta_archivo_adjunto) ? "<div style='background: #eff6ff; border: 1px solid #bfdbfe; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; color: #1d4ed8;'><i style='margin-right: 6px;'>📎</i> <strong>Documento adjunto incluido</strong> en esta directiva.</div>" : "") . "
                                <div style='border-top: 1px solid #e2e8f0; margin: 20px 0;'></div>
                                <p style='font-size: 11px; color: #94a3b8; text-align: center; margin: 0; line-height: 1.5;'>
                                    Este es un mensaje institucional automatizado del Sistema de Gestión GNB.<br>Por favor, no responda directamente a esta dirección de correo.
                                </p>
                            </div>
                        ";

                        if ($mail_aviso->send()) {
                            $enviados_exito++;
                        }
                    } catch (Exception $e) {
                        $error_smtp = $mail_aviso->ErrorInfo;
                        continue; 
                    }
                }

                if (!empty($ruta_archivo_adjunto) && file_exists($ruta_archivo_adjunto)) {
                    @unlink($ruta_archivo_adjunto);
                }

                if ($enviados_exito > 0) {
                    $stmt_hist = $pdo->prepare("INSERT INTO comunicados_enviados (usuario_id, asunto, cuerpo, total_destinatarios, incluye_adjunto) VALUES (?, ?, ?, ?, ?)");
                    $stmt_hist->execute([$usuario_id_actual, $asunto_comunicado, $mensaje_comunicado, $enviados_exito, $tiene_adjunto]);

                    $mensaje_alerta = "¡Comunicado oficial enviado con éxito a $enviados_exito funcionario(s)!";
                    $tipo_alerta = "success";
                } else {
                    $mensaje_alerta = "Error al enviar el correo. Detalles técnicos: " . ($error_smtp ?: "No se pudo conectar al servidor SMTP o el destinatario no tiene un correo válido.");
                    $tipo_alerta = "danger";
                }
            } else {
                $mensaje_alerta = "No se seleccionaron destinatarios válidos o los funcionarios no tienen correo registrado.";
                $tipo_alerta = "warning";
            }
        } catch (PDOException $e) {
            $mensaje_alerta = "Error de base de datos.";
            $tipo_alerta = "danger";
        }
    } else {
        $mensaje_alerta = "Por favor, complete el asunto y el mensaje.";
        $tipo_alerta = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Criminalístico N° 11 (Zulia) - Correo Institucional</title>
    <script>
        const savedTheme = localStorage.getItem('gnb_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <style>
        [data-theme="dark"] {
            --bg-body: #070b14; --bg-navbar: #0f172a; --text-main: #f8fafc; --text-muted: #cbd5e1;
            --card-bg: #111827; --card-border: rgba(255, 255, 255, 0.12); --card-shadow: 0 20px 40px rgba(0,0,0,0.4);
            --input-bg: #0b1120; --section-bg: #1e293b; --hover-item: rgba(59, 130, 246, 0.15);
            --badge-grado-bg: rgba(59, 130, 246, 0.2); --badge-grado-text: #93c5fd; --badge-grado-border: rgba(59, 130, 246, 0.4);
            --table-hover: #1f293d;
            --file-btn-bg: #1e293b; --file-btn-hover: #334155; --file-btn-text: #f8fafc;
        }
        [data-theme="light"] {
            --bg-body: #e2e8f0; --bg-navbar: #ffffff; --text-main: #0f172a; --text-muted: #475569;
            --card-bg: #ffffff; --card-border: #cbd5e1; --card-shadow: 0 15px 35px rgba(15, 23, 42, 0.12);
            --input-bg: #ffffff; --section-bg: #f8fafc; --hover-item: rgba(59, 130, 246, 0.08);
            --badge-grado-bg: #eff6ff; --badge-grado-text: #1d4ed8; --badge-grado-border: #bfdbfe;
            --table-hover: #f1f5f9;
            --file-btn-bg: #e2e8f0; --file-btn-hover: #cbd5e1; --file-btn-text: #0f172a;
        }
        body { background-color: var(--bg-body); min-height: 100vh; font-family: 'Segoe UI', system-ui, sans-serif; color: var(--text-main); transition: background 0.3s ease, color 0.3s ease; }
        .text-muted { color: var(--text-muted) !important; }
        .navbar-gnb { background: var(--bg-navbar); border-bottom: 1.5px solid var(--card-border); box-shadow: 0 4px 25px rgba(0,0,0,0.1); }
        .navbar-brand img { width: 140px; height: 140px; object-fit: contain; position: relative; top: 4px; margin-top: -40px; margin-bottom: -40px; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3)); }
        .btn-nav-custom { color: var(--text-main) !important; border-color: var(--card-border) !important; background: transparent; transition: all 0.2s ease; }
        .btn-nav-custom:hover { background: rgba(59, 130, 246, 0.1); color: #3b82f6 !important; }
        .container-limited { max-width: 1750px; margin: 0 auto; }
        .main-card { border-radius: 1.5rem; box-shadow: var(--card-shadow); background: var(--card-bg); border: 1.5px solid var(--card-border); color: var(--text-main); }
        .form-control, .form-select { background-color: var(--input-bg) !important; color: var(--text-main) !important; border: 1.5px solid var(--card-border) !important; border-radius: 0.85rem; padding: 0.7rem 1rem; font-size: 0.92rem; }
        .form-control::placeholder, .form-select::placeholder { color: #94a3b8 !important; opacity: 1; }
        .form-control:focus, .form-select:focus { border-color: #3b82f6 !important; box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.2) !important; background-color: var(--input-bg) !important; color: var(--text-main) !important; }
        
        input[type="file"].form-control::file-selector-button {
            background-color: var(--file-btn-bg) !important;
            color: var(--file-btn-text) !important;
            border: 1px solid var(--card-border) !important;
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            margin-right: 1rem;
            transition: background 0.2s ease;
        }
        input[type="file"].form-control::file-selector-button:hover {
            background-color: var(--file-btn-hover) !important;
        }

        .box-section-custom { background-color: var(--section-bg); border: 1.5px solid var(--card-border); border-radius: 1.25rem; padding: 1.25rem; }
        
        /* Estilo mejorado y ordenado para las tarjetas de funcionarios */
        .funcionario-item { background: var(--input-bg); border: 1.5px solid var(--card-border); border-radius: 0.85rem; transition: all 0.2s ease; cursor: pointer; align-items: center !important; }
        .funcionario-item:hover { border-color: #3b82f6; background: var(--hover-item); }
        .badge-grado-custom { background-color: var(--badge-grado-bg) !important; color: var(--badge-grado-text) !important; border: 1px solid var(--badge-grado-border) !important; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 50rem; font-size: 0.7rem; display: inline-block; vertical-align: middle; }

        .table { color: var(--text-main); margin-bottom: 0; font-size: 0.88rem; }
        .table > :not(caption) > * > * { background-color: transparent; color: var(--text-main); border-bottom-color: var(--card-border); padding: 0.8rem 0.5rem; }
        .table-hover > tbody > tr:hover > * { background-color: var(--table-hover) !important; color: var(--text-main); }
        .table th { background: linear-gradient(135deg, #1e3a8a, #1e40af) !important; color: #ffffff !important; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; padding: 0.8rem 0.5rem; border: none; text-align: center; }
        .cursor-pointer { cursor: pointer; }

        .toast-floating-container {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 1080;
            width: auto;
        }
        .toast-custom {
            border-radius: 1rem !important;
            border: 1.5px solid rgba(255, 255, 255, 0.15) !important;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            backdrop-filter: blur(8px);
            font-weight: 600;
            white-space: nowrap !important;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-gnb shadow-lg py-3 sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold fs-5 d-flex align-items-center" href="dashboard.php" style="color: var(--text-main);">
                <img src="imagen/logo_gnb.png?v=<?php echo time(); ?>" alt="Escudo GNB" class="me-3"> Laboratorio Criminalístico N<sup>o</sup> 11 (Zulia)
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="dashboard.php" class="btn btn-nav-custom btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Volver al Panel
                </a>
            </div>
        </div>
    </nav>

    <?php if (!empty($mensaje_alerta)): ?>
        <div class="toast-floating-container" id="alertaFlotante">
            <div class="alert alert-<?php echo $tipo_alerta; ?> alert-dismissible fade show toast-custom d-flex align-items-center justify-content-between py-3 px-4 shadow-lg mb-0" role="alert">
                <div class="d-flex align-items-center pe-4">
                    <i class="bi bi-info-circle-fill fs-5 me-2.5 flex-shrink-0"></i>
                    <span class="small"><?php echo $mensaje_alerta; ?></span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <script>
            setTimeout(function() {
                const alerta = document.getElementById('alertaFlotante');
                if (alerta) {
                    alerta.classList.remove('show');
                    setTimeout(() => alerta.remove(), 300);
                }
            }, 5000);
        </script>
    <?php endif; ?>

    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="container-limited">

            <div class="row g-4">
                <div class="col-xl-7">
                    <div class="card main-card p-4 p-md-5">
                        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom" style="border-color: var(--card-border) !important;">
                            <div class="p-3 rounded-4 text-white fs-2 shadow-sm d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); width: 60px; height: 60px;">
                                <i class="bi bi-envelope-paper-heart-fill"></i>
                            </div>
                            <div>
                                <h3 class="fw-extrabold mb-1" style="color: var(--text-main); letter-spacing: -0.5px;">Correo Institucional</h3>
                                <p class="mb-0 text-muted small">Despacho automatizado de circulares y directivas oficiales.</p>
                            </div>
                        </div>

                        <form id="formCorreo" action="correo_institucional.php" method="POST" enctype="multipart/form-data">
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="box-section-custom">
                                        <label for="destinatario_tipo" class="form-label fw-bold text-primary mb-2">
                                            <i class="bi bi-people-fill me-1"></i> Modo de Destinatarios
                                        </label>
                                        <select class="form-select mb-3 fw-semibold" id="destinatario_tipo" name="destinatario_tipo" onchange="toggleDestinatarioTipo(this.value)">
                                            <option value="todos" selected>Todos los funcionarios</option>
                                            <option value="especificos">Seleccionar funcionarios específicos</option>
                                        </select>

                                        <div id="infoModoMasivo" class="p-3 rounded-4 text-center border border-info border-opacity-25 mt-2" style="background: rgba(59, 130, 246, 0.08);">
                                            <h6 class="fw-bold text-primary mb-1"><i class="bi bi-broadcast me-1"></i> Envío Masivo Activo</h6>
                                            <p class="small text-muted mb-0" style="font-size: 0.8rem;">
                                                El comunicado oficial se despachará a <strong>todos</strong> los funcionarios con correo registrado.
                                            </p>
                                        </div>

                                        <div id="divFuncionariosEspecificos" style="display: none;" class="mt-2 flex-column">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label small fw-bold mb-0">Seleccione los funcionarios:</label>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary py-1 px-2 fw-semibold" onclick="marcarTodosCheck(true)">Todos</button>
                                                    <button type="button" class="btn btn-outline-secondary py-1 px-2 fw-semibold" onclick="marcarTodosCheck(false)">Ninguno</button>
                                                </div>
                                            </div>
                                            
                                            <div class="input-group input-group-sm mb-2">
                                                <span class="input-group-text bg-transparent text-muted"><i class="bi bi-search"></i></span>
                                                <input type="text" class="form-control form-control-sm" id="buscarFuncionario" placeholder="Buscar por nombre, cédula o grado..." oninput="filtrarFuncionarios(this.value)">
                                            </div>

                                            <div class="p-2 border rounded-3 overflow-y-auto shadow-inner" style="max-height: 250px; background: var(--card-bg); border-color: var(--card-border) !important;">
                                                <?php 
                                                try {
                                                    $stmt_pers_list = $pdo->query("SELECT id, cedula, grado, nombres, apellidos, correo FROM personal WHERE correo IS NOT NULL AND correo != '' ORDER BY apellidos, nombres");
                                                    while ($func = $stmt_pers_list->fetch(PDO::FETCH_ASSOC)) {
                                                        $nombre_completo = htmlspecialchars($func['apellidos'] . ' ' . $func['nombres']);
                                                        $grado_func = htmlspecialchars($func['grado'] ?? 'S/G');
                                                        $cedula_func = htmlspecialchars($func['cedula']);
                                                        $correo_func = htmlspecialchars($func['correo']);
                                                        $search_string = "$nombre_completo $grado_func $cedula_func $correo_func";
                                                        
                                                        echo '
                                                        <div class="form-check p-2.5 mb-2 funcionario-item d-flex align-items-center gap-3" data-search="'.$search_string.'">
                                                            <input class="form-check-input funcionario-checkbox flex-shrink-0 ms-0" type="checkbox" name="funcionarios_ids[]" value="'.$func['id'].'" id="func_chk_'.$func['id'].'" style="width: 1.25rem; height: 1.25rem;">
                                                            <label class="form-check-label w-100 cursor-pointer lh-sm text-break mb-0" for="func_chk_'.$func['id'].'">
                                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                                    <span class="badge-grado-custom">'.$grado_func.'</span>
                                                                    <span class="fw-bold fs-6 text-wrap" style="color: var(--text-main);">'.$nombre_completo.'</span>
                                                                </div>
                                                                <div class="text-muted text-wrap" style="font-size: 0.76rem;">
                                                                    <i class="bi bi-card-text me-1"></i>C.I: '.$cedula_func.' &bull; <i class="bi bi-envelope me-1"></i>'.$correo_func.'
                                                                </div>
                                                            </label>
                                                        </div>';
                                                    }
                                                } catch (Exception $e) {}
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="asunto_comunicado" class="form-label fw-semibold">Asunto del Comunicado</label>
                                    <input type="text" class="form-control" id="asunto_comunicado" name="asunto_comunicado" placeholder="Ej: Circular Oficial N° 05 - Convocatoria" required>
                                </div>

                                <div class="col-12">
                                    <label for="mensaje_comunicado" class="form-label fw-semibold">Cuerpo del Mensaje / Instrucciones</label>
                                    <textarea class="form-control" id="mensaje_comunicado" name="mensaje_comunicado" rows="6" placeholder="Escriba aquí el contenido detallado del comunicado oficial..." required oninput="actualizarContador(this)"></textarea>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small id="contadorCaracteres" class="text-muted fw-semibold">0 caracteres | 0 palabras</small>
                                        <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3 fw-bold" onclick="abrirVistaPrevia()">
                                            <i class="bi bi-eye-fill me-1"></i> Vista Previa del Correo
                                        </button>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="archivo_adjunto" class="form-label fw-semibold">Documento o Archivo Adjunto (Opcional)</label>
                                    <input type="file" class="form-control" id="archivo_adjunto" name="archivo_adjunto">
                                    <div class="form-text small" style="color: var(--text-muted);">Formatos compatibles: PDF, Word, Excel o Imágenes (Máx. 10 MB).</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top" style="border-color: var(--card-border) !important;">
                                <a href="dashboard.php" class="btn btn-outline-secondary px-4 py-2 fw-bold rounded-pill">Cancelar</a>
                                <button type="submit" name="enviar_comunicado" value="1" id="btnEnviar" class="btn btn-primary px-5 py-2 fw-bold rounded-pill shadow-lg" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border:none;">
                                    <span><i class="bi bi-send-fill me-2"></i> Despachar Comunicado</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card main-card p-4 d-flex flex-column" style="height: 100%;">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom" style="border-color: var(--card-border) !important;">
                            <div>
                                <h4 class="fw-bold mb-1" style="color: var(--text-main);"><i class="bi bi-clock-history me-2 text-primary"></i> Bandeja de Salida</h4>
                                <p class="mb-0 text-muted small">Historial y auditoría de comunicados emitidos.</p>
                            </div>
                        </div>

                        <div class="table-responsive flex-grow-1" style="max-height: 700px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead style="position: sticky; top: 0; z-index: 5;">
                                    <tr>
                                        <th class="text-center">Fecha</th>
                                        <th>Asunto</th>
                                        <th class="text-center">Dest.</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    try {
                                        $stmt_hist = $pdo->query("
                                            SELECT c.*, CONCAT(u.nombre, ' ', u.apellido) AS nombre_usuario 
                                            FROM comunicados_enviados c 
                                            LEFT JOIN usuarios u ON c.usuario_id = u.id 
                                            ORDER BY c.fecha_envio DESC
                                        ");
                                        $historial = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        if (count($historial) > 0) {
                                            foreach ($historial as $idx => $com) {
                                                $fecha_formateada = date('d/m/Y h:i A', strtotime($com['fecha_envio']));
                                                $asunto_com = htmlspecialchars($com['asunto']);
                                                $remitente_com = htmlspecialchars($com['nombre_usuario'] ?? 'Administrador');
                                                $cuerpo_texto = $com['cuerpo']; 
                                                $total_dest = $com['total_destinatarios'];
                                                $id_comunicado = $com['id'];

                                                echo '
                                                <tr>
                                                    <td class="text-center text-nowrap small text-muted">'.$fecha_formateada.'</td>
                                                    <td class="fw-semibold text-truncate" style="max-width: 140px; color: var(--text-main);" title="'.$asunto_com.'">'.$asunto_com.'</td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2">'.$total_dest.'</span>
                                                    </td>
                                                    <td class="text-center text-nowrap">
                                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 fw-semibold me-1" title="Ver Detalle" onclick="verDetalleHistorial(' . htmlspecialchars(json_encode($asunto_com), ENT_QUOTES) . ', ' . htmlspecialchars(json_encode($cuerpo_texto), ENT_QUOTES) . ', \'' . $fecha_formateada . '\', \'' . $remitente_com . '\')">
                                                            <i class="bi bi-file-text"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1 fw-semibold" title="Eliminar del Historial" onclick="confirmarEliminacion('.$id_comunicado.', \'' . addslashes($asunto_com) . '\')">
                                                            <i class="bi bi-trash-fill"></i>
                                                        </button>
                                                    </td>
                                                </tr>';
                                            }
                                        } else {
                                            echo '
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                                    No hay comunicados registrados.
                                                </td>
                                            </tr>';
                                        }
                                    } catch (Exception $e) {
                                        echo '<tr><td colspan="4" class="text-center text-danger py-4">Error al cargar historial.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL VISTA PREVIA -->
    <div class="modal fade" id="modalVistaPrevia" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: var(--card-bg); color: var(--text-main);">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e3a8a, #1e40af);">
                    <h5 class="modal-title fw-bold fs-6"><i class="bi bi-eye-fill me-2"></i>Vista Previa del Correo HTML</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div style="font-family: 'Segoe UI', Arial, sans-serif; max-width: 650px; margin: 0 auto; padding: 30px; border: 1px solid #cbd5e1; border-radius: 16px; background-color: #f8fafc; box-shadow: 0 10px 25px rgba(0,0,0,0.05); color: #334155;">
                        <div style="text-align: center; margin-bottom: 25px;">
                            <div style="display: inline-block; padding: 10px; background: #ffffff; border-radius: 50%; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 12px;">
                                <img src="http://localhost/gnb/imagen/logo_gnb.png" alt="Logo Laboratorio" style="width: 110px; height: 110px; object-fit: contain; display: block;">
                            </div>
                            <h2 style="color: #1e3a8a; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.5px;">Laboratorio Criminalístico N° 11</h2>
                            <p style="color: #64748b; font-size: 13px; margin: 4px 0 0 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Estado Zulia &bull; Comunicación Oficial</p>
                        </div>
                        <div style="border-top: 2px solid #e2e8f0; margin: 20px 0;"></div>
                        <div style="background: #ffffff; padding: 22px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                            <span style="background: #eff6ff; color: #1d4ed8; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 50rem; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; margin-bottom: 8px;">Asunto Oficial</span>
                            <h3 id="previewAsunto" style="color: #0f172a; margin: 0 0 12px 0; font-size: 18px;">Asunto: (Sin asunto)</h3>
                            <div id="previewMensaje" style="color: #334155; line-height: 1.7; font-size: 14px; white-space: pre-wrap;">
                                (Escriba un mensaje para visualizarlo aquí...)
                            </div>
                        </div>
                        <div id="previewAdjuntoAviso" style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; color: #1d4ed8; display: none;">
                            <i>📎</i> <strong>Documento adjunto incluido</strong> en esta directiva.
                        </div>
                        <div style="border-top: 1px solid #e2e8f0; margin: 20px 0;"></div>
                        <p style="font-size: 11px; color: #94a3b8; text-align: center; margin: 0; line-height: 1.5;">
                            Este es un mensaje institucional automatizado del Sistema de Gestión GNB.<br>Por favor, no responda directamente a esta dirección de correo.
                        </p>
                    </div>
                </div>
                <div class="modal-footer border-0 py-3 px-4" style="background: var(--card-bg);">
                    <button type="button" class="btn btn-secondary rounded-pill fw-bold px-4" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DETALLE HISTORIAL -->
    <div class="modal fade" id="modalDetalleHistorial" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: var(--card-bg); color: var(--text-main);">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e3a8a, #1e40af);">
                    <h5 class="modal-title fw-bold fs-6"><i class="bi bi-clock-history me-2"></i>Detalle de Comunicado Enviado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <small class="text-muted d-block">Asunto:</small>
                        <h5 id="histAsunto" class="fw-bold text-primary mb-1"></h5>
                        <small class="text-muted"><i class="bi bi-person-fill me-1"></i>Enviado por: <strong id="histRemitente"></strong> | <i class="bi bi-calendar-event me-1"></i>Fecha: <strong id="histFecha"></strong></small>
                    </div>
                    <hr style="border-color: var(--card-border);">
                    <small class="text-muted d-block mb-2">Contenido enviado:</small>
                    <div id="histCuerpo" class="p-3 rounded-3" style="background: var(--input-bg); border: 1.5px solid var(--card-border); white-space: pre-wrap; color: var(--text-main); font-size: 0.95rem;"></div>
                </div>
                <div class="modal-footer border-0 py-3 px-4" style="background: var(--card-bg);">
                    <button type="button" class="btn btn-secondary rounded-pill fw-bold px-4" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PERSONALIZADO DE CONFIRMACIÓN DE ELIMINACIÓN -->
    <div class="modal fade" id="modalConfirmarEliminacion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: var(--card-bg); color: var(--text-main);">
                <div class="modal-header bg-danger text-white border-0 py-3 px-4">
                    <h5 class="modal-title fw-bold fs-6"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="text-danger mb-3">
                        <i class="bi bi-trash3 fs-1"></i>
                    </div>
                    <h5 class="fw-bold mb-2">¿Deseas eliminar este comunicado?</h5>
                    <p class="text-muted small mb-1">Asunto: <strong id="lblAsuntoEliminar" class="text-main"></strong></p>
                    <p class="text-muted small mb-0">Esta acción borrará permanentemente este registro del historial de salida.</p>
                </div>
                <div class="modal-footer border-0 py-3 px-4 justify-content-center gap-2" style="background: var(--card-bg);">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="btnEjecutarEliminar" class="btn btn-danger rounded-pill px-4 fw-semibold">Sí, Eliminar</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleDestinatarioTipo(valor) {
            const divEspecificos = document.getElementById('divFuncionariosEspecificos');
            const infoMasivo = document.getElementById('infoModoMasivo');

            if (valor === 'especificos') {
                divEspecificos.style.setProperty('display', 'flex', 'important');
                infoMasivo.style.setProperty('display', 'none', 'important');
            } else {
                divEspecificos.style.setProperty('display', 'none', 'important');
                infoMasivo.style.setProperty('display', 'block', 'important');
                
                const checkboxes = document.querySelectorAll('.funcionario-checkbox');
                checkboxes.forEach(chk => chk.checked = false);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const selectModo = document.getElementById('destinatario_tipo');
            if (selectModo) {
                toggleDestinatarioTipo(selectModo.value);
            }
        });

        function filtrarFuncionarios(texto) {
            const filtro = texto.toLowerCase();
            const items = document.querySelectorAll('.funcionario-item');
            items.forEach(item => {
                const searchData = item.getAttribute('data-search').toLowerCase();
                if (searchData.includes(filtro)) {
                    item.style.setProperty('display', 'flex', 'important');
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });
        }

        function marcarTodosCheck(marcar) {
            const checkboxes = document.querySelectorAll('.funcionario-checkbox');
            checkboxes.forEach(chk => {
                const parentItem = chk.closest('.funcionario-item');
                if (parentItem.style.display !== 'none') {
                    chk.checked = marcar;
                }
            });
        }

        function actualizarContador(textarea) {
            const texto = textarea.value;
            const caracteres = texto.length;
            const palabras = texto.trim() === '' ? 0 : texto.trim().split(/\s+/).length;
            document.getElementById('contadorCaracteres').textContent = `${caracteres} caracteres | ${palabras} palabras`;
        }

        function abrirVistaPrevia() {
            const asunto = document.getElementById('asunto_comunicado').value.trim();
            const mensaje = document.getElementById('mensaje_comunicado').value.trim();
            const archivoInput = document.getElementById('archivo_adjunto');

            document.getElementById('previewAsunto').textContent = (asunto !== "" ? asunto : "(Sin asunto)");
            document.getElementById('previewMensaje').textContent = mensaje !== "" ? mensaje : "(Escriba un mensaje para visualizarlo aquí...)";
            
            const avisoAdjunto = document.getElementById('previewAdjuntoAviso');
            if (archivoInput.files.length > 0) {
                avisoAdjunto.style.display = 'block';
            } else {
                avisoAdjunto.style.display = 'none';
            }

            const modal = new bootstrap.Modal(document.getElementById('modalVistaPrevia'));
            modal.show();
        }

        function verDetalleHistorial(asunto, cuerpo, fecha, remitente) {
            document.getElementById('histAsunto').textContent = asunto;
            document.getElementById('histRemitente').textContent = remitente;
            document.getElementById('histFecha').textContent = fecha;
            document.getElementById('histCuerpo').textContent = cuerpo;

            const modal = new bootstrap.Modal(document.getElementById('modalDetalleHistorial'));
            modal.show();
        }

        function confirmarEliminacion(id, asunto) {
            document.getElementById('lblAsuntoEliminar').textContent = asunto;
            document.getElementById('btnEjecutarEliminar').href = 'correo_institucional.php?eliminar_id=' + id;
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmarEliminacion'));
            modal.show();
        }
    </script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <?php include 'inactivity_check.php'; ?>
</body>
</html>