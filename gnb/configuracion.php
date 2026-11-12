<?php
session_start();
require_once 'conexion.php';

// Verificar que el usuario sea administrador (insensible a mayúsculas/minúsculas)
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$mensaje = "";
$tipo_alerta = "";

// Procesar formularios de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Restaurar Base de Datos (Subir archivo .sql)
    if (isset($_POST['restaurar_db'])) {
        if (isset($_FILES['archivo_sql']) && $_FILES['archivo_sql']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['archivo_sql']['tmp_name'];
            $sql_content = file_get_contents($tmp_name);
            try {
                $pdo->exec($sql_content);
                $mensaje = "Base de datos restaurada exitosamente desde el respaldo.";
                $tipo_alerta = "success";
            } catch (PDOException $e) {
                $mensaje = "Error al restaurar la base de datos: " . $e->getMessage();
                $tipo_alerta = "danger";
            }
        } else {
            $mensaje = "Por favor, seleccione un archivo .sql válido para restaurar.";
            $tipo_alerta = "warning";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Criminalístico N° 11 (Zulia) - Configuración Avanzada</title>
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
        }

        [data-theme="light"] {
            --bg-body: #e2e8f0; 
            --bg-navbar: #ffffff; 
            --text-main: #0f172a; 
            --text-muted: #475569;
            --card-bg: #ffffff; 
            --card-border: #475569; 
            --card-shadow: 0 15px 35px rgba(15, 23, 42, 0.12);
            --input-bg: #ffffff; 
            --section-bg: #f8fafc;
        }

        body { 
            background-color: var(--bg-body); 
            min-height: 100vh; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            color: var(--text-main); 
            transition: background 0.3s ease, color 0.3s ease; 
            padding-bottom: 50px; 
        }

        .navbar-gnb { 
            background: var(--bg-navbar); 
            border-bottom: 1.5px solid var(--card-border); 
            box-shadow: 0 4px 25px rgba(0,0,0,0.1); 
        }

        .config-card { 
            background: var(--card-bg); 
            border: 1.5px solid var(--card-border); 
            border-radius: 1.5rem; 
            box-shadow: var(--card-shadow); 
            color: var(--text-main);
            transition: all 0.3s ease;
        }

        .custom-muted { 
            color: var(--text-muted) !important; 
        }

        .status-box {
            background-color: var(--section-bg) !important;
            border: 1.5px solid var(--card-border) !important;
            color: var(--text-main) !important;
        }

        .form-control, .form-select {
            background-color: var(--input-bg) !important;
            border: 1.5px solid var(--card-border) !important;
            color: var(--text-main) !important;
            border-radius: 0.75rem;
            padding: 0.65rem 0.9rem;
            font-size: 0.9rem;
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

        .form-select option {
            background-color: var(--card-bg);
            color: var(--text-main);
        }

        .btn-nav-custom {
            color: var(--text-main) !important;
            border: 1.5px solid var(--card-border);
            background: transparent;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-nav-custom:hover {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6 !important;
            border-color: #3b82f6 !important;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark navbar-gnb shadow-lg py-3 sticky-top">
        <div class="container px-4">
            <a class="navbar-brand fw-bold fs-5 d-flex align-items-center" href="dashboard.php" style="color: var(--text-main);">
                <i class="bi bi-arrow-left-circle text-primary fs-4 me-2"></i> Volver al Panel General
            </a>
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-semibold shadow-sm">
                <i class="bi bi-gear-fill me-1"></i> Módulo de Configuración Avanzada
            </span>
        </div>
    </nav>

    <div class="container py-5">
        <div class="mb-4">
            <h2 class="fw-extrabold mb-1" style="letter-spacing: -0.5px;">Configuración y Mantenimiento del Sistema</h2>
            <p class="custom-muted small">Gestión de respaldos, restauración, preferencias de interfaz y seguridad de sesiones.</p>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_alerta; ?> alert-dismissible fade show rounded-4 mb-4 shadow-sm" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i> <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <!-- 1. Respaldo y Restauración de Base de Datos -->
            <div class="col-lg-6">
                <div class="config-card p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-4 bg-danger bg-opacity-10 text-danger me-3 fs-4 shadow-sm">
                                <i class="bi bi-database-fill-gear"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Base de Datos (Backup / Restore)</h5>
                                <span class="small custom-muted">Gestión integral de respaldos SQL</span>
                            </div>
                        </div>
                        <p class="small custom-muted mb-3">Descargue un respaldo completo o suba un archivo .sql previo para restaurar los datos del sistema en caso de emergencia.</p>
                        
                        <form method="POST" enctype="multipart/form-data" class="mb-3 border-top pt-3" style="border-color: var(--card-border) !important;">
                            <label class="form-label small fw-semibold text-warning">Restaurar Base de Datos (.sql)</label>
                            <div class="input-group mb-2">
                                <input type="file" class="form-control form-control-sm" name="archivo_sql" accept=".sql" required>
                                <button type="submit" name="restaurar_db" class="btn btn-outline-warning btn-sm fw-bold px-3">
                                    <i class="bi bi-upload"></i> Restaurar
                                </button>
                            </div>
                        </form>
                    </div>

                    <a href="exportar_db.php" class="btn btn-danger w-100 fw-bold py-2.5 rounded-pill shadow-sm" style="background: linear-gradient(135deg, #dc2626, #b91c1c); border:none;">
                        <i class="bi bi-cloud-arrow-down-fill me-2"></i> Descargar Respaldo Completo (.sql)
                    </a>
                </div>
            </div>

            <!-- 2. Preferencias de Interfaz y Opciones del Sistema -->
            <div class="col-lg-6">
                <div class="config-card p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-4 bg-primary bg-opacity-10 text-primary me-3 fs-4 shadow-sm">
                                <i class="bi bi-display-fill"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Preferencias de Interfaz</h5>
                                <span class="small custom-muted">Apariencia, temas y formato horario</span>
                            </div>
                        </div>
                        <p class="small custom-muted mb-3">Personalice el aspecto visual del sistema cambiando entre modo oscuro o blanco, y ajuste el formato de las horas según su preferencia.</p>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Modo de Apariencia (Tema)</label>
                            <select class="form-select form-select-sm" id="selectorTema" onchange="cambiarTema(this.value)">
                                <option value="dark">Modo Oscuro</option>
                                <option value="light">Modo Blanco / Claro</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Formato de Hora</label>
                            <select class="form-select form-select-sm" id="selectorHora" onchange="cambiarFormatoHora(this.value)">
                                <option value="military">Hora Militar (24 Horas)</option>
                                <option value="normal">Hora Normal (12 Horas AM/PM)</option>
                            </select>
                        </div>
                    </div>
                    <div class="p-3 rounded-4 status-box small d-flex align-items-center justify-content-between shadow-sm">
                        <span>Estado de preferencias:</span>
                        <span class="badge bg-success text-white px-2.5 py-1.5">Sincronizadas localmente</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila Inferior: Seguridad y Sesiones -->
        <div class="row g-4">
            <div class="col-12">
                <div class="config-card p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 rounded-4 bg-warning bg-opacity-10 text-warning me-3 fs-4 shadow-sm">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Seguridad y Sesiones</h5>
                            <span class="small custom-muted">Control operativo de inactividad del usuario</span>
                        </div>
                    </div>
                    <p class="small custom-muted mb-3">Configure el tiempo límite para cerrar sesiones abiertas automáticamente por inactividad en los equipos de la unidad.</p>
                    
                    <div class="row align-items-end g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Tiempo de Expiración por Inactividad</label>
                            <select class="form-select" id="tiempoInactividad">
                                <option value="15" selected>15 Minutos (Recomendado)</option>
                                <option value="30">30 Minutos</option>
                                <option value="60">1 Hora</option>
                                <option value="0">Desactivado</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-outline-primary w-100 fw-bold rounded-pill py-2.5 shadow-sm" onclick="guardarTiempoInactividad()">
                                <i class="bi bi-shield-check me-1"></i> Aplicar Política
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="js/bootstrap.bundle.min.js"></script>

    <script>
        // Cargar preferencias guardadas al abrir la vista
        document.addEventListener('DOMContentLoaded', () => {
            // Tema
            const savedTheme = localStorage.getItem('gnb_theme') || 'dark';
            const selectTema = document.getElementById('selectorTema');
            if (selectTema) selectTema.value = savedTheme;

            // Hora
            const savedHora = localStorage.getItem('gnb_time_format') || 'military';
            const selectHora = document.getElementById('selectorHora');
            if (selectHora) selectHora.value = savedHora;

            // Inactividad
            const savedTime = localStorage.getItem('gnb_inactivity_time');
            if (savedTime !== null) {
                const selectEl = document.getElementById('tiempoInactividad');
                if (selectEl) selectEl.value = savedTime;
            }
        });

        // Cambiar tema dinámicamente
        function cambiarTema(nuevoTema) {
            localStorage.setItem('gnb_theme', nuevoTema);
            document.documentElement.setAttribute('data-theme', nuevoTema);
        }

        // Cambiar formato de hora
        function cambiarFormatoHora(formato) {
            localStorage.setItem('gnb_time_format', formato);
        }

        // Guardar la elección de inactividad
        function guardarTiempoInactividad() {
            const select = document.getElementById('tiempoInactividad');
            const valorMinutos = select.value;
            
            localStorage.setItem('gnb_inactivity_time', valorMinutos);
            
            let textoNotificacion = valorMinutos == 0 ? 
                "El control de inactividad ha sido desactivado." : 
                "Nuevo tiempo límite: " + valorMinutos + " minuto(s).";
                
            alert("¡Política de seguridad aplicada!\n" + textoNotificacion);
        }
    </script>

    <?php include 'inactivity_check.php'; ?>
</body>
</html>