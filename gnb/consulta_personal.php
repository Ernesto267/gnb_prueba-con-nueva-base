<?php
session_start();
require_once 'conexion.php';

// Verificar sesión de usuario
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$rol_actual = $_SESSION['usuario_rol'] ?? 'usuario';
$mensaje = ''; $tipo_mensaje = '';

// Manejo de Eliminación
if (isset($_GET['eliminar_id'])) {
    $eliminar_id = (int)$_GET['eliminar_id'];
    try {
        // Consultar archivos en tablas relacionadas antes de eliminar
        $stmt_f = $pdo->prepare("SELECT * FROM personal_fotos WHERE personal_id = ?");
        $stmt_f->execute([$eliminar_id]);
        $reg_fotos = $stmt_f->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt_fam = $pdo->prepare("SELECT * FROM personal_familiares WHERE personal_id = ?");
        $stmt_fam->execute([$eliminar_id]);
        $reg_fam = $stmt_fam->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt_h = $pdo->prepare("SELECT * FROM personal_hijos WHERE personal_id = ?");
        $stmt_h->execute([$eliminar_id]);
        $reg_hijos = $stmt_h->fetchAll(PDO::FETCH_ASSOC);

        $stmt_c = $pdo->prepare("SELECT * FROM personal_cursos WHERE personal_id = ?");
        $stmt_c->execute([$eliminar_id]);
        $reg_cursos = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

        $stmt_del = $pdo->prepare("DELETE FROM personal WHERE id = ?");
        if ($stmt_del->execute([$eliminar_id])) {
            $archivos_fisicos = [
                $reg_fotos['foto_cedula'] ?? '', $reg_fotos['foto_carnet_frente'] ?? '', $reg_fotos['foto_carnet_reverso'] ?? '',
                $reg_fotos['foto_partida_frente'] ?? '', $reg_fotos['foto_partida_reverso'] ?? '',
                $reg_fotos['foto_gala'] ?? '', $reg_fotos['foto_patriota_tiuna'] ?? '', 
                $reg_fotos['foto_interior_cuartel'] ?? '', $reg_fotos['foto_civil'] ?? '',
                $reg_fam['foto_cedula_conyuge'] ?? '', $reg_fam['foto_cedula_padre'] ?? '', $reg_fam['foto_cedula_madre'] ?? ''
            ];
            foreach ($archivos_fisicos as $file) {
                if (!empty($file) && file_exists($file)) { @unlink($file); }
            }

            foreach ($reg_hijos as $h) {
                if (!empty($h['cedula_doc']) && file_exists($h['cedula_doc'])) { @unlink($h['cedula_doc']); }
                if (!empty($h['partida_frente']) && file_exists($h['partida_frente'])) { @unlink($h['partida_frente']); }
                if (!empty($h['partida_reverso']) && file_exists($h['partida_reverso'])) { @unlink($h['partida_reverso']); }
            }

            foreach ($reg_cursos as $c) {
                if (!empty($c['soporte']) && file_exists($c['soporte'])) { @unlink($c['soporte']); }
            }

            $mensaje = "Expediente del funcionario eliminado correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al intentar eliminar el registro.";
            $tipo_mensaje = "danger";
        }
    } catch (PDOException $e) {
        $mensaje = "Error en la base de datos: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Cargar personal y sus relaciones
try {
    $stmt = $pdo->query("SELECT * FROM personal ORDER BY id DESC");
    $personal_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $personal = [];
    if (count($personal_raw) > 0) {
        $ids = array_column($personal_raw, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // Fotos
        $stmt_f = $pdo->prepare("SELECT * FROM personal_fotos WHERE personal_id IN ($placeholders)");
        $stmt_f->execute($ids);
        $fotos_map = [];
        while ($row = $stmt_f->fetch(PDO::FETCH_ASSOC)) {
            $fotos_map[$row['personal_id']] = $row;
        }

        // Familiares
        $stmt_fam = $pdo->prepare("SELECT * FROM personal_familiares WHERE personal_id IN ($placeholders)");
        $stmt_fam->execute($ids);
        $fam_map = [];
        while ($row = $stmt_fam->fetch(PDO::FETCH_ASSOC)) {
            $fam_map[$row['personal_id']] = $row;
        }

        // Hijos
        $stmt_h = $pdo->prepare("SELECT * FROM personal_hijos WHERE personal_id IN ($placeholders)");
        $stmt_h->execute($ids);
        $hijos_map = [];
        while ($row = $stmt_h->fetch(PDO::FETCH_ASSOC)) {
            $hijos_map[$row['personal_id']][] = $row;
        }

        // Cursos
        $stmt_c = $pdo->prepare("SELECT * FROM personal_cursos WHERE personal_id IN ($placeholders)");
        $stmt_c->execute($ids);
        $cursos_map = [];
        while ($row = $stmt_c->fetch(PDO::FETCH_ASSOC)) {
            $cursos_map[$row['personal_id']][] = $row;
        }

        foreach ($personal_raw as $p) {
            $pid = $p['id'];
            $p['fotos'] = $fotos_map[$pid] ?? [];
            $p['familiares'] = $fam_map[$pid] ?? [];
            $p['hijos'] = $hijos_map[$pid] ?? [];
            $p['cursos'] = $cursos_map[$pid] ?? [];
            $personal[] = $p;
        }
    }
} catch (PDOException $e) {
    $mensaje = "Error al consultar la base de datos: " . $e->getMessage();
    $tipo_mensaje = "danger";
    $personal = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Criminalístico N° 11 (Zulia) - Consulta de Personal</title>
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
            --table-hover: #1f293d;
            --accordion-bg: #111827;
            --pill-bg: #1e293b;
            --badge-counter-bg: rgba(30, 58, 138, 0.4);
            --badge-counter-border: rgba(59, 130, 246, 0.3);
            --badge-counter-text: #93c5fd;
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
            --table-hover: #f1f5f9;
            --accordion-bg: #ffffff;
            --pill-bg: #f8fafc;
            --badge-counter-bg: #eff6ff;
            --badge-counter-border: #bfdbfe;
            --badge-counter-text: #1d4ed8;
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
            border-radius: 1.5rem; 
            box-shadow: var(--card-shadow); 
            background: var(--card-bg); 
            border: 1.5px solid var(--card-border); 
            color: var(--text-main); 
        }

        .table-responsive { border-radius: 0.85rem; overflow: hidden; border: 1.5px solid var(--card-border); }
        .table { color: var(--text-main); margin-bottom: 0; }
        .table > :not(caption) > * > * { background-color: transparent; color: var(--text-main); border-bottom-color: var(--card-border); }
        .table-hover > tbody > tr:hover > * { background-color: var(--table-hover) !important; color: var(--text-main); }
        .table th { background: linear-gradient(135deg, #1e3a8a, #1e40af) !important; color: #ffffff !important; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; padding: 1rem 0.75rem; border: none; }
        
        .badge-especialidad { background-color: var(--section-bg); color: var(--text-main); border: 1.5px solid var(--card-border); font-weight: 600; }
        .badge-expedientes { background-color: var(--badge-counter-bg) !important; color: var(--badge-counter-text) !important; border: 1.5px solid var(--badge-counter-border) !important; }
        
        .btn-action-custom { padding: 0.4rem 0.7rem; font-size: 0.75rem; font-weight: 700; border-radius: 50rem; border: none; display: inline-flex; align-items: center; gap: 0.25rem; text-transform: uppercase; text-decoration: none !important; }
        .btn-archivos { background: linear-gradient(135deg, #0284c7, #0369a1); color: #ffffff !important; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.3); }
        .btn-formacion { background: linear-gradient(135deg, #0d9488, #0f766e); color: #ffffff !important; box-shadow: 0 4px 10px rgba(13, 148, 136, 0.3); }
        .btn-pdf { background: linear-gradient(135deg, #e11d48, #be123c); color: #ffffff !important; box-shadow: 0 4px 10px rgba(225, 29, 72, 0.3); }
        .btn-editar { background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff !important; box-shadow: 0 4px 10px rgba(217, 119, 6, 0.3); }
        .btn-eliminar { background: #fee2e2; color: #dc2626 !important; border: 1.5px solid #fca5a5; }
        
        .search-box { position: relative; }
        .search-box i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #3b82f6; z-index: 10; }
        .search-box input { 
            padding-left: 50px; 
            border-radius: 50rem; 
            border: 1.5px solid var(--card-border) !important; 
            background: var(--input-bg) !important; 
            color: var(--text-main) !important; 
            transition: all 0.25s ease;
        }
        .search-box input::placeholder { 
            color: var(--text-muted) !important; 
            opacity: 0.7; 
        }
        .search-box input:focus {
            background: var(--card-bg) !important;
            color: var(--text-main) !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25) !important;
        }
        
        .preview-pill { display: flex; align-items: center; justify-content: space-between; background: var(--pill-bg); border: 1.5px solid var(--card-border); border-radius: 0.85rem; padding: 12px 16px; margin-bottom: 10px; transition: transform 0.2s ease, border-color 0.2s ease; }
        .preview-pill:hover { border-color: #3b82f6; transform: translateY(-2px); }
        .preview-pill span.fw-bold, .preview-pill small, .preview-pill span { color: var(--text-main) !important; }
        .preview-pill small.text-muted, .text-muted { color: var(--text-muted) !important; }
        .img-thumb-modern { width: 60px; height: 60px; object-fit: cover; border-radius: 0.5rem; border: 1.5px solid var(--card-border); box-shadow: 0 2px 6px rgba(0,0,0,0.2); }
        
        .accordion-item { background-color: var(--accordion-bg) !important; border: 1.5px solid var(--card-border) !important; margin-bottom: 12px; border-radius: 0.85rem !important; overflow: hidden; }
        .accordion-button { background-color: var(--accordion-bg) !important; color: var(--text-main) !important; border: none; box-shadow: none !important; font-weight: 600; padding: 1rem 1.25rem; }
        .accordion-button:not(.collapsed) { background-color: rgba(59, 130, 246, 0.12) !important; color: #3b82f6 !important; }
        .accordion-body { background-color: var(--accordion-bg) !important; color: var(--text-main) !important; border-top: 1.5px solid var(--card-border); padding: 1.25rem; }
        .hijo-card-box { background: var(--pill-bg); border: 1.5px solid var(--card-border); border-radius: 0.85rem; padding: 1rem; margin-bottom: 12px; }
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
                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-4 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCerrarSesion">
                    <i class="bi bi-box-arrow-right me-1"></i> Salir
                </button>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h2 class="fw-extrabold mb-1" style="color: var(--text-main); letter-spacing: -0.5px;">Consulta de Personal Militar</h2>
                        <p class="mb-0 text-muted">Directorio interactivo y gestión centralizada de expedientes.</p>
                    </div>
                    <?php if ($rol_actual === 'admin'): ?>
                        <a href="registro_personal.php" class="btn btn-success btn-lg rounded-pill fw-bold shadow-sm px-4 py-2" style="background: linear-gradient(135deg, #16a34a, #15803d); border:none;">
                            <i class="bi bi-person-plus-fill me-2"></i> Registrar Nuevo Personal
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show mb-4 shadow-sm rounded-4" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i><?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card main-card p-4">
                    
                    <div class="row align-items-center mb-4 g-3">
                        <div class="col-md-6 search-box">
                            <i class="bi bi-search fs-5"></i>
                            <input type="text" id="inputBuscador" class="form-control form-control-lg fs-6" placeholder="Buscar por Cédula, Nombres, Apellidos o Grado..." onkeyup="filtrarTabla()">
                        </div>
                        <div class="col-md-6 text-md-end d-flex justify-content-md-end justify-content-center align-items-center">
                            <span class="badge badge-expedientes fs-6 px-3 py-2 rounded-pill shadow-sm fw-semibold">
                                <i class="bi bi-people-fill me-1"></i> Expedientes: <strong id="totalRegistros"><?php echo count($personal); ?></strong>
                            </span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaPersonal">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Grado</th>
                                    <th>Cédula</th>
                                    <th>Nombres y Apellidos</th>
                                    <th>Especialidad</th>
                                    <th>Teléfono</th>
                                    <th class="text-center" style="width: 390px;">Acciones Directas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($personal) > 0): ?>
                                    <?php foreach ($personal as $index => $row): ?>
                                        <tr>
                                            <td class="text-center fw-bold" style="color: var(--text-muted);"><?php echo $index + 1; ?></td>
                                            <td><span class="badge bg-dark rounded-pill px-3 py-2"><?php echo htmlspecialchars($row['grado'] ?? 'N/A'); ?></span></td>
                                            <td class="fw-bold text-info"><?php echo htmlspecialchars($row['cedula']); ?></td>
                                            <td class="fw-semibold" style="color: var(--text-main);"><?php echo htmlspecialchars(($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? '')); ?></td>
                                            <td><span class="badge badge-especialidad px-3 py-1.5 rounded-pill"><?php echo htmlspecialchars($row['especialidad'] ?? 'Sin especificar'); ?></span></td>
                                            
                                            <td class="small" style="color: var(--text-muted);">
                                                <?php 
                                                    $tel = trim($row['telefono_celular'] ?? '');
                                                    if (strlen($tel) === 11 && strpos($tel, '-') === false) {
                                                        $telFormatted = substr($tel, 0, 4) . '-' . substr($tel, 4);
                                                    } else {
                                                        $telFormatted = $tel;
                                                    }
                                                    echo htmlspecialchars($telFormatted ?: 'N/A'); 
                                                ?>
                                            </td>

                                            <td class="text-center">
                                                <div class="d-flex justify-content-center align-items-center gap-1">
                                                    <button type="button" class="btn-action-custom btn-archivos" title="Ver Cédula y Fotografías" data-bs-toggle="modal" data-bs-target="#modalArchivos<?php echo $row['id']; ?>">
                                                        <i class="bi bi-folder2-open fs-6"></i> Archivos
                                                    </button>
                                                    <button type="button" class="btn-action-custom btn-formacion" title="Ver Títulos, Cursos y Talleres" data-bs-toggle="modal" data-bs-target="#modalCursos<?php echo $row['id']; ?>">
                                                        <i class="bi bi-mortarboard-fill fs-6"></i> Formación
                                                    </button>
                                                    <a href="generar_pdf.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn-action-custom btn-pdf" title="Imprimir / Exportar PDF">
                                                        <i class="bi bi-file-earmark-pdf-fill fs-6"></i> PDF
                                                    </a>
                                                    <?php if ($rol_actual === 'admin'): ?>
                                                        <a href="editar_personal.php?id=<?php echo $row['id']; ?>" class="btn-action-custom btn-editar" title="Editar Expediente">
                                                            <i class="bi bi-pencil-square fs-6"></i>
                                                        </a>
                                                        <button type="button" class="btn-action-custom btn-eliminar" title="Eliminar Registro" data-bs-toggle="modal" data-bs-target="#modalEliminar<?php echo $row['id']; ?>">
                                                            <i class="bi bi-trash3-fill fs-6"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr id="sinRegistrosBD">
                                        <td colspan="7" class="text-center py-5" style="color: var(--text-muted);">
                                            <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                                            No hay personal registrado en la base de datos.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- MODALES DE ELIMINACIÓN PARA CADA REGISTRO -->
    <?php if (count($personal) > 0): ?>
        <?php foreach ($personal as $row): ?>
            <div class="modal fade" id="modalEliminar<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: var(--card-bg); color: var(--text-main);">
                        <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
                            <h5 class="modal-title fw-bold fs-6"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Confirmar Eliminación</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center py-4">
                            <i class="bi bi-trash3-fill text-danger display-4 d-block mb-3"></i>
                            <h5 class="fw-bold mb-2">¿Deseas eliminar este expediente?</h5>
                            <p class="small text-muted mb-0">Funcionario: <strong><?php echo htmlspecialchars(($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? '')); ?></strong></p>
                            <p class="small text-muted mb-0">Esta acción borrará permanentemente todos sus datos y respaldos asociados.</p>
                        </div>
                        <div class="modal-footer border-0 d-flex justify-content-center gap-2 py-3" style="background: var(--card-bg);">
                            <button type="button" class="btn btn-outline-secondary px-4 fw-bold rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                            <a href="consulta_personal.php?eliminar_id=<?php echo $row['id']; ?>" class="btn btn-danger px-4 fw-bold rounded-pill shadow-sm" style="background: linear-gradient(135deg, #dc2626, #b91c1c); border:none;">Sí, Eliminar</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- MODAL 1: ARCHIVOS CON ACORDEÓN MODERNO -->
    <?php if (count($personal) > 0): ?>
        <?php foreach ($personal as $row): 
            $f = $row['fotos'];
            $fam = $row['familiares'];
        ?>
            <div class="modal fade" id="modalArchivos<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden" style="background: var(--card-bg); color: var(--text-main);">
                        <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #1e3a8a, #1e40af);">
                            <h5 class="modal-title fw-bold fs-6">
                                <i class="bi bi-folder-symlink-fill text-warning fs-5 me-2"></i> Documentación y Archivos: <?php echo htmlspecialchars(($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? '')); ?>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 text-start">
                            
                            <div class="accordion" id="accordionArchivos<?php echo $row['id']; ?>">
                                
                                <div class="accordion-item shadow-sm">
                                    <h2 class="accordion-header" id="headingIdent<?php echo $row['id']; ?>">
                                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseIdent<?php echo $row['id']; ?>" aria-expanded="false" aria-controls="collapseIdent<?php echo $row['id']; ?>">
                                            <i class="bi bi-person-badge text-primary fs-5 me-2"></i> 1. Cédula, Carnet y Partida Personal
                                        </button>
                                    </h2>
                                    <div id="collapseIdent<?php echo $row['id']; ?>" class="accordion-collapse collapse" aria-labelledby="headingIdent<?php echo $row['id']; ?>" data-bs-parent="#accordionArchivos<?php echo $row['id']; ?>">
                                        <div class="accordion-body">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <?php if (!empty($f['foto_cedula']) && file_exists($f['foto_cedula'])): ?>
                                                        <div class="preview-pill" style="cursor: pointer;" onclick="verSoporte('<?php echo $f['foto_cedula']; ?>', 'Cédula de Identidad')">
                                                            <span class="fw-semibold small">Cédula Personal</span>
                                                            <img src="<?php echo $f['foto_cedula']; ?>" class="img-thumb-modern" alt="Cédula">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="preview-pill opacity-50"><span class="small">Cédula Personal</span><span class="text-muted small">No adjunta</span></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4">
                                                    <?php if (!empty($f['foto_carnet_frente']) && file_exists($f['foto_carnet_frente'])): ?>
                                                        <div class="preview-pill" style="cursor: pointer;" onclick="verSoporte('<?php echo $f['foto_carnet_frente']; ?>', 'Carnet Frente')">
                                                            <span class="fw-semibold small">Carnet (Frente)</span>
                                                            <img src="<?php echo $f['foto_carnet_frente']; ?>" class="img-thumb-modern" alt="Carnet Frente">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="preview-pill opacity-50"><span class="small">Carnet (Frente)</span><span class="text-muted small">No adjunta</span></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4">
                                                    <?php if (!empty($f['foto_carnet_reverso']) && file_exists($f['foto_carnet_reverso'])): ?>
                                                        <div class="preview-pill" style="cursor: pointer;" onclick="verSoporte('<?php echo $f['foto_carnet_reverso']; ?>', 'Carnet Reverso')">
                                                            <span class="fw-semibold small">Carnet (Reverso)</span>
                                                            <img src="<?php echo $f['foto_carnet_reverso']; ?>" class="img-thumb-modern" alt="Carnet Reverso">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="preview-pill opacity-50"><span class="small">Carnet (Reverso)</span><span class="text-muted small">No adjunta</span></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <?php if (!empty($f['foto_partida_frente']) && file_exists($f['foto_partida_frente'])): ?>
                                                        <div class="preview-pill" style="cursor: pointer;" onclick="verSoporte('<?php echo $f['foto_partida_frente']; ?>', 'Partida de Nacimiento (Frente)')">
                                                            <span class="fw-semibold small">Partida Nac. (Frente)</span>
                                                            <img src="<?php echo $f['foto_partida_frente']; ?>" class="img-thumb-modern" alt="Partida Frente">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="preview-pill opacity-50"><span class="small">Partida Nac. (Frente)</span><span class="text-muted small">No adjunta</span></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <?php if (!empty($f['foto_partida_reverso']) && file_exists($f['foto_partida_reverso'])): ?>
                                                        <div class="preview-pill" style="cursor: pointer;" onclick="verSoporte('<?php echo $f['foto_partida_reverso']; ?>', 'Partida de Nacimiento (Reverso)')">
                                                            <span class="fw-semibold small">Partida Nac. (Reverso)</span>
                                                            <img src="<?php echo $f['foto_partida_reverso']; ?>" class="img-thumb-modern" alt="Partida Reverso">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="preview-pill opacity-50"><span class="small">Partida Nac. (Reverso)</span><span class="text-muted small">No adjunta</span></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item shadow-sm">
                                    <h2 class="accordion-header" id="headingFotos<?php echo $row['id']; ?>">
                                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFotos<?php echo $row['id']; ?>" aria-expanded="false" aria-controls="collapseFotos<?php echo $row['id']; ?>">
                                            <i class="bi bi-camera-fill text-success fs-5 me-2"></i> 2. Fotografías Oficiales del Funcionario
                                        </button>
                                    </h2>
                                    <div id="collapseFotos<?php echo $row['id']; ?>" class="accordion-collapse collapse" aria-labelledby="headingFotos<?php echo $row['id']; ?>" data-bs-parent="#accordionArchivos<?php echo $row['id']; ?>">
                                        <div class="accordion-body">
                                            <?php 
                                            $etiquetas_fotos = [
                                                'foto_gala' => 'Foto Gala',
                                                'foto_patriota_tiuna' => 'Foto Patriota Tiuna',
                                                'foto_interior_cuartel' => 'Foto Interior de Cuartel',
                                                'foto_civil' => 'Foto Civil'
                                            ];
                                            ?>
                                            <div class="row g-3">
                                                <?php foreach ($etiquetas_fotos as $key => $label): ?>
                                                    <div class="col-md-6">
                                                        <?php if (!empty($f[$key]) && file_exists($f[$key])): ?>
                                                            <div class="preview-pill" style="cursor: pointer;" onclick="verSoporte('<?php echo $f[$key]; ?>', '<?php echo $label; ?>')">
                                                                <span class="fw-semibold small"><?php echo $label; ?></span>
                                                                <img src="<?php echo $f[$key]; ?>" class="img-thumb-modern" alt="<?php echo $label; ?>">
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="preview-pill opacity-50">
                                                                <span class="fw-semibold small"><?php echo $label; ?></span>
                                                                <span class="text-muted small">No adjunta</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item shadow-sm">
                                    <h2 class="accordion-header" id="headingFam<?php echo $row['id']; ?>">
                                        <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFam<?php echo $row['id']; ?>" aria-expanded="false" aria-controls="collapseFam<?php echo $row['id']; ?>">
                                            <i class="bi bi-people-fill text-warning fs-5 me-2"></i> 3. Documentación de Familiares (Cónyuge, Hijos, Padres)
                                        </button>
                                    </h2>
                                    <div id="collapseFam<?php echo $row['id']; ?>" class="accordion-collapse collapse" aria-labelledby="headingFam<?php echo $row['id']; ?>" data-bs-parent="#accordionArchivos<?php echo $row['id']; ?>">
                                        <div class="accordion-body">
                                            
                                            <h6 class="text-info fw-bold small text-uppercase mb-3"><i class="bi bi-person-heart me-1"></i> Cónyuge y Padres</h6>
                                            <div class="row g-3 mb-4">
                                                <div class="col-md-4">
                                                    <?php if (!empty($fam['foto_cedula_conyuge']) && file_exists($fam['foto_cedula_conyuge'])): ?>
                                                        <div class="preview-pill" style="cursor: pointer;" onclick="verSoporte('<?php echo $fam['foto_cedula_conyuge']; ?>', 'Cédula Cónyuge')">
                                                            <span class="fw-semibold small">Cédula Cónyuge</span>
                                                            <img src="<?php echo $fam['foto_cedula_conyuge']; ?>" class="img-thumb-modern" alt="Cédula Cónyuge">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="preview-pill opacity-50"><span class="small">Cédula Cónyuge</span><span class="text-muted small">No adjunta</span></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4">
                                                    <?php if (!empty($fam['foto_cedula_padre']) && file_exists($fam['foto_cedula_padre'])): ?>
                                                        <div class="preview-pill" style="cursor: pointer;" onclick="verSoporte('<?php echo $fam['foto_cedula_padre']; ?>', 'Cédula Padre')">
                                                            <span class="fw-semibold small">Cédula Padre</span>
                                                            <img src="<?php echo $fam['foto_cedula_padre']; ?>" class="img-thumb-modern" alt="Cédula Padre">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="preview-pill opacity-50"><span class="small">Cédula Padre</span><span class="text-muted small">No adjunta</span></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4">
                                                    <?php if (!empty($fam['foto_cedula_madre']) && file_exists($fam['foto_cedula_madre'])): ?>
                                                        <div class="preview-pill" style="cursor: pointer;" onclick="verSoporte('<?php echo $fam['foto_cedula_madre']; ?>', 'Cédula Madre')">
                                                            <span class="fw-semibold small">Cédula Madre</span>
                                                            <img src="<?php echo $fam['foto_cedula_madre']; ?>" class="img-thumb-modern" alt="Cédula Madre">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="preview-pill opacity-50"><span class="small">Cédula Madre</span><span class="text-muted small">No adjunta</span></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <h6 class="text-info fw-bold small text-uppercase mb-3"><i class="bi bi-child me-1"></i> Hijos Registrados</h6>
                                            <?php $hijos_lista = $row['hijos']; ?>
                                            <?php if (count($hijos_lista) > 0): ?>
                                                <div class="row g-3">
                                                    <?php foreach ($hijos_lista as $idx => $hijo): 
                                                        $edad_hijo = (int)($hijo['edad'] ?? 0);
                                                    ?>
                                                        <div class="col-12 hijo-card-box">
                                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                                <span class="fw-bold text-primary small">Hijo(a) #<?php echo ($idx + 1); ?>: <?php echo htmlspecialchars(($hijo['nombres'] ?? '') . ' ' . ($hijo['apellidos'] ?? '')); ?></span>
                                                                <span class="badge bg-info text-dark fw-bold px-2 py-1">Edad: <?php echo $edad_hijo; ?> años</span>
                                                            </div>
                                                            <div class="row g-2">
                                                                <?php if ($edad_hijo >= 9): ?>
                                                                    <div class="col-md-12">
                                                                        <?php if (!empty($hijo['cedula_doc']) && file_exists($hijo['cedula_doc'])): ?>
                                                                            <div class="preview-pill mb-0" style="cursor: pointer;" onclick="verSoporte('<?php echo $hijo['cedula_doc']; ?>', 'Cédula de Identidad')">
                                                                                <span class="small fw-semibold">Cédula de Identidad</span>
                                                                                <img src="<?php echo $hijo['cedula_doc']; ?>" class="img-thumb-modern" alt="Cédula Hijo">
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="preview-pill opacity-50 mb-0"><span class="small fw-semibold">Cédula de Identidad</span><span class="text-muted small">No adjunta</span></div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="col-md-6">
                                                                        <?php if (!empty($hijo['partida_frente']) && file_exists($hijo['partida_frente'])): ?>
                                                                            <div class="preview-pill mb-0" style="cursor: pointer;" onclick="verSoporte('<?php echo $hijo['partida_frente']; ?>', 'Acta de Nacimiento (Frente)')">
                                                                                <span class="small fw-semibold">Acta (Frente)</span>
                                                                                <img src="<?php echo $hijo['partida_frente']; ?>" class="img-thumb-modern" alt="Acta Frente">
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="preview-pill opacity-50 mb-0"><span class="small fw-semibold">Acta (Frente)</span><span class="text-muted small">No adjunta</span></div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <?php if (!empty($hijo['partida_reverso']) && file_exists($hijo['partida_reverso'])): ?>
                                                                            <div class="preview-pill mb-0" style="cursor: pointer;" onclick="verSoporte('<?php echo $hijo['partida_reverso']; ?>', 'Acta de Nacimiento (Reverso)')">
                                                                                <span class="small fw-semibold">Acta (Reverso)</span>
                                                                                <img src="<?php echo $hijo['partida_reverso']; ?>" class="img-thumb-modern" alt="Acta Reverso">
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="preview-pill opacity-50 mb-0"><span class="small fw-semibold">Acta (Reverso)</span><span class="text-muted small">No adjunta</span></div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="small text-muted mb-0 italic">No registra hijos en este expediente.</p>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                        <div class="modal-footer border-0 px-4 py-3" style="background: var(--card-bg);">
                            <button type="button" class="btn btn-secondary rounded-pill fw-bold px-4" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- MODAL 2: FORMACIÓN -->
    <?php if (count($personal) > 0): ?>
        <?php foreach ($personal as $row): ?>
            <div class="modal fade" id="modalCursos<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden" style="background: var(--card-bg); color: var(--text-main);">
                        <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #0d9488, #0f766e);">
                            <h5 class="modal-title fw-bold fs-6">
                                <i class="bi bi-mortarboard-fill text-warning fs-5 me-2"></i> Historial de Formación: <?php echo htmlspecialchars(($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? '')); ?>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 text-start">

                            <?php 
                            $cursos_lista = $row['cursos'];

                            $titulos = array_filter($cursos_lista, fn($c) => strcasecmp($c['tipo'] ?? '', 'Titulo') === 0);
                            $cursos = array_filter($cursos_lista, fn($c) => strcasecmp($c['tipo'] ?? '', 'Curso') === 0);
                            $talleres = array_filter($cursos_lista, fn($c) => strcasecmp($c['tipo'] ?? '', 'Taller') === 0);
                            ?>

                            <?php if (count($cursos_lista) > 0): ?>
                                
                                <?php if (count($titulos) > 0): ?>
                                    <div class="mb-4">
                                        <span class="badge bg-secondary mb-2 text-uppercase fw-bold" style="font-size: 0.7rem;">Títulos Académicos</span>
                                        <?php foreach ($titulos as $t): ?>
                                            <div class="preview-pill d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="fw-bold text-main d-block"><?php echo htmlspecialchars($t['nombre']); ?></span>
                                                    <small class="text-muted"><?php echo htmlspecialchars($t['institucion'] ?? 'Sin institución'); ?> &bull; Año: <?php echo htmlspecialchars($t['ano'] ?? 'S/A'); ?></small>
                                                </div>
                                                <div>
                                                    <?php if (!empty($t['soporte']) && file_exists($t['soporte'])): ?>
                                                        <button type="button" class="btn btn-sm btn-primary fw-semibold px-3 py-1 rounded-pill" onclick="verSoporte('<?php echo $t['soporte']; ?>', 'Título: <?php echo htmlspecialchars($t['nombre']); ?>')">
                                                            <i class="bi bi-eye-fill me-1"></i> Ver Soporte
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Sin soporte</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (count($cursos) > 0): ?>
                                    <div class="mb-4">
                                        <span class="badge bg-info text-dark mb-2 text-uppercase fw-bold" style="font-size: 0.7rem;">Cursos</span>
                                        <?php foreach ($cursos as $cur): ?>
                                            <div class="preview-pill d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="fw-bold text-main d-block"><?php echo htmlspecialchars($cur['nombre']); ?></span>
                                                    <small class="text-muted"><?php echo htmlspecialchars($cur['institucion'] ?? 'Sin institución'); ?> &bull; Año: <?php echo htmlspecialchars($cur['ano'] ?? 'S/A'); ?></small>
                                                </div>
                                                <div>
                                                    <?php if (!empty($cur['soporte']) && file_exists($cur['soporte'])): ?>
                                                        <button type="button" class="btn btn-sm btn-primary fw-semibold px-3 py-1 rounded-pill" onclick="verSoporte('<?php echo $cur['soporte']; ?>', 'Curso: <?php echo htmlspecialchars($cur['nombre']); ?>')">
                                                            <i class="bi bi-eye-fill me-1"></i> Ver Soporte
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Sin soporte</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (count($talleres) > 0): ?>
                                    <div class="mb-3">
                                        <span class="badge bg-warning text-dark mb-2 text-uppercase fw-bold" style="font-size: 0.7rem;">Talleres</span>
                                        <?php foreach ($talleres as $tal): ?>
                                            <div class="preview-pill d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="fw-bold text-main d-block"><?php echo htmlspecialchars($tal['nombre']); ?></span>
                                                    <small class="text-muted"><?php echo htmlspecialchars($tal['institucion'] ?? 'Sin institución'); ?> &bull; Año: <?php echo htmlspecialchars($tal['ano'] ?? 'S/A'); ?></small>
                                                </div>
                                                <div>
                                                    <?php if (!empty($tal['soporte']) && file_exists($tal['soporte'])): ?>
                                                        <button type="button" class="btn btn-sm btn-primary fw-semibold px-3 py-1 rounded-pill" onclick="verSoporte('<?php echo $tal['soporte']; ?>', 'Taller: <?php echo htmlspecialchars($tal['nombre']); ?>')">
                                                            <i class="bi bi-eye-fill me-1"></i> Ver Soporte
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Sin soporte</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>
                                <p class="small text-muted italic mb-0">No registra títulos o cursos adicionales con soporte.</p>
                            <?php endif; ?>

                        </div>
                        <div class="modal-footer border-0 px-4 py-3 d-flex justify-content-between" style="background: var(--card-bg);">
                            <a href="generar_pdf_formacion.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-danger rounded-pill fw-bold px-4 shadow-sm">
                                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Imprimir / Descargar Reporte PDF
                            </a>
                            <button type="button" class="btn btn-secondary rounded-pill fw-bold px-4" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- VISOR DE IMAGENES AMPLIADAS -->
    <div class="modal fade" id="modalVisorImagen" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: var(--card-bg); color: var(--text-main);">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e3a8a, #1e40af);">
                    <h5 class="modal-title fw-bold fs-6" id="tituloVisorImagen">Previsualización de Documento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4 bg-black bg-opacity-75 overflow-auto" style="max-height: 75vh;">
                    <img id="imagenAmpliadaSrc" src="" class="img-fluid rounded shadow" style="max-height: 70vh; object-fit: contain; transform: scale(1); transition: transform 0.3s ease;" alt="Imagen Ampliada">
                </div>
                <div class="modal-footer border-0 py-3 px-4 d-flex justify-content-between" style="background: var(--card-bg);">
                    <button type="button" class="btn btn-outline-secondary rounded-pill fw-bold px-4" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-left me-1"></i> Volver
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-info rounded-pill fw-bold px-3 text-white" onclick="alternarZoom()">
                            <i class="bi bi-zoom-in me-1"></i> Zoom
                        </button>
                        <button type="button" class="btn btn-warning rounded-pill fw-bold px-3 text-dark" onclick="abrirPantallaGigante()">
                            <i class="bi bi-arrows-fullscreen me-1"></i> Ampliar (Pantalla Completa)
                        </button>
                        <a id="btnDescargarImagen" href="" download class="btn btn-primary rounded-pill fw-bold px-3">
                            <i class="bi bi-download me-1"></i> Descargar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL SECUNDARIO: PANTALLA GIGANTE -->
    <div class="modal fade" id="modalPantallaGigante" tabindex="-1" aria-hidden="true" style="z-index: 1070;">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-black text-white">
                <div class="modal-header border-0 bg-dark bg-opacity-75 px-4 py-3">
                    <button type="button" class="btn btn-outline-light rounded-pill px-4 fw-bold" onclick="regresarAlVisorAnterior()">
                        <i class="bi bi-arrow-left me-2"></i> Volver
                    </button>
                    <h5 class="modal-title fw-bold text-center flex-grow-1" id="tituloGigante">Vista Detallada</h5>
                    <a id="btnDescargarGigante" href="" download class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="bi bi-download me-1"></i> Descargar Foto
                    </a>
                </div>
                <div class="modal-body d-flex justify-content-center align-items-center p-0">
                    <img id="imagenGiganteSrc" src="" class="img-fluid w-100 h-100 object-fit-contain" alt="Imagen Gigante">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cerrar Sesión -->
    <div class="modal fade" id="modalCerrarSesion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: var(--card-bg); color: var(--text-main);">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e3a8a, #1e40af);">
                    <h5 class="modal-title fw-bold"><i class="bi bi-shield-exclamation me-2"></i>Confirmar Salida del Sistema</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-box-arrow-right text-info display-3 d-block mb-3"></i>
                    <h5 class="fw-bold mb-2">¿Seguro que deseas cerrar sesión?</h5>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-center gap-2 py-3" style="background: var(--card-bg);">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-bold rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <a href="logout.php" class="btn btn-primary px-4 fw-bold rounded-pill shadow-sm" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border:none;">Sí, Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        let rutaImagenActual = '';
        let tituloActual = '';

        function filtrarTabla() {
            const input = document.getElementById('inputBuscador');
            const filtro = input.value.toLowerCase().trim();
            const tabla = document.getElementById('tablaPersonal');
            const filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            let visibles = 0;
            for (let i = 0; i < filas.length; i++) {
                if (filas[i].id === 'sinRegistrosBD') continue;
                if (filas[i].textContent.toLowerCase().includes(filtro)) {
                    filas[i].style.display = '';
                    visibles++;
                } else {
                    filas[i].style.display = 'none';
                }
            }
            document.getElementById('totalRegistros').textContent = visibles;
        }

        function verSoporte(urlRuta, tituloDoc) {
            const extension = urlRuta.split('.').pop().toLowerCase();
            if (extension === 'pdf') {
                window.open(urlRuta, '_blank');
            } else {
                rutaImagenActual = urlRuta;
                tituloActual = tituloDoc;

                document.getElementById('tituloVisorImagen').textContent = tituloDoc;
                const img = document.getElementById('imagenAmpliadaSrc');
                img.src = urlRuta;
                img.style.transform = "scale(1)";
                document.getElementById('btnDescargarImagen').href = urlRuta;
                
                const modalVisor = new bootstrap.Modal(document.getElementById('modalVisorImagen'));
                modalVisor.show();
            }
        }

        function alternarZoom() {
            const img = document.getElementById('imagenAmpliadaSrc');
            if (img.style.transform === "scale(1.8)") {
                img.style.transform = "scale(1)";
            } else {
                img.style.transform = "scale(1.8)";
            }
        }

        function abrirPantallaGigante() {
            const modalVisorEl = document.getElementById('modalVisorImagen');
            const modalVisor = bootstrap.Modal.getInstance(modalVisorEl);
            modalVisor.hide();

            document.getElementById('tituloGigante').textContent = tituloActual;
            document.getElementById('imagenGiganteSrc').src = rutaImagenActual;
            document.getElementById('btnDescargarGigante').href = rutaImagenActual;

            const modalGigante = new bootstrap.Modal(document.getElementById('modalPantallaGigante'));
            modalGigante.show();
        }

        function regresarAlVisorAnterior() {
            const modalGiganteEl = document.getElementById('modalPantallaGigante');
            const modalGigante = bootstrap.Modal.getInstance(modalGiganteEl);
            modalGigante.hide();

            const modalVisor = new bootstrap.Modal(document.getElementById('modalVisorImagen'));
            modalVisor.show();
        }
    </script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <?php include 'inactivity_check.php'; ?>
</body>
</html>