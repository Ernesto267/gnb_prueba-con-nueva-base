<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$rol_actual = $_SESSION['usuario_rol'] ?? 'usuario';

$total_personal = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM personal");
    $total_personal = $stmt->fetchColumn();
} catch (PDOException $e) {}

$total_usuarios = 0;
try {
    $stmt_u = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $total_usuarios = $stmt_u->fetchColumn();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Criminalístico N° 11 (Zulia) - Panel de Control</title>
    <script>
        const savedTheme = localStorage.getItem('gnb_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <style>
        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-navbar: #090d16;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --card-bg: #1e293b;
            --card-border: rgba(255, 255, 255, 0.1);
            --card-shadow: 0 10px 25px rgba(0,0,0,0.1);
            --badge-db-bg: rgba(16, 185, 129, 0.2);
            --badge-db-text: #34d399;
            --badge-db-border: rgba(52, 211, 153, 0.4);
            --badge-cuentas-bg: rgba(245, 158, 11, 0.25);
            --badge-cuentas-text: #fcd34d;
            --badge-cuentas-border: rgba(245, 158, 11, 0.4);
            --btn-nav-bg: rgba(255, 255, 255, 0.08);
            --btn-nav-border: rgba(255, 255, 255, 0.2);
            --btn-nav-text: #f8fafc;
            --btn-nav-hover: rgba(255, 255, 255, 0.18);
        }

        [data-theme="light"] {
            --bg-body: #cbd5e1;
            --bg-navbar: #ffffff;
            --text-main: #0f172a;
            --text-muted: #334155;
            --card-bg: #ffffff;
            --card-border: rgba(15, 23, 42, 0.22);
            --card-shadow: 0 12px 30px rgba(15, 23, 42, 0.15);
            --badge-db-bg: #a7f3d0;
            --badge-db-text: #064e3b;
            --badge-db-border: #34d399;
            --badge-cuentas-bg: #fde68a;
            --badge-cuentas-text: #78350f;
            --badge-cuentas-border: #f59e0b;
            --btn-nav-bg: #f1f5f9;
            --btn-nav-border: rgba(15, 23, 42, 0.25);
            --btn-nav-text: #0f172a;
            --btn-nav-hover: #e2e8f0;
        }

        body {
            background: var(--bg-body);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--text-main);
            padding-bottom: 50px;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .navbar-gnb {
            background: var(--bg-navbar);
            border-bottom: 1px solid var(--card-border);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .navbar-brand img {
            width: 150px;
            height: 150px;
            object-fit: contain;
            position: relative;
            top: 5px; 
            margin-top: -45px; 
            margin-bottom: -45px;
            filter: drop-shadow(0 5px 10px rgba(0,0,0,0.5));
        }

        .btn-nav-custom {
            background-color: var(--btn-nav-bg) !important;
            border: 1px solid var(--btn-nav-border) !important;
            color: var(--btn-nav-text) !important;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-nav-custom:hover {
            background-color: var(--btn-nav-hover) !important;
            transform: translateY(-1px);
        }

        .hero-banner {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            border-radius: 1.5rem;
            color: white;
            padding: 2.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .badge-banner-gnb {
            background-color: rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .badge-db-status {
            background-color: var(--badge-db-bg) !important;
            color: var(--badge-db-text) !important;
            border: 1px solid var(--badge-db-border) !important;
        }

        .badge-cuentas-custom {
            background-color: var(--badge-cuentas-bg) !important;
            color: var(--badge-cuentas-text) !important;
            border: 1px solid var(--badge-cuentas-border) !important;
        }

        .card-dash {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.2s ease, border-color 0.2s ease;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            cursor: pointer;
            text-decoration: none !important;
        }
        .card-dash:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 35px rgba(0,0,0,0.22);
            border-color: rgba(59, 130, 246, 0.6);
        }

        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .metric-card {
            background: var(--card-bg);
            border-radius: 1.25rem;
            padding: 1.25rem;
            border: 1px solid var(--card-border);
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background 0.2s ease, border-color 0.2s ease;
        }

        .pulse-effect {
            animation: pulseGlow 2s infinite;
        }
        @keyframes pulseGlow {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.5); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .card-title-custom { color: var(--text-main); }
        .card-text-custom { color: var(--text-muted); }
    </style>
</head>
<body>

    <!-- Navbar Superior -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-gnb shadow-lg py-2.5 sticky-top">
        <div class="container px-4">
            <a class="navbar-brand fw-bold fs-5 d-flex align-items-center" href="dashboard.php" style="color: var(--text-main);">
                <img src="imagen/logo_gnb.png?v=<?php echo time(); ?>" alt="Escudo GNB" class="me-3"> Laboratorio Criminalístico N<sup>o</sup> 11 (Zulia)
            </a>
            
            <div class="d-flex align-items-center gap-3">
                <button type="button" id="themeToggleBtn" class="btn btn-nav-custom btn-sm px-3 rounded-pill fw-semibold d-flex align-items-center gap-1" title="Cambiar Tema">
                    <i id="themeIcon" class="bi bi-sun-fill text-warning"></i> <span id="themeText" class="d-none d-md-inline">Blanco</span>
                </button>

                <div class="d-none d-md-flex flex-column text-end pe-3 border-end border-secondary">
                    <span id="liveClock" class="fw-bold fs-6 text-info font-monospace">00:00:00 HSR</span>
                    <span class="small" style="color: var(--text-muted); font-size: 0.7rem;"><?php echo date('d/m/Y'); ?></span>
                </div>

                <div class="text-end d-none d-sm-block">
                    <span class="fw-semibold" style="color: var(--text-main);"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></span>
                    <span class="badge <?php echo ($rol_actual === 'admin') ? 'bg-danger' : 'bg-primary'; ?> text-uppercase rounded-pill px-2" style="font-size: 0.65rem;">
                        <?php echo $rol_actual; ?>
                    </span>
                </div>

                <button type="button" class="btn btn-outline-danger btn-sm px-3 rounded-pill fw-semibold border-opacity-75 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCerrarSesion">
                    <i class="bi bi-box-arrow-right me-1"></i> Salir
                </button>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        
        <!-- Hero Banner de Bienvenida -->
        <div class="hero-banner mb-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <span class="badge badge-banner-gnb px-3 py-2 rounded-pill mb-3 fw-semibold">
                    <i class="bi bi-shield-check me-1"></i> Centro Operativo Digital
                </span>
                <h1 class="fw-bold display-6 mb-2 text-white">Panel de Control General</h1>
                <p class="text-white-50 mb-0">Sistema automatizado de gestión de expedientes militares, consultas y control de personal.</p>
            </div>
            <div class="mt-4 mt-md-0 text-md-end">
                <span class="d-block text-white-50 small mb-1">Estado de Conexión</span>
                <span class="badge badge-db-status px-3 py-2 rounded-pill fw-bold d-inline-flex align-items-center shadow-sm">
                    <i class="bi bi-circle-fill small me-2 pulse-effect text-success"></i> Base de Datos Online
                </span>
            </div>
        </div>

        <!-- Fila de Métricas Rápidas -->
        <div class="row g-4 mb-5">
            <?php if ($rol_actual === 'admin'): ?>
            <div class="col-md-4">
                <div class="metric-card">
                    <div>
                        <span class="small fw-bold text-uppercase d-block mb-1" style="color: var(--text-muted);">Cuentas Activas</span>
                        <h2 class="fw-bold mb-0" style="color: var(--text-main);"><?php echo $total_usuarios; ?></h2>
                    </div>
                    <div class="icon-box bg-warning text-dark">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="col-md-<?php echo ($rol_actual === 'admin') ? '4' : '6'; ?>">
                <div class="metric-card">
                    <div>
                        <span class="small fw-bold text-uppercase d-block mb-1" style="color: var(--text-muted);">Total Funcionarios</span>
                        <h2 class="fw-bold mb-0" style="color: var(--text-main);"><?php echo $total_personal; ?></h2>
                    </div>
                    <div class="icon-box bg-primary text-white">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-<?php echo ($rol_actual === 'admin') ? '4' : '6'; ?>">
                <div class="metric-card">
                    <div>
                        <span class="small fw-bold text-uppercase d-block mb-1" style="color: var(--text-muted);">Privilegios de Usuario</span>
                        <h3 class="fw-bold text-uppercase text-<?php echo ($rol_actual === 'admin') ? 'danger' : 'success'; ?> mb-0 fs-4">
                            <?php echo $rol_actual; ?>
                        </h3>
                    </div>
                    <div class="icon-box bg-success text-white">
                        <i class="bi bi-award-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Módulos y Accesos (Cuadrícula 4x4) -->
        <div class="mb-3">
            <div class="mb-4">
                <h5 class="fw-bold mb-0 d-flex align-items-center gap-2" style="color: var(--text-main);">
                    <i class="bi bi-grid-1x2-fill text-primary"></i> Módulos y Accesos del Sistema
                </h5>
            </div>
            
            <div class="row g-4">

                <?php if ($rol_actual === 'admin'): ?>
                    <!-- 1. Gestión de Usuarios -->
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <a href="registro_usuario.php" class="card card-dash h-100 p-4 d-flex flex-column justify-content-between text-decoration-none" style="border-top: 4px solid #f59e0b;">
                            <div>
                                <div class="icon-box bg-warning text-dark mb-3">
                                    <i class="bi bi-person-gear"></i>
                                </div>
                                <h4 class="fw-bold card-title-custom mb-2 fs-5">Gestión de Usuarios</h4>
                                <p class="card-text-custom small mb-3">Creación y administración de cuentas de acceso al sistema.</p>
                                <span class="badge badge-cuentas-custom fw-bold px-3 py-2 mb-4 rounded-pill">
                                    <i class="bi bi-person-badge me-1"></i> Cuentas: <?php echo $total_usuarios; ?>
                                </span>
                            </div>
                            <div class="btn btn-warning text-dark w-100 fw-bold py-2.5 rounded-pill shadow-sm">
                                <i class="bi bi-person-plus-fill me-1"></i> Crear Cuenta
                            </div>
                        </a>
                    </div>

                    <!-- 2. Registro de Personal -->
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <a href="registro_personal.php" class="card card-dash h-100 p-4 d-flex flex-column justify-content-between text-decoration-none" style="border-top: 4px solid #3b82f6;">
                            <div>
                                <div class="icon-box bg-primary text-white mb-3">
                                    <i class="bi bi-person-vcard-fill"></i>
                                </div>
                                <h4 class="fw-bold card-title-custom mb-2 fs-5">Registro de Personal</h4>
                                <p class="card-text-custom small mb-4">Registro completo de expedientes militares, familiares y médicos.</p>
                            </div>
                            <div class="btn btn-primary w-100 fw-bold py-2.5 rounded-pill shadow-sm">
                                <i class="bi bi-plus-circle me-1"></i> Registrar Personal
                            </div>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- 3. Consultas y Fichas -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <a href="consulta_personal.php" class="card card-dash h-100 p-4 d-flex flex-column justify-content-between text-decoration-none" style="border-top: 4px solid #10b981;">
                        <div>
                            <div class="icon-box bg-success text-white mb-3">
                                <i class="bi bi-search"></i>
                            </div>
                            <h4 class="fw-bold card-title-custom mb-2 fs-5">Consultas y Fichas</h4>
                            <p class="card-text-custom small mb-3">Lista general, buscador dinámico y generación de Fichas PDF.</p>
                            <span class="badge bg-success bg-opacity-25 text-success fw-bold px-3 py-2 mb-4 rounded-pill border border-success border-opacity-25">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Registrados: <?php echo $total_personal; ?>
                            </span>
                        </div>
                        <div class="btn btn-success w-100 fw-bold py-2.5 rounded-pill shadow-sm text-white">
                            <i class="bi bi-list-check me-1"></i> Ver Lista / Fichas
                        </div>
                    </a>
                </div>

                <!-- 4. Exportación de Datos a Excel -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <a href="exportar_excel.php" class="card card-dash h-100 p-4 d-flex flex-column justify-content-between text-decoration-none" style="border-top: 4px solid #0ea5e9;">
                        <div>
                            <div class="icon-box text-white mb-3 shadow-sm" style="background: linear-gradient(135deg, #0284c7, #0ea5e9) !important;">
                                <i class="bi bi-file-earmark-excel-fill"></i>
                            </div>
                            <h4 class="fw-bold card-title-custom mb-2 fs-5">Exportación Excel</h4>
                            <p class="card-text-custom small mb-4">Descarga de reportes estructurados de todo el personal en formato de hoja de cálculo.</p>
                        </div>
                        <div class="btn w-100 fw-bold py-2.5 rounded-pill shadow-sm text-white" style="background: linear-gradient(135deg, #0284c7, #0ea5e9); border:none;">
                            <i class="bi bi-arrow-right-circle me-1"></i> Ir a Exportación
                        </div>
                    </a>
                </div>

                <?php if ($rol_actual === 'admin'): ?>
                    <!-- 5. Correo Institucional (Redirige a la nueva página) -->
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <a href="correo_institucional.php" class="card card-dash h-100 p-4 d-flex flex-column justify-content-between text-decoration-none" style="border-top: 4px solid #0284c7;">
                            <div>
                                <div class="icon-box text-white mb-3 shadow-sm" style="background: linear-gradient(135deg, #0284c7, #38bdf8) !important;">
                                    <i class="bi bi-envelope-paper-fill fs-2"></i>
                                </div>
                                <h4 class="fw-bold card-title-custom mb-2 fs-5">Correo Institucional</h4>
                                <p class="card-text-custom small mb-4">Envío de comunicados, directivas y archivos adjuntos por correo.</p>
                            </div>
                            <div class="btn text-white w-100 fw-bold py-2.5 rounded-pill shadow-sm" style="background: linear-gradient(135deg, #0284c7, #38bdf8); border:none;">
                                <i class="bi bi-send-fill me-1"></i> Redactar Mensaje
                            </div>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- 6. Dashboard Analítico -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <a href="estadisticas_dashboard.php" class="card card-dash h-100 p-4 d-flex flex-column justify-content-between text-decoration-none" style="border-top: 4px solid #8b5cf6;">
                        <div>
                            <div class="icon-box text-white mb-3 shadow-sm" style="background: linear-gradient(135deg, #7c3aed, #8b5cf6) !important;">
                                <i class="bi bi-pie-chart-fill"></i>
                            </div>
                            <h4 class="fw-bold card-title-custom mb-2 fs-5">Dashboard Analítico</h4>
                            <p class="card-text-custom small mb-4">Gráficos interactivos de componentes, patologías y estadísticas globales.</p>
                        </div>
                        <div class="btn w-100 fw-bold py-2.5 rounded-pill shadow-sm text-white" style="background: linear-gradient(135deg, #7c3aed, #8b5cf6); border:none;">
                            <i class="bi bi-graph-up-arrow me-1"></i> Ver Estadísticas
                        </div>
                    </a>
                </div>

                <?php if ($rol_actual === 'admin'): ?>
                    <!-- 7. Configuración del Sistema -->
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <a href="configuracion.php" class="card card-dash h-100 p-4 d-flex flex-column justify-content-between text-decoration-none" style="border-top: 4px solid #ef4444;">
                            <div>
                                <div class="icon-box text-white mb-3 shadow-sm" style="background: linear-gradient(135deg, #dc2626, #ef4444) !important;">
                                    <i class="bi bi-gear-fill"></i>
                                </div>
                                <h4 class="fw-bold card-title-custom mb-2 fs-5">Configuración</h4>
                                <p class="card-text-custom small mb-4">Respaldos de base de datos SQL y parámetros de la unidad.</p>
                            </div>
                            <div class="btn w-100 fw-bold py-2.5 rounded-pill shadow-sm text-white" style="background: linear-gradient(135deg, #dc2626, #ef4444); border:none;">
                                <i class="bi bi-sliders me-1"></i> Configurar Sistema
                            </div>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- 8. Centro de Ayuda -->
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card card-dash h-100 p-4 position-relative overflow-hidden d-flex flex-column justify-content-between" style="border-top: 4px solid #64748b;" data-bs-toggle="modal" data-bs-target="#modalAyuda">
                        <div class="position-absolute top-0 end-0 me-3 mt-3">
                            <span class="badge bg-secondary text-white fw-bold px-2 py-1 rounded-pill"><i class="bi bi-star-fill text-warning me-1"></i>Soporte</span>
                        </div>
                        <div>
                            <div class="icon-box text-white mb-3 shadow-sm" style="background: linear-gradient(135deg, #475569, #64748b) !important;">
                                <i class="bi bi-headset fs-2"></i>
                            </div>
                            <h4 class="fw-bold card-title-custom mb-2 fs-5">Centro de Ayuda</h4>
                            <p class="card-text-custom small mb-4">Manual interactivo de uso, preguntas frecuentes y asistencia directa.</p>
                        </div>
                        <div class="btn text-white w-100 fw-bold py-2.5 rounded-pill shadow-sm" style="background: linear-gradient(135deg, #475569, #64748b); border:none;">
                            <i class="bi bi-question-circle-fill me-1"></i> Abrir Soporte
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Modal Cerrar Sesión -->
    <div class="modal fade" id="modalCerrarSesion" tabindex="-1" aria-labelledby="modalCerrarSesionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: var(--card-bg); color: var(--text-main);">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e3a8a, #1e40af);">
                    <h5 class="modal-title fw-bold" id="modalCerrarSesionLabel">
                        <i class="bi bi-shield-exclamation me-2"></i>Confirmar Salida del Sistema
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-box-arrow-right text-info display-3 d-block mb-3"></i>
                    <h5 class="fw-bold mb-2" style="color: var(--text-main);">¿Seguro que deseas cerrar sesión?</h5>
                    <p class="small mb-0" style="color: var(--text-muted);">Tendrás que ingresar tus credenciales nuevamente para acceder al panel.</p>
                </div>
                <div class="modal-footer border-top-0 d-flex justify-content-center gap-2 py-3" style="background: var(--card-bg);">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-bold rounded-pill" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <a href="logout.php" class="btn btn-primary px-4 fw-bold rounded-pill shadow-sm" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border:none;">
                        <i class="bi bi-check-circle-fill me-1"></i> Sí, Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Ayuda y Soporte -->
    <div class="modal fade" id="modalAyuda" tabindex="-1" aria-labelledby="modalAyudaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: var(--card-bg); color: var(--text-main);">
                <div class="modal-header px-4 py-3 border-bottom" style="background: var(--bg-navbar);">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="modalAyudaLabel">
                        <i class="bi bi-patch-question-fill text-info fs-3 me-2"></i> Centro de Ayuda y Soporte Técnico
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <ul class="nav nav-pills nav-fill mb-4 p-2 rounded-3 border" id="pills-tab" role="tablist" style="background: var(--bg-navbar);">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold rounded-pill" id="pills-manual-tab" data-bs-toggle="pill" data-bs-target="#pills-manual" type="button" role="tab">
                                <i class="bi bi-book me-1"></i> Manual de Uso
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold rounded-pill" id="pills-faq-tab" data-bs-toggle="pill" data-bs-target="#pills-faq" type="button" role="tab">
                                <i class="bi bi-question-lg me-1"></i> Preguntas Frecuentes
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold rounded-pill" id="pills-soporte-tab" data-bs-toggle="pill" data-bs-target="#pills-soporte" type="button" role="tab">
                                <i class="bi bi-telephone-outbound me-1"></i> Contactar Soporte
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-manual" role="tabpanel">
                            <div class="p-4 rounded-3 border" style="background: var(--bg-body);">
                                <h6 class="fw-bold text-info mb-3"><i class="bi bi-journal-check me-2"></i>Guía Rápida de Operación</h6>
                                <div class="d-flex mb-3 align-items-start">
                                    <div class="badge bg-warning text-dark rounded-circle p-2 me-3 fs-6">1</div>
                                    <div>
                                        <h6 class="fw-bold mb-1" style="color: var(--text-main);">Módulo de Usuarios (Solo Administrador)</h6>
                                        <p class="small mb-0" style="color: var(--text-muted);">Permite registrar nuevos administradores o usuarios estándar asignando su rol correspondiente.</p>
                                    </div>
                                </div>
                                <hr style="border-color: var(--card-border);">
                                <div class="d-flex mb-3 align-items-start">
                                    <div class="badge bg-primary rounded-circle p-2 me-3 fs-6">2</div>
                                    <div>
                                        <h6 class="fw-bold mb-1" style="color: var(--text-main);">Registro de Personal</h6>
                                        <p class="small mb-0" style="color: var(--text-muted);">Ingreso completo de expedientes militares, familiares y datos médicos en la base de datos.</p>
                                    </div>
                                </div>
                                <hr style="border-color: var(--card-border);">
                                <div class="d-flex align-items-start">
                                    <div class="badge bg-success rounded-circle p-2 me-3 fs-6">3</div>
                                    <div>
                                        <h6 class="fw-bold mb-1" style="color: var(--text-main);">Consultas y Fichas PDF</h6>
                                        <p class="small mb-0" style="color: var(--text-muted);">Buscador dinámico de funcionarios, edición de registros y generación directa de fichas en PDF.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-faq" role="tabpanel">
                            <div class="accordion rounded-3 overflow-hidden border" id="accordionFAQ">
                                <div class="accordion-item" style="background: var(--card-bg); color: var(--text-main); border-color: var(--card-border);">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" style="background: var(--card-bg); color: var(--text-main);">
                                            ¿Qué hago si no puedo visualizar la Ficha en PDF?
                                        </button>
                                    </h2>
                                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#accordionFAQ">
                                        <div class="accordion-body small" style="color: var(--text-muted);">
                                            Verifique que su navegador web no esté bloqueando las ventanas emergentes (Pop-ups).
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item" style="background: var(--card-bg); color: var(--text-main); border-color: var(--card-border);">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" style="background: var(--card-bg); color: var(--text-main);">
                                            ¿Cómo recupero mi contraseña?
                                        </button>
                                    </h2>
                                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                                        <div class="accordion-body small" style="color: var(--text-muted);">
                                            Utilice la opción de recuperación mediante token por correo en la pantalla de inicio de sesión (index.php).
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="pills-soporte" role="tabpanel">
                            <div class="p-4 rounded-3 border text-center" style="background: var(--bg-body);">
                                <i class="bi bi-shield-check text-success display-4 mb-2 d-block"></i>
                                <h5 class="fw-bold mb-1" style="color: var(--text-main);">Soporte Técnico Institucional</h5>
                                <p class="small mb-4" style="color: var(--text-muted);">Atención ante incidencias y requerimientos del sistema.</p>
                                <div class="d-flex flex-wrap justify-content-center gap-3">
                                    <a href="https://wa.me/584246521645" target="_blank" class="btn btn-success fw-bold px-4 py-2 rounded-pill">
                                        <i class="bi bi-whatsapp me-2"></i> WhatsApp Directo
                                    </a>
                                    <a href="mailto:leonernesto609@gmail.com" class="btn btn-outline-danger fw-bold px-4 py-2 rounded-pill">
                                        <i class="bi bi-envelope-fill me-2"></i> Enviar Correo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3" style="background: var(--bg-navbar); border-color: var(--card-border);">
                    <button type="button" class="btn btn-secondary px-4 fw-bold rounded-pill" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script de Reloj y Tema -->
    <script>
        function updateClock() {
            const now = new Date();
            const format = localStorage.getItem('gnb_time_format') || 'military';
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            let timeString = "";
            if (format === 'normal') {
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12; 
                const formattedHours = String(hours).padStart(2, '0');
                timeString = `${formattedHours}:${minutes}:${seconds} ${ampm}`;
            } else {
                const formattedHours = String(hours).padStart(2, '0');
                timeString = `${formattedHours}:${minutes}:${seconds} HSR`;
            }
            const clockElement = document.getElementById('liveClock');
            if (clockElement) {
                clockElement.textContent = timeString;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');
        const htmlElement = document.documentElement;

        updateThemeUI(htmlElement.getAttribute('data-theme'));

        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('gnb_theme', newTheme);
            updateThemeUI(newTheme);
        });

        function updateThemeUI(theme) {
            if (theme === 'light') {
                themeIcon.className = 'bi bi-moon-fill text-dark';
                themeText.textContent = 'Oscuro';
            } else {
                themeIcon.className = 'bi bi-sun-fill text-warning';
                themeText.textContent = 'Blanco';
            }
        }
    </script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <?php include 'inactivity_check.php'; ?>
</body>
</html>