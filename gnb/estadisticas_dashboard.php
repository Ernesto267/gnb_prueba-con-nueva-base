<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$rol_actual = $_SESSION['usuario_rol'] ?? 'usuario';

// Consultas analíticas detalladas y extracción de listados nominativos corregidas con JOIN
try {
    $total_personal = $pdo->query("SELECT COUNT(*) FROM personal")->fetchColumn();
    $total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

    // Distribución por Componente
    $stmt_comp = $pdo->query("SELECT componente, COUNT(*) as total FROM personal GROUP BY componente");
    $por_componente = $stmt_comp->fetchAll(PDO::FETCH_ASSOC);

    // Distribución por Grupo Sanguíneo
    $stmt_sangre = $pdo->query("SELECT grupo_sang, COUNT(*) as total FROM personal WHERE grupo_sang IS NOT NULL AND grupo_sang != '' GROUP BY grupo_sang");
    $por_sangre = $stmt_sangre->fetchAll(PDO::FETCH_ASSOC);

    // Distribución por Estado Civil
    $stmt_civil = $pdo->query("SELECT estado_civil, COUNT(*) as total FROM personal GROUP BY estado_civil");
    $por_civil = $stmt_civil->fetchAll(PDO::FETCH_ASSOC);

    // Listados detallados de personal con Patologías Médicas (Uniendo 'personal' con 'personal_medico')
    $stmt_hipertensos = $pdo->query("SELECT p.grado, p.apellidos, p.nombres, p.cedula FROM personal p INNER JOIN personal_medico m ON p.id = m.personal_id WHERE m.hipertenso = 'Sí'");
    $lista_hipertensos = $stmt_hipertensos->fetchAll(PDO::FETCH_ASSOC);

    $stmt_asmaticos = $pdo->query("SELECT p.grado, p.apellidos, p.nombres, p.cedula FROM personal p INNER JOIN personal_medico m ON p.id = m.personal_id WHERE m.asma = 'Sí'");
    $lista_asmaticos = $stmt_asmaticos->fetchAll(PDO::FETCH_ASSOC);

    $stmt_sinusitis = $pdo->query("SELECT p.grado, p.apellidos, p.nombres, p.cedula FROM personal p INNER JOIN personal_medico m ON p.id = m.personal_id WHERE m.sinusitis = 'Sí'");
    $lista_sinusitis = $stmt_sinusitis->fetchAll(PDO::FETCH_ASSOC);

    $stmt_renales = $pdo->query("SELECT p.grado, p.apellidos, p.nombres, p.cedula FROM personal p INNER JOIN personal_medico m ON p.id = m.personal_id WHERE m.renales = 'Sí'");
    $lista_renales = $stmt_renales->fetchAll(PDO::FETCH_ASSOC);

    $stmt_cardiologicos = $pdo->query("SELECT p.grado, p.apellidos, p.nombres, p.cedula FROM personal p INNER JOIN personal_medico m ON p.id = m.personal_id WHERE m.cardiologicos = 'Sí'");
    $lista_cardiologicos = $stmt_cardiologicos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $total_personal = 0;
    $total_usuarios = 0;
    $por_componente = [];
    $por_sangre = [];
    $por_civil = [];
    $lista_hipertensos = $lista_asmaticos = $lista_sinusitis = $lista_renales = $lista_cardiologicos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Criminalístico N° 11 (Zulia) - Dashboard Analítico Ejecutivo</title>
    <script>
        const savedTheme = localStorage.getItem('gnb_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <style>
        [data-theme="dark"] {
            --bg-body: #0f172a; --bg-navbar: #090d16; --text-main: #f8fafc; --text-muted: #94a3b8;
            --card-bg: #1e293b; --card-border: rgba(255, 255, 255, 0.1);
        }
        [data-theme="light"] {
            --bg-body: #f1f5f9; --bg-navbar: #ffffff; --text-main: #0f172a; --text-muted: #64748b;
            --card-bg: #ffffff; --card-border: rgba(0, 0, 0, 0.1);
        }
        body { background: var(--bg-body); min-height: 100vh; font-family: 'Segoe UI', system-ui, sans-serif; color: var(--text-main); padding-bottom: 50px; }
        .navbar-gnb { background: var(--bg-navbar); border-bottom: 1px solid var(--card-border); box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
        
        .analytic-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 1.25rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .analytic-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        .hero-stats {
            background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
            border-radius: 1.5rem;
            color: white;
            padding: 2.5rem;
            box-shadow: 0 10px 25px rgba(124, 58, 237, 0.3);
        }
        .icon-box-stat {
            width: 50px; height: 50px; border-radius: 1rem; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
        }
        .progress { background-color: rgba(100, 116, 139, 0.2); border-radius: 10px; height: 12px; }
        .table-custom { color: var(--text-main); background-color: transparent; }
        .table-custom th, .table-custom td { border-color: var(--card-border); background-color: transparent; color: var(--text-main); }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark navbar-gnb shadow-lg py-2.5 sticky-top">
        <div class="container px-4">
            <a class="navbar-brand fw-bold fs-5 d-flex align-items-center" href="dashboard.php" style="color: var(--text-main);">
                <i class="bi bi-arrow-left-circle text-primary fs-4 me-2"></i> Volver al Panel General
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-white px-3 py-2 rounded-pill fw-semibold" style="background-color: #7c3aed !important;">
                    <i class="bi bi-pie-chart-fill me-1"></i> Dashboard Analítico Ejecutivo
                </span>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        
        <!-- Hero Banner Estadístico -->
        <div class="hero-stats mb-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <span class="badge bg-white bg-opacity-25 px-3 py-1 rounded-pill mb-2 fw-semibold text-white">
                    <i class="bi bi-graph-up-arrow me-1"></i> Centro de Inteligencia de Datos
                </span>
                <h1 class="fw-bold display-6 mb-1 text-white">Panel Estadístico Detallado del Personal</h1>
                <p class="text-white-50 mb-0">Métricas globales, demografía militar, recursos humanos y listados nominativos médicos.</p>
            </div>
            <div class="mt-4 mt-md-0 text-md-end bg-white bg-opacity-10 p-3 rounded-4 border border-white border-opacity-25">
                <span class="d-block text-white-50 small">Total Registros Analizados</span>
                <span class="display-5 fw-bold text-white"><?php echo $total_personal; ?></span>
            </div>
        </div>

        <!-- Fila de Tarjetas Resumen -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="analytic-card p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted d-block mb-1">Funcionarios Activos</span>
                        <h2 class="fw-bold mb-0"><?php echo $total_personal; ?></h2>
                    </div>
                    <div class="icon-box-stat bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="analytic-card p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted d-block mb-1">Cuentas de Acceso</span>
                        <h2 class="fw-bold mb-0"><?php echo $total_usuarios; ?></h2>
                    </div>
                    <div class="icon-box-stat bg-warning bg-opacity-25 text-warning border border-warning border-warning-opacity-25">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="analytic-card p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase small fw-bold text-muted d-block mb-1">Estatus del Sistema</span>
                        <h4 class="fw-bold text-success mb-0 mt-1"><i class="bi bi-check-circle-fill me-1"></i> Óptimo</h4>
                    </div>
                    <div class="icon-box-stat bg-success bg-opacity-25 text-success border border-success border-opacity-25">
                        <i class="bi bi-cpu-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila Principal de Gráficos de Distribución -->
        <div class="row g-4 mb-4">
            <!-- Componente Militar -->
            <div class="col-lg-6">
                <div class="analytic-card p-4 h-100">
                    <h5 class="fw-bold mb-4 d-flex align-items-center">
                        <i class="bi bi-shield-shaded text-primary fs-4 me-2"></i> Distribución por Componente Militar
                    </h5>
                    <?php if (count($por_componente) > 0): foreach ($por_componente as $comp): 
                        $porcentaje = ($total_personal > 0) ? round(($comp['total'] / $total_personal) * 100, 1) : 0;
                    ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small fw-bold mb-1">
                                <span><?php echo htmlspecialchars($comp['componente']); ?></span>
                                <span class="text-primary"><?php echo $comp['total']; ?> efectivos (<?php echo $porcentaje; ?>%)</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary rounded-pill" role="progressbar" style="width: <?php echo $porcentaje; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="text-muted small">No hay componentes registrados.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Estado Civil -->
            <div class="col-lg-6">
                <div class="analytic-card p-4 h-100">
                    <h5 class="fw-bold mb-4 d-flex align-items-center">
                        <i class="bi bi-people-fill text-info fs-4 me-2"></i> Distribución por Estado Civil
                    </h5>
                    <?php if (count($por_civil) > 0): foreach ($por_civil as $civil): 
                        $porcentaje_c = ($total_personal > 0) ? round(($civil['total'] / $total_personal) * 100, 1) : 0;
                    ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small fw-bold mb-1">
                                <span><?php echo htmlspecialchars($civil['estado_civil']); ?></span>
                                <span class="text-info"><?php echo $civil['total']; ?> (<?php echo $porcentaje_c; ?>%)</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-info rounded-pill" role="progressbar" style="width: <?php echo $porcentaje_c; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="text-muted small">No hay estados civiles registrados.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Grupo Sanguíneo -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="analytic-card p-4">
                    <h5 class="fw-bold mb-4 d-flex align-items-center">
                        <i class="bi bi-droplet-fill text-danger fs-4 me-2"></i> Mapa Sanguíneo del Personal
                    </h5>
                    <div class="row g-3">
                        <?php if (count($por_sangre) > 0): foreach ($por_sangre as $sangre): ?>
                            <div class="col-md-3 col-6">
                                <div class="p-3 rounded-4 border text-center" style="background: var(--bg-body);">
                                    <span class="small text-muted d-block mb-1">Grupo</span>
                                    <h3 class="fw-bold text-danger mb-0"><?php echo htmlspecialchars($sangre['grupo_sang']); ?></h3>
                                    <span class="badge bg-danger bg-opacity-10 text-danger mt-2 px-3 py-1 rounded-pill fw-bold"><?php echo $sangre['total']; ?> efectivos</span>
                                </div>
                            </div>
                        <?php endforeach; else: ?>
                            <div class="col-12"><p class="text-muted small">Sin datos de grupos sanguíneos registrados.</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DETALLADA: NOMINAS Y LISTADOS DE PATOLOGÍAS MÉDICAS -->
        <div class="row g-4">
            <div class="col-12">
                <div class="analytic-card p-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center">
                        <i class="bi bi-journal-medical text-success fs-4 me-2"></i> Listados Detallados por Patología Médica
                    </h5>
                    <p class="small text-muted mb-4">A continuación se detallan los funcionarios que presentan alguna condición médica registrada en el sistema:</p>

                    <ul class="nav nav-pills mb-4 gap-2" id="medTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold rounded-pill px-4" id="hiper-tab" data-bs-toggle="pill" data-bs-target="#hiper" type="button" role="tab">
                                Hipertensos (<?php echo count($lista_hipertensos); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold rounded-pill px-4" id="asma-tab" data-bs-toggle="pill" data-bs-target="#asma" type="button" role="tab">
                                Asmáticos (<?php echo count($lista_asmaticos); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold rounded-pill px-4" id="sinu-tab" data-bs-toggle="pill" data-bs-target="#sinu" type="button" role="tab">
                                Sinusitis (<?php echo count($lista_sinusitis); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold rounded-pill px-4" id="renal-tab" data-bs-toggle="pill" data-bs-target="#renal" type="button" role="tab">
                                Renales (<?php echo count($lista_renales); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold rounded-pill px-4" id="cardio-tab" data-bs-toggle="pill" data-bs-target="#cardio" type="button" role="tab">
                                Cardiológicos (<?php echo count($lista_cardiologicos); ?>)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="medTabsContent">
                        <!-- Hipertensos -->
                        <div class="tab-pane fade show active" id="hiper" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-custom align-middle">
                                    <thead>
                                        <tr>
                                            <th>Grado / Jerarquía</th>
                                            <th>Apellidos y Nombres</th>
                                            <th>Cédula de Identidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($lista_hipertensos) > 0): foreach($lista_hipertensos as $f): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($f['grado']); ?></td>
                                            <td><?php echo htmlspecialchars($f['apellidos'] . ', ' . $f['nombres']); ?></td>
                                            <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars($f['cedula']); ?></span></td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                        <tr><td colspan="3" class="text-center text-muted py-3">No hay funcionarios registrados con hipertensión.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Asmáticos -->
                        <div class="tab-pane fade" id="asma" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-custom align-middle">
                                    <thead>
                                        <tr>
                                            <th>Grado / Jerarquía</th>
                                            <th>Apellidos y Nombres</th>
                                            <th>Cédula de Identidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($lista_asmaticos) > 0): foreach($lista_asmaticos as $f): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($f['grado']); ?></td>
                                            <td><?php echo htmlspecialchars($f['apellidos'] . ', ' . $f['nombres']); ?></td>
                                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($f['cedula']); ?></span></td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                        <tr><td colspan="3" class="text-center text-muted py-3">No hay funcionarios registrados con asma.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Sinusitis -->
                        <div class="tab-pane fade" id="sinu" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-custom align-middle">
                                    <thead>
                                        <tr>
                                            <th>Grado / Jerarquía</th>
                                            <th>Apellidos y Nombres</th>
                                            <th>Cédula de Identidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($lista_sinusitis) > 0): foreach($lista_sinusitis as $f): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($f['grado']); ?></td>
                                            <td><?php echo htmlspecialchars($f['apellidos'] . ', ' . $f['nombres']); ?></td>
                                            <td><span class="badge bg-primary text-white"><?php echo htmlspecialchars($f['cedula']); ?></span></td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                        <tr><td colspan="3" class="text-center text-muted py-3">No hay funcionarios registrados con sinusitis.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Renales -->
                        <div class="tab-pane fade" id="renal" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-custom align-middle">
                                    <thead>
                                        <tr>
                                            <th>Grado / Jerarquía</th>
                                            <th>Apellidos y Nombres</th>
                                            <th>Cédula de Identidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($lista_renales) > 0): foreach($lista_renales as $f): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($f['grado']); ?></td>
                                            <td><?php echo htmlspecialchars($f['apellidos'] . ', ' . $f['nombres']); ?></td>
                                            <td><span class="badge bg-danger text-white"><?php echo htmlspecialchars($f['cedula']); ?></span></td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                        <tr><td colspan="3" class="text-center text-muted py-3">No hay funcionarios registrados con afecciones renales.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Cardiológicos -->
                        <div class="tab-pane fade" id="cardio" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-custom align-middle">
                                    <thead>
                                        <tr>
                                            <th>Grado / Jerarquía</th>
                                            <th>Apellidos y Nombres</th>
                                            <th>Cédula de Identidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($lista_cardiologicos) > 0): foreach($lista_cardiologicos as $f): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($f['grado']); ?></td>
                                            <td><?php echo htmlspecialchars($f['apellidos'] . ', ' . $f['nombres']); ?></td>
                                            <td><span class="badge bg-danger text-white"><?php echo htmlspecialchars($f['cedula']); ?></span></td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                        <tr><td colspan="3" class="text-center text-muted py-3">No hay funcionarios registrados con antecedentes cardiológicos.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <?php include 'inactivity_check.php'; ?>
</body>
</html>