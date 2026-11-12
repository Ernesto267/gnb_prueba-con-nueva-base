<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Criminalístico N° 11 (Zulia) - Exportar a Excel Detallado</title>
    <script>
        const savedTheme = localStorage.getItem('gnb_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <!-- ENLACES LOCALES (Funcionan 100% sin internet) -->
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
            --box-active-bg: rgba(22, 163, 74, 0.2);
            --box-active-border: #22c55e;
            --box-active-text: #4ade80;
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
            --box-active-bg: #dcfce7;
            --box-active-border: #16a34a;
            --box-active-text: #15803d;
        }

        body { 
            background-color: var(--bg-body); 
            min-height: 100vh; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            color: var(--text-main); 
            transition: background 0.3s ease, color 0.3s ease; 
        }

        .text-muted { color: var(--text-muted) !important; }

        .navbar-gnb { 
            background: var(--bg-navbar); 
            border-bottom: 1px solid var(--card-border); 
            box-shadow: 0 4px 25px rgba(0,0,0,0.1); 
        }

        .navbar-brand img { 
            width: 140px; height: 140px; object-fit: contain; position: relative; top: 4px; 
            margin-top: -40px; margin-bottom: -40px; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }

        .step-card { 
            border-radius: 1.5rem; 
            box-shadow: var(--card-shadow); 
            background: var(--card-bg); 
            border: 1.5px solid var(--card-border); 
            color: var(--text-main); 
        }

        .form-control, .form-select { 
            background-color: var(--input-bg) !important; 
            color: var(--text-main) !important; 
            border: 1.5px solid var(--card-border) !important; 
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

        .box-section-custom { 
            background-color: var(--section-bg); 
            border: 1.5px solid var(--card-border); 
            border-radius: 1rem; 
            padding: 1.25rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .opcion-box { 
            background: var(--section-bg); 
            border: 1.5px solid var(--card-border); 
            border-radius: 0.85rem; 
            padding: 14px 18px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            height: 100%; 
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            position: relative;
        }
        .opcion-box:hover {
            border-color: #3b82f6 !important;
            transform: translateY(-1px);
        }
        .opcion-box span.label-texto {
            color: var(--text-main) !important;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* DISEÑO ACTIVO IMPACTANTE (CUANDO SE SELECCIONA SÍ) */
        .opcion-box.active-option {
            background-color: var(--box-active-bg) !important;
            border: 2px solid var(--box-active-border) !important;
            box-shadow: 0 4px 15px rgba(22, 163, 74, 0.3);
        }
        .opcion-box.active-option span.label-texto {
            color: var(--box-active-text) !important;
            font-weight: 700;
        }
        /* Icono check oculto por defecto, visible cuando está activo */
        .check-icon {
            display: none;
            color: #16a34a;
            font-size: 1.1rem;
        }
        .opcion-box.active-option .check-icon {
            display: inline-block;
        }

        .seccion-titulo {
            border-bottom: 2px solid var(--card-border);
            padding-bottom: 0.5rem;
            letter-spacing: -0.3px;
        }

        .btn-nav-custom {
            background-color: transparent;
            border: 1px solid var(--card-border);
            color: var(--text-main) !important;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-nav-custom:hover {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6 !important;
        }
        
        .btn-preset {
            background-color: var(--card-bg) !important;
            border: 1.5px solid var(--card-border);
            transition: all 0.25s ease;
        }
        .btn-preset span {
            color: var(--text-main) !important;
            font-weight: 700;
        }
        .btn-preset:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            border-color: #3b82f6 !important;
            background-color: var(--card-bg) !important;
        }
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
                
                <div class="card step-card p-4 p-md-5">
                    <div class="d-flex align-items-center mb-4 pb-3 border-bottom" style="border-color: var(--card-border) !important;">
                        <div class="bg-success bg-opacity-10 p-3 rounded-4 me-3 text-success shadow-sm">
                            <i class="bi bi-file-earmark-excel-fill fs-2"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-1" style="color: var(--text-main);">Exportar Reporte a Excel</h3>
                            <p class="mb-0 text-muted">Utilice los accesos directos para descarga inmediata o configure un reporte personalizado detallado.</p>
                        </div>
                    </div>

                    <!-- DESCARGA DIRECTA (PRESETS) -->
                    <div class="mb-5 p-4 box-section-custom">
                        <h5 class="fw-bold mb-3 text-success d-flex align-items-center">
                            <i class="bi bi-lightning-charge-fill me-2"></i> Descarga Directa de Relaciones (Todos los Funcionarios)
                        </h5>
                        <p class="small text-muted mb-3">Haga clic en cualquiera de los siguientes botones para descargar de inmediato el reporte preconfigurado:</p>
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-3">
                                <a href="procesar_exportacion.php?preset=datos_personales" target="_blank" class="btn btn-outline-primary w-100 py-3 rounded-4 btn-preset d-flex flex-column align-items-center gap-1 shadow-sm">
                                    <i class="bi bi-person-badge fs-2 text-primary"></i>
                                    <span>Datos Personales</span>
                                </a>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <a href="procesar_exportacion.php?preset=relacion_profesional" target="_blank" class="btn btn-outline-success w-100 py-3 rounded-4 btn-preset d-flex flex-column align-items-center gap-1 shadow-sm">
                                    <i class="bi bi-shield-shaded fs-2 text-success"></i>
                                    <span>Relación Profesional</span>
                                </a>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <a href="procesar_exportacion.php?preset=relacion_uniformes" target="_blank" class="btn btn-outline-warning w-100 py-3 rounded-4 btn-preset d-flex flex-column align-items-center gap-1 shadow-sm">
                                    <i class="bi bi-person-gear fs-2 text-warning"></i>
                                    <span>Relación Uniformes</span>
                                </a>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <a href="procesar_exportacion.php?preset=relacion_hijos" target="_blank" class="btn btn-outline-info w-100 py-3 rounded-4 btn-preset d-flex flex-column align-items-center gap-1 shadow-sm">
                                    <i class="bi bi-people-fill fs-2 text-info"></i>
                                    <span>Relación Hijos</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4" style="border-color: var(--card-border);">

                    <!-- FORMULARIO MANUAL DETALLADO -->
                    <h5 class="fw-bold mb-3" style="color: var(--text-main);"><i class="bi bi-sliders me-2 text-primary"></i>Generador de Reporte Personalizado (Manual)</h5>

                    <form action="procesar_exportacion.php" method="POST" target="_blank">
                        <input type="hidden" name="orden_columnas" id="orden_columnas" value="">
                        
                        <div class="mb-4 p-4 box-section-custom">
                            <h6 class="fw-bold mb-3" style="color: var(--text-main);"><i class="bi bi-funnel me-2 text-primary"></i>Selección de Funcionario o Grupo</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <select class="form-select" name="funcionario_id">
                                        <option value="todos" selected>-- Todos los funcionarios registrados --</option>
                                        <option value="hombres_todos">Todos los funcionarios (Hombres)</option>
                                        <option value="mujeres_todas">Todos los funcionarios (Mujeres)</option>
                                        <optgroup label="Funcionarios Individuales">
                                            <?php
                                            $stmt_p = $pdo->query("SELECT id, nombres, apellidos, cedula FROM personal ORDER BY apellidos ASC");
                                            while ($p = $stmt_p->fetch(PDO::FETCH_ASSOC)) {
                                                echo "<option value='{$p['id']}'>{$p['apellidos']} {$p['nombres']} (C.I: {$p['cedula']})</option>";
                                            }
                                            ?>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- 1. Datos de Identificación -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3 seccion-titulo">
                                <h6 class="fw-bold text-primary mb-0"><i class="bi bi-person-vcard me-2"></i>1. Datos de Identificación Adicionales</h6>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary fw-semibold" onclick="toggleSeccion('sec-id', 'si')">Todo</button>
                                    <button type="button" class="btn btn-outline-secondary fw-semibold" onclick="toggleSeccion('sec-id', 'no')">Ninguno</button>
                                </div>
                            </div>
                            <div class="row g-3" id="sec-id">
                                <?php 
                                $campos_id = [
                                    'col_primer_nombre' => 'Primer Nombre',
                                    'col_segundo_nombre' => 'Segundo Nombre',
                                    'col_primer_apellido' => 'Primer Apellido',
                                    'col_segundo_apellido' => 'Segundo Apellido',
                                    'col_cedula' => 'Cédula de Identidad',
                                    'col_numero_carnet' => 'Número de Carnet', 
                                    'col_sexo' => 'Sexo', 
                                    'col_estado_civil' => 'Estado Civil',
                                    'col_fecha_nacimiento' => 'Fecha de Nacimiento', 
                                    'col_edad' => 'Edad', 
                                    'col_grupo_sang' => 'Grupo Sanguíneo',
                                    'col_lugar_nacimiento' => 'Lugar de Nacimiento'
                                ];
                                foreach ($campos_id as $key => $label): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="opcion-box" id="box-<?php echo $key; ?>">
                                            <span class="label-texto">
                                                <i class="bi bi-check-circle-fill check-icon"></i> <?php echo $label; ?>
                                            </span>
                                            <select class="form-select form-select-sm ms-2" name="<?php echo $key; ?>" style="width: 90px;" onchange="actualizarEstiloBox('<?php echo $key; ?>')">
                                                <option value="no" selected>No</option>
                                                <option value="si">Sí</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 2. Carrera y Tallas Adicionales -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3 seccion-titulo">
                                <h6 class="fw-bold text-primary mb-0"><i class="bi bi-shield-shaded me-2"></i>2. Carrera Militar y Tallas Adicionales</h6>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary fw-semibold" onclick="toggleSeccion('sec-carrera', 'si')">Todo</button>
                                    <button type="button" class="btn btn-outline-secondary fw-semibold" onclick="toggleSeccion('sec-carrera', 'no')">Ninguno</button>
                                </div>
                            </div>
                            <div class="row g-3" id="sec-carrera">
                                <?php 
                                $campos_carrera = [
                                    'col_especialidad' => 'Especialidad', 'col_escuela_graduacion' => 'Escuela de Graduación', 'col_promocion' => 'Promoción',
                                    'col_ano_graduacion' => 'Año de Graduación', 'col_fecha_ingreso' => 'Fecha de Ingreso',
                                    'col_anos_servicio' => 'Años de Servicio', 'col_meses_servicio' => 'Meses de Servicio', 'col_talla_zapato' => 'Talla Zapato',
                                    'col_talla_camisa' => 'Talla Camisa', 'col_talla_pantalon' => 'Talla Pantalón', 'col_talla_gorra' => 'Talla Gorra',
                                    'col_talla_patriota' => 'Talla Patriota', 'col_exposicion' => 'Exposición / Observaciones'
                                ];
                                foreach ($campos_carrera as $key => $label): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="opcion-box" id="box-<?php echo $key; ?>">
                                            <span class="label-texto">
                                                <i class="bi bi-check-circle-fill check-icon"></i> <?php echo $label; ?>
                                            </span>
                                            <select class="form-select form-select-sm ms-2" name="<?php echo $key; ?>" style="width: 90px;" onchange="actualizarEstiloBox('<?php echo $key; ?>')">
                                                <option value="no" selected>No</option>
                                                <option value="si">Sí</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 3. Contactos -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3 seccion-titulo">
                                <h6 class="fw-bold text-primary mb-0"><i class="bi bi-geo-alt me-2"></i>3. Contactos y Direcciones</h6>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary fw-semibold" onclick="toggleSeccion('sec-contacto', 'si')">Todo</button>
                                    <button type="button" class="btn btn-outline-secondary fw-semibold" onclick="toggleSeccion('sec-contacto', 'no')">Ninguno</button>
                                </div>
                            </div>
                            <div class="row g-3" id="sec-contacto">
                                <?php 
                                $campos_contacto = [
                                    'col_telefono_celular' => 'Teléfono Celular', 
                                    'col_telefono_habitacion' => 'Teléfono Habitación',
                                    'col_contacto_emergencia_telefono' => 'Teléfono de Emergencia', 
                                    'col_correo' => 'Correo Electrónico',
                                    'col_direccion' => 'Dirección de Habitación', 
                                    'col_direccion_emergencia' => 'Dirección de Emergencia'
                                ];
                                foreach ($campos_contacto as $key => $label): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="opcion-box" id="box-<?php echo $key; ?>">
                                            <span class="label-texto">
                                                <i class="bi bi-check-circle-fill check-icon"></i> <?php echo $label; ?>
                                            </span>
                                            <select class="form-select form-select-sm ms-2" name="<?php echo $key; ?>" style="width: 90px;" onchange="actualizarEstiloBox('<?php echo $key; ?>')">
                                                <option value="no" selected>No</option>
                                                <option value="si">Sí</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 4. Fotografías Oficiales -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3 seccion-titulo">
                                <h6 class="fw-bold text-primary mb-0"><i class="bi bi-camera me-2"></i>4. Fotografías Oficiales</h6>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary fw-semibold" onclick="toggleSeccion('sec-fotos', 'si')">Todo</button>
                                    <button type="button" class="btn btn-outline-secondary fw-semibold" onclick="toggleSeccion('sec-fotos', 'no')">Ninguno</button>
                                </div>
                            </div>
                            <div class="row g-3" id="sec-fotos">
                                <?php 
                                $campos_fotos = [
                                    'col_foto_gala' => 'Foto Gala', 
                                    'col_foto_patriota_tiuna' => 'Foto Patriota Tiuna', 
                                    'col_foto_interior_cuartel' => 'Foto Interior de Cuartel',
                                    'col_foto_civil' => 'Foto Civil'
                                ];
                                foreach ($campos_fotos as $key => $label): ?>
                                    <div class="col-md-6 col-lg-6">
                                        <div class="opcion-box" id="box-<?php echo $key; ?>">
                                            <span class="label-texto">
                                                <i class="bi bi-check-circle-fill check-icon"></i> <?php echo $label; ?>
                                            </span>
                                            <select class="form-select form-select-sm ms-2" name="<?php echo $key; ?>" style="width: 90px;" onchange="actualizarEstiloBox('<?php echo $key; ?>')">
                                                <option value="no" selected>No</option>
                                                <option value="si">Sí</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 5. Datos Familiares -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3 seccion-titulo">
                                <h6 class="fw-bold text-primary mb-0"><i class="bi bi-people-fill me-2"></i>5. Datos Familiares</h6>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary fw-semibold" onclick="toggleSeccion('sec-familia', 'si')">Todo</button>
                                    <button type="button" class="btn btn-outline-secondary fw-semibold" onclick="toggleSeccion('sec-familia', 'no')">Ninguno</button>
                                </div>
                            </div>
                            <div class="row g-3" id="sec-familia">
                                <?php 
                                $campos_familia = [
                                    'col_nombre_conyuge' => 'Nombre del Cónyuge', 'col_cedula_conyuge' => 'Cédula del Cónyuge', 'col_telefono_conyuge' => 'Teléfono del Cónyuge',
                                    'col_num_hijos' => 'Número de Hijos', 'col_hijos_data' => 'Detalle Completo de Hijos', 'col_nombre_padre' => 'Nombre del Padre',
                                    'col_cedula_padre' => 'Cédula del Padre', 'col_padre_vive' => '¿Padre Vive?', 'col_nombre_madre' => 'Nombre de la Madre',
                                    'col_cedula_madre' => 'Cédula de la Madre', 'col_madre_vive' => '¿Madre Vive?'
                                ];
                                foreach ($campos_familia as $key => $label): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="opcion-box" id="box-<?php echo $key; ?>">
                                            <span class="label-texto">
                                                <i class="bi bi-check-circle-fill check-icon"></i> <?php echo $label; ?>
                                            </span>
                                            <select class="form-select form-select-sm ms-2" name="<?php echo $key; ?>" style="width: 90px;" onchange="actualizarEstiloBox('<?php echo $key; ?>')">
                                                <option value="no" selected>No</option>
                                                <option value="si">Sí</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 6. Antecedentes Médicos -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3 seccion-titulo">
                                <h6 class="fw-bold text-primary mb-0"><i class="bi bi-heart-pulse me-2"></i>6. Antecedentes Médicos</h6>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary fw-semibold" onclick="toggleSeccion('sec-medicos', 'si')">Todo</button>
                                    <button type="button" class="btn btn-outline-secondary fw-semibold" onclick="toggleSeccion('sec-medicos', 'no')">Ninguno</button>
                                </div>
                            </div>
                            <div class="row g-3" id="sec-medicos">
                                <?php 
                                $campos_medicos = [
                                    'col_hipertenso' => 'Hipertenso', 'col_cardiologicos' => 'Cardiológicos', 'col_renales' => 'Renales',
                                    'col_testiculares' => 'Testiculares', 'col_pelvicos' => 'Pélvicos', 'col_infeccion_respiratoria' => 'Infección Respiratoria',
                                    'col_sinusitis' => 'Sinusitis', 'col_bronquitis' => 'Bronquitis', 'col_neumonia' => 'Neumonía',
                                    'col_asma' => 'Asma', 'col_tratamiento_asignado' => 'Tratamiento Asignado'
                                ];
                                foreach ($campos_medicos as $key => $label): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="opcion-box" id="box-<?php echo $key; ?>">
                                            <span class="label-texto">
                                                <i class="bi bi-check-circle-fill check-icon"></i> <?php echo $label; ?>
                                            </span>
                                            <select class="form-select form-select-sm ms-2" name="<?php echo $key; ?>" style="width: 90px;" onchange="actualizarEstiloBox('<?php echo $key; ?>')">
                                                <option value="no" selected>No</option>
                                                <option value="si">Sí</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top" style="border-color: var(--card-border) !important;">
                            <a href="dashboard.php" class="btn btn-outline-secondary px-4 py-2.5 fw-bold rounded-pill">Cancelar</a>
                            <button type="submit" class="btn btn-success px-5 py-2.5 fw-bold shadow-lg rounded-pill" style="background: linear-gradient(135deg, #16a34a, #15803d); border:none;">
                                <i class="bi bi-file-earmark-excel-fill me-2"></i> Generar Excel Personalizado
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        let seleccionOrden = [];

        function actualizarEstiloBox(key) {
            const select = document.querySelector(`select[name="${key}"]`);
            const box = document.getElementById(`box-${key}`);
            const nombre = select.name;
            const index = seleccionOrden.indexOf(nombre);
            
            if (select.value === 'si') {
                box.classList.add('active-option');
                if (index === -1) {
                    seleccionOrden.push(nombre);
                }
            } else {
                box.classList.remove('active-option');
                if (index !== -1) {
                    seleccionOrden.splice(index, 1);
                }
            }
            document.getElementById('orden_columnas').value = seleccionOrden.join(',');
        }

        function toggleSeccion(containerId, valor) {
            const contenedor = document.getElementById(containerId);
            if (contenedor) {
                const selects = contenedor.querySelectorAll('select');
                selects.forEach(select => {
                    select.value = valor;
                    actualizarEstiloBox(select.name);
                });
            }
        }
    </script>
    <!-- SCRIPT LOCAL (Funciona 100% sin internet) -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <?php include 'inactivity_check.php'; ?>
</body>
</html>