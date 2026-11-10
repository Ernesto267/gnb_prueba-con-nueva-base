<?php
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

$mensaje_alerta = "";
$tipo_alerta = "info";

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
                if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] === UPLOAD_ERR_OK) {
                    $nombre_temporal = $_FILES['archivo_adjunto']['tmp_name'];
                    $nombre_original = basename($_FILES['archivo_adjunto']['name']);
                    
                    $directorio_subidas = 'uploads_temp/';
                    if (!is_dir($directorio_subidas)) {
                        mkdir($directorio_subidas, 0755, true);
                    }
                    
                    $ruta_archivo_adjunto = $directorio_subidas . time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "", $nombre_original);
                    move_uploaded_file($nombre_temporal, $ruta_archivo_adjunto);
                }

                require_once 'PHPMailer/Exception.php';
                require_once 'PHPMailer/PHPMailer.php';
                require_once 'PHPMailer/SMTP.php';

                $enviados_exito = 0;

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

                        $mail_aviso->setFrom('leonernesto609@gmail.com', 'Laboratorio Criminalístico N° 11'); 
                        $mail_aviso->addAddress($dest['correo'], $dest['nombre_completo'] ?? 'Funcionario');     

                        if (!empty($ruta_archivo_adjunto) && file_exists($ruta_archivo_adjunto)) {
                            $mail_aviso->addAttachment($ruta_archivo_adjunto, basename($nombre_original));
                        }

                        $mail_aviso->isHTML(true);
                        $mail_aviso->Subject = 'Comunicado Oficial: ' . $asunto_comunicado;
                        $mail_aviso->Body    = "
                            <div style='font-family: Arial, sans-serif; padding: 25px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #f8fafc;'>
                                <div style='text-align: center; margin-bottom: 20px;'>
                                    <h3 style='color: #1e3a8a; margin: 0;'>Guardia Nacional Bolivariana</h3>
                                    <p style='color: #64748b; font-size: 12px; margin: 0;'>Laboratorio Criminalístico N° 11 (Zulia)</p>
                                </div>
                                <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 15px 0;'>
                                <h4 style='color: #0f172a; margin-top: 0;'>Asunto: " . htmlspecialchars($asunto_comunicado) . "</h4>
                                <p style='color: #334155;'>Estimado(a) <b>" . htmlspecialchars($dest['nombre_completo']) . "</b>,</p>
                                <div style='color: #334155; line-height: 1.6; background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;'>
                                    " . nl2br(htmlspecialchars($mensaje_comunicado)) . "
                                </div>
                                " . (!empty($ruta_archivo_adjunto) ? "<p style='margin-top: 15px; font-size: 13px; color: #2563eb;'>📎 Este mensaje incluye un documento adjunto.</p>" : "") . "
                                <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                                <p style='font-size: 11px; color: #94a3b8; text-align: center;'>Este es un mensaje institucional automatizado. Por favor no responda directamente a este correo.</p>
                            </div>
                        ";

                        $mail_aviso->send();
                        $enviados_exito++;
                    } catch (Exception $e) {}
                }

                if (!empty($ruta_archivo_adjunto) && file_exists($ruta_archivo_adjunto)) {
                    @unlink($ruta_archivo_adjunto);
                }

                $mensaje_alerta = "¡Comunicado con archivos adjuntos enviado con éxito a $enviados_exito funcionario(s)!";
                $tipo_alerta = "success";
            } else {
                $mensaje_alerta = "No se seleccionaron destinatarios válidos o los funcionarios no tienen correo registrado en su expediente.";
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
            --bg-body: #070b14; 
            --bg-navbar: #0f172a; 
            --text-main: #f8fafc; 
            --text-muted: #94a3b8;
            --card-bg: #111827; 
            --card-border: rgba(255, 255, 255, 0.12); 
            --card-shadow: 0 20px 40px rgba(0,0,0,0.4);
            --input-bg: #0b1120;
            --section-bg: #1e293b;
            --hover-item: rgba(59, 130, 246, 0.15);
            --badge-grado-bg: rgba(59, 130, 246, 0.15);
            --badge-grado-text: #93c5fd;
            --badge-grado-border: rgba(59, 130, 246, 0.3);
        }
        [data-theme="light"] {
            --bg-body: #e2e8f0; 
            --bg-navbar: #ffffff; 
            --text-main: #0f172a; 
            --text-muted: #475569;
            --card-bg: #ffffff; 
            --card-border: #cbd5e1; 
            --card-shadow: 0 15px 35px rgba(15, 23, 42, 0.12);
            --input-bg: #ffffff; 
            --section-bg: #f8fafc;
            --hover-item: rgba(59, 130, 246, 0.08);
            --badge-grado-bg: #eff6ff;
            --badge-grado-text: #1d4ed8;
            --badge-grado-border: #bfdbfe;
        }

        body { 
            background-color: var(--bg-body); 
            min-height: 100vh; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            color: var(--text-main); 
            transition: background 0.3s ease, color 0.3s ease; 
        }

        .navbar-gnb { 
            background: var(--bg-navbar); 
            border-bottom: 1.5px solid var(--card-border); 
            box-shadow: 0 4px 25px rgba(0,0,0,0.1); 
        }
        
        .navbar-brand img {
            width: 140px;
            height: 140px;
            object-fit: contain;
            position: relative;
            top: 4px; 
            margin-top: -40px; 
            margin-bottom: -40px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }

        .btn-nav-custom { 
            color: var(--text-main) !important; 
            border-color: var(--card-border) !important; 
            background: transparent; 
            transition: all 0.2s ease;
        }
        .btn-nav-custom:hover {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6 !important;
        }

        .main-card { 
            border-radius: 1.75rem; 
            box-shadow: var(--card-shadow); 
            background: var(--card-bg); 
            border: 1.5px solid var(--card-border); 
            color: var(--text-main); 
        }

        .form-control, .form-select { 
            background-color: var(--input-bg) !important; 
            color: var(--text-main) !important; 
            border: 1.5px solid var(--card-border) !important; 
            border-radius: 0.85rem;
            padding: 0.75rem 1rem;
            font-size: 0.92rem;
            transition: all 0.25s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.2) !important;
            background-color: var(--card-bg) !important;
        }
        .form-control::placeholder {
            color: var(--text-muted) !important;
            opacity: 0.7;
        }

        .box-section-custom { 
            background-color: var(--section-bg); 
            border: 1.5px solid var(--card-border); 
            border-radius: 1.25rem; 
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }

        .funcionario-item {
            background: var(--input-bg);
            border: 1.5px solid var(--card-border);
            border-radius: 0.85rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .funcionario-item:hover {
            border-color: #3b82f6;
            background: var(--hover-item);
            transform: translateY(-1px);
        }

        .badge-grado-custom {
            background-color: var(--badge-grado-bg) !important;
            color: var(--badge-grado-text) !important;
            border: 1px solid var(--badge-grado-border) !important;
            font-weight: 700;
            padding: 0.3rem 0.65rem;
            border-radius: 50rem;
            font-size: 0.7rem;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-bottom: 0.35rem;
        }

        .cursor-pointer { cursor: pointer; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-gnb shadow-lg py-3 sticky-top">
        <div class="container px-4">
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

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-11">

                <?php if (!empty($mensaje_alerta)): ?>
                    <div class="alert alert-<?php echo $tipo_alerta; ?> alert-dismissible fade show mb-4 shadow-sm rounded-4" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i><?php echo $mensaje_alerta; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card main-card p-4 p-md-5">
                    
                    <!-- Encabezado con Icono -->
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom" style="border-color: var(--card-border) !important;">
                        <div class="p-3 rounded-4 text-white fs-2 shadow-sm d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); width: 68px; height: 68px;">
                            <i class="bi bi-envelope-paper-heart-fill"></i>
                        </div>
                        <div>
                            <h2 class="fw-extrabold mb-1" style="color: var(--text-main); letter-spacing: -0.5px;">Correo Institucional</h2>
                            <p class="mb-0 text-muted">Despacho automatizado de circulares, directivas y documentos adjuntos al personal.</p>
                        </div>
                    </div>

                    <form action="correo_institucional.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="enviar_comunicado" value="1">
                        
                        <div class="row g-4">
                            <!-- Columna Izquierda: Destinatarios -->
                            <div class="col-lg-5">
                                <div class="box-section-custom h-100 d-flex flex-column">
                                    <label for="destinatario_tipo" class="form-label fw-bold text-primary mb-2">
                                        <i class="bi bi-people-fill me-1"></i> Modo de Destinatarios
                                    </label>
                                    <select class="form-select mb-3 fw-semibold" id="destinatario_tipo" name="destinatario_tipo" onchange="toggleDestinatarioTipo(this.value)">
                                        <option value="todos" selected>🌐 Todos los funcionarios (Masivo)</option>
                                        <option value="especificos">🎯 Seleccionar específicos (A medida)</option>
                                    </select>

                                    <!-- Bloque informativo en modo Masivo (Sin el círculo azul) -->
                                    <div id="infoModoMasivo" class="p-4 rounded-4 text-center border border-info border-opacity-25 mt-2" style="background: rgba(59, 130, 246, 0.05);">
                                        <h6 class="fw-bold text-primary mb-1"><i class="bi bi-broadcast me-1"></i> Envío Masivo Activo</h6>
                                        <p class="small text-muted mb-0" style="font-size: 0.82rem;">
                                            El comunicado oficial se despachará automáticamente a <strong>todos</strong> los funcionarios registrados que poseen correo institucional.
                                        </p>
                                    </div>

                                    <!-- Bloque de Selección de Funcionarios Específicos -->
                                    <div id="divFuncionariosEspecificos" style="display: none;" class="mt-2 flex-grow-1 flex-column">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label small fw-bold mb-0">Seleccione los funcionarios:</label>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary py-1 px-2 fw-semibold" onclick="marcarTodosCheck(true)">Todos</button>
                                                <button type="button" class="btn btn-outline-secondary py-1 px-2 fw-semibold" onclick="marcarTodosCheck(false)">Ninguno</button>
                                            </div>
                                        </div>
                                        
                                        <div class="input-group input-group-sm mb-3">
                                            <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                                            <input type="text" class="form-control form-control-sm" id="buscarFuncionario" placeholder="Buscar por nombre, cédula o grado..." oninput="filtrarFuncionarios(this.value)">
                                        </div>

                                        <div class="p-2 border rounded-3 overflow-y-auto flex-grow-1 shadow-inner" style="max-height: 340px; background: var(--card-bg); border-color: var(--card-border) !important;">
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
                                                    <div class="form-check p-3 mb-2 funcionario-item d-flex align-items-center gap-2" data-search="'.$search_string.'">
                                                        <input class="form-check-input funcionario-checkbox mt-0" type="checkbox" name="funcionarios_ids[]" value="'.$func['id'].'" id="func_chk_'.$func['id'].'" style="width: 1.2rem; height: 1.2rem;">
                                                        <label class="form-check-label w-100 cursor-pointer lh-sm" for="func_chk_'.$func['id'].'">
                                                            <span class="badge-grado-custom">'.$grado_func.'</span><br>
                                                            <span class="fw-bold fs-6" style="color: var(--text-main);">'.$nombre_completo.'</span><br>
                                                            <span class="text-muted" style="font-size: 0.78rem;"><i class="bi bi-card-text me-1"></i>C.I: '.$cedula_func.' | <i class="bi bi-envelope me-1"></i>'.$correo_func.'</span>
                                                        </label>
                                                    </div>';
                                                }
                                            } catch (Exception $e) {}
                                            ?>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Columna Derecha: Mensaje y Adjunto -->
                            <div class="col-lg-7">
                                <div class="d-flex flex-column gap-3">
                                    <div>
                                        <label for="asunto_comunicado" class="form-label fw-semibold">Asunto del Comunicado</label>
                                        <input type="text" class="form-control" id="asunto_comunicado" name="asunto_comunicado" placeholder="Ej: Circular Oficial N° 05 - Convocatoria" required>
                                    </div>

                                    <div>
                                        <label for="mensaje_comunicado" class="form-label fw-semibold">Cuerpo del Mensaje / Instrucciones</label>
                                        <textarea class="form-control" id="mensaje_comunicado" name="mensaje_comunicado" rows="9" placeholder="Escriba aquí el contenido detallado del comunicado oficial..." required></textarea>
                                    </div>

                                    <div>
                                        <label for="archivo_adjunto" class="form-label fw-semibold">Documento o Archivo Adjunto (Opcional)</label>
                                        <input type="file" class="form-control" id="archivo_adjunto" name="archivo_adjunto">
                                        <div class="form-text small" style="color: var(--text-muted);">Formatos compatibles: PDF, Word, Excel o Imágenes (Máx. 10 MB).</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-3 mt-4 pt-4 border-top" style="border-color: var(--card-border) !important;">
                            <a href="dashboard.php" class="btn btn-outline-secondary px-4 py-2.5 fw-bold rounded-pill">Cancelar</a>
                            <button type="submit" class="btn btn-primary px-5 py-2.5 fw-bold rounded-pill shadow-lg" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border:none;">
                                <i class="bi bi-send-fill me-2"></i> Despachar Comunicado Oficial
                            </button>
                        </div>
                    </form>

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
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
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
    </script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <?php include 'inactivity_check.php'; ?>
</body>
</html>