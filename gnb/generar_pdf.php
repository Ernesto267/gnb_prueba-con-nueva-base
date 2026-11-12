<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: consulta_personal.php");
    exit();
}

// Obtener datos principales del personal
$stmt = $pdo->prepare("SELECT * FROM personal WHERE id = :id");
$stmt->execute(['id' => $id]);
$persona = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$persona) {
    header("Location: consulta_personal.php");
    exit();
}

// Obtener fotos desde personal_fotos
$stmt_f = $pdo->prepare("SELECT * FROM personal_fotos WHERE personal_id = :id");
$stmt_f->execute(['id' => $id]);
$fotos_db = $stmt_f->fetch(PDO::FETCH_ASSOC) ?: [];

// Combinar rutas de fotos en el arreglo $fotos_persona
$fotos_persona = [
    'foto_gala' => $fotos_db['foto_gala'] ?? '',
    'foto_patriota_tiuna' => $fotos_db['foto_patriota_tiuna'] ?? '',
    'foto_interior_cuartel' => $fotos_db['foto_interior_cuartel'] ?? ''
];

// Obtener datos de familiares desde personal_familiares
$stmt_fam = $pdo->prepare("SELECT * FROM personal_familiares WHERE personal_id = :id");
$stmt_fam->execute(['id' => $id]);
$fam_db = $stmt_fam->fetch(PDO::FETCH_ASSOC) ?: [];

// Fusionar datos de familiares al arreglo $persona para mantener compatibilidad con la vista
$persona = array_merge($persona, $fam_db);
// Inyectar campos específicos de fotos individuales si existen en tablas normalizadas
if (!empty($fotos_db['foto_cedula'])) $persona['foto_cedula'] = $fotos_db['foto_cedula'];
if (!empty($fotos_db['foto_carnet_frente'])) $persona['foto_carnet_frente'] = $fotos_db['foto_carnet_frente'];
if (!empty($fotos_db['foto_carnet_reverso'])) $persona['foto_carnet_reverso'] = $fotos_db['foto_carnet_reverso'];

// Obtener hijos desde personal_hijos
$stmt_h = $pdo->prepare("SELECT * FROM personal_hijos WHERE personal_id = :id");
$stmt_h->execute(['id' => $id]);
$hijos = $stmt_h->fetchAll(PDO::FETCH_ASSOC);

if (empty($persona['tratamiento_asignado'])) {
    $persona['tratamiento_asignado'] = 'NINGUNO';
}

// Función auxiliar para colocar X
function marcarOpcion($valor, $esperado) {
    return (strcasecmp(trim($valor ?? ''), $esperado) === 0) ? 'X' : '';
}

// Formatear Tiempo de Servicio (Años y Meses en la misma casilla)
$anos = (int)($persona['anos_servicio'] ?? 0);
$meses = (int)($persona['meses_servicio'] ?? 0);
$tiempo_servicio_texto = '';

if ($anos > 0 && $meses > 0) {
    $tiempo_servicio_texto = $anos . ' AÑOS Y ' . $meses . ' MESES';
} elseif ($anos > 0) {
    $tiempo_servicio_texto = $anos . ' AÑOS';
} elseif ($meses > 0) {
    $tiempo_servicio_texto = $meses . ' MESES';
} else {
    $tiempo_servicio_texto = '0 AÑOS';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>HOJA DE AUDITORIA Y EXPEDIENTE COMPLETO - GNB</title>
    <style>
        @page {
            size: letter;
            margin: 8mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: #000;
            background: #e2e8f0;
            margin: 0;
            padding: 20px 0;
            line-height: 1.15;
        }

        /* SIMULACIÓN DE HOJA EN LA VISTA WEB */
        .vista-previa-web {
            width: 215.9mm;
            min-height: 279.4mm;
            background: #fff;
            margin: 0 auto 20px auto;
            padding: 10mm;
            box-sizing: border-box;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }

        .pagina {
            width: 100%;
            min-height: 96vh;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        /* Página específica para Anexos sin separación forzada vertical extrema */
        .pagina-anexos {
            width: 100%;
            min-height: 96vh;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            gap: 15px;
        }
        
        .tabla-cabecera {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .tabla-cabecera td {
            border: none;
            vertical-align: middle;
        }
        .logo-izq {
            width: 55px;
            height: 55px;
            object-fit: contain;
            text-align: left;
        }
        .logo-der {
            width: 130px;
            height: 130px;
            object-fit: contain;
            text-align: right;
        }
        .membrete {
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
            line-height: 1.2;
        }

        .titulo-doc {
            text-align: center;
            font-weight: bold;
            font-size: 9.5pt;
            text-decoration: underline;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .carnet-header {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 2px;
        }
        .carnet-num {
            text-decoration: underline;
        }
        
        .tabla-datos {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            table-layout: fixed;
        }
        .tabla-datos td {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: top;
            word-wrap: break-word;
            word-break: break-all;
            overflow-wrap: break-word;
        }
        .etiqueta {
            font-size: 6.5pt;
            font-weight: bold;
            display: block;
            text-transform: uppercase;
        }
        .valor {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            display: block;
            margin-top: 1px;
        }

        .seccion-exposicion {
            margin-top: 6px;
        }
        .titulo-exposicion {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 2px;
        }
        
        /* CONTENEDOR DINÁMICO CON LÍNEAS AUTOMÁTICAS (EVITA SOLAPAMIENTOS) */
        .contenedor-exposicion-renglones {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 22px; 
            background-image: repeating-linear-gradient(
                transparent,
                transparent 21px,
                #000 21px,
                #000 22px
            );
            padding-bottom: 2px;
            word-wrap: break-word;
            word-break: break-all;
        }

        .bloque-firmas {
            margin-top: 8px;
            margin-bottom: 4px;
            width: 100%;
        }
        .esquema-huellas {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 65%;
            margin: 0 auto 5px auto;
            position: relative;
        }
        .caja-huella {
            width: 45px;
            height: 55px;
            border: 1.5px solid #000;
            background: #fff;
            z-index: 2;
        }
        .linea-conexion {
            position: absolute;
            top: 50%;
            left: 45px;
            right: 45px;
            height: 1.5px;
            background-color: #000;
            z-index: 1;
        }
        .linea-firma {
            width: 45%;
            border-bottom: 1px solid #000;
            margin: 0 auto 3px auto;
        }
        .caja-firma-texto {
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
            text-transform: uppercase;
        }

        .grid-fotos {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .celda-foto {
            text-align: center;
            vertical-align: middle;
            padding: 4px;
        }
        .imagen-oficial {
            width: 140px;
            height: 175px;
            object-fit: cover;
        }
        .imagen-carnet {
            width: 260px;
            height: 155px;
            object-fit: contain;
        }
        .imagen-cedula {
            width: 340px;
            height: 200px;
            object-fit: contain;
        }

        .seccion-titulo {
            font-weight: bold;
            font-size: 9pt;
            margin-top: 6px;
            margin-bottom: 4px;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
        }
        .tabla-familiar {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 6px;
        }
        .tabla-familiar th, .tabla-familiar td {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
            word-wrap: break-word;
            word-break: break-all;
        }
        .etiqueta-pequenha {
            font-size: 6.5pt;
            font-weight: bold;
            display: block;
            text-transform: uppercase;
        }
        .valor-campo {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
        }
        .tabla-antecedentes {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 6px;
        }
        .tabla-antecedentes th {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-weight: bold;
            font-size: 8.5pt;
            background: #f0f0f0;
        }
        .tabla-antecedentes td {
            border: 1px solid #000;
            padding: 2px 6px;
            font-weight: bold;
            font-size: 8pt;
            text-transform: uppercase;
        }
        .tabla-antecedentes td.campo-nombre {
            width: 76%;
        }
        .tabla-antecedentes td.opcion {
            width: 12%;
            text-align: center;
        }
        .linea-x {
            display: inline-block;
            width: 100%;
            text-align: center;
            font-weight: bold;
        }
        .tratamiento-contenedor {
            margin-top: 4px;
            font-weight: bold;
            font-size: 8pt;
        }
        .linea-tratamiento {
            border: 1px solid #000;
            min-height: 22px;
            padding: 4px;
            text-transform: uppercase;
            font-size: 8pt;
            background: #fff;
            word-wrap: break-word;
            word-break: break-all;
        }
        .footer-confidencial {
            margin-top: 4px;
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
        }

        .btn-imprimir {
            position: fixed;
            top: 15px;
            right: 25px;
            background-color: #0d6efd;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 50rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4);
            z-index: 1000;
            transition: all 0.2s ease;
        }
        .btn-imprimir:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .btn-imprimir { 
                display: none !important; 
            }
            .vista-previa-web {
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                page-break-after: always;
            }
        }
    </style>
</head>
<body>

    <button class="btn-imprimir" onclick="window.print()"><i class="bi bi-printer-fill"></i> 🖨️ Imprimir / Guardar Documento</button>

    <!-- ================= HOJA 1: DATOS Y EXPOSICIÓN ================= -->
    <div class="vista-previa-web">
        <div class="pagina">
            <div>
                <table class="tabla-cabecera">
                    <tr>
                        <td style="width: 20%; text-align: left;">
                            <img src="imagen/logo_izq.png" class="logo-izq" alt="Logo Izquierdo" onerror="this.style.display='none'">
                        </td>
                        <td style="width: 60%;">
                            <div class="membrete">
                                REPÚBLICA BOLIVARIANA DE VENEZUELA<br>
                                MINISTERIO DEL PODER POPULAR PARA LA DEFENSA<br>
                                FUERZA ARMADA NACIONAL BOLIVARIANA<br>
                                GUARDIA NACIONAL BOLIVARIANA<br>
                                DIRECCIÓN DE PERSONAL
                            </div>
                        </td>
                        <td style="width: 20%; text-align: right;">
                            <img src="imagen/logo_der.jpg" class="logo-der" alt="Logo Derecho" onerror="this.style.display='none'">
                        </td>
                    </tr>
                </table>

                <div class="titulo-doc">
                    HOJA DE AUDITORIA Y DATOS PERSONALES PARA EL PERSONAL DE TROPA PROFESIONAL.
                </div>

                <div class="carnet-header">
                    CARNET N°: <span class="carnet-num"><?php echo htmlspecialchars(strtoupper($persona['numero_carnet'] ?? '')); ?></span>
                </div>

                <table class="tabla-datos">
                    <tr>
                        <td style="width: 20%;">
                            <span class="etiqueta">GRADO/JQUIA:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['grado'] ?? '')); ?></span>
                        </td>
                        <td style="width: 45%;" colspan="2">
                            <span class="etiqueta">APELLIDOS:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['apellidos'] ?? '')); ?></span>
                        </td>
                        <td style="width: 35%;" colspan="2">
                            <span class="etiqueta">NOMBRES:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['nombres'] ?? '')); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="etiqueta">C.I.:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['cedula'] ?? '')); ?></span>
                        </td>
                        <td style="width: 10%;">
                            <span class="etiqueta">SEXO:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['sexo'] ?? '')); ?></span>
                        </td>
                        <td style="width: 15%;">
                            <span class="etiqueta">GRUPO SANGUÍNEO:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['grupo_sang'] ?? '')); ?></span>
                        </td>
                        <td>
                            <span class="etiqueta">ESTADO CIVIL:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['estado_civil'] ?? '')); ?></span>
                        </td>
                        <td style="width: 10%;">
                            <span class="etiqueta">EDAD:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['edad'] ?? '')); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span class="etiqueta">LUGAR DE NACIMIENTO:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['lugar_nacimiento'] ?? '')); ?></span>
                        </td>
                        <td colspan="3">
                            <span class="etiqueta">FECHA DE NACIMIENTO:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['fecha_nacimiento'] ?? '')); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span class="etiqueta">ESPECIALIDAD:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['especialidad'] ?? '')); ?></span>
                        </td>
                        <td colspan="2">
                            <span class="etiqueta">PROMOCIÓN/Nº DE PROMOCIÓN:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['promocion'] ?? '')); ?></span>
                        </td>
                        <td>
                            <span class="etiqueta">AÑO GRADUACIÓN:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['ano_graduacion'] ?? '')); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <span class="etiqueta">ESCUELA DE GRADUACIÓN:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['escuela_graduacion'] ?? '')); ?></span>
                        </td>
                        <td colspan="2">
                            <span class="etiqueta">CATEGORÍA:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['categoria'] ?? '')); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span class="etiqueta">FECHA DE INGRESO:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['fecha_ingreso'] ?? '')); ?></span>
                        </td>
                        <td colspan="3">
                            <span class="etiqueta">TIEMPO DE SERVICIO:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($tiempo_servicio_texto)); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="etiqueta">TALLA ZAPATO:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['talla_zapato'] ?? 'N/A')); ?></span>
                        </td>
                        <td>
                            <span class="etiqueta">TALLA CAMISA:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['talla_camisa'] ?? 'N/A')); ?></span>
                        </td>
                        <td>
                            <span class="etiqueta">TALLA PANTALÓN:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['talla_pantalon'] ?? 'N/A')); ?></span>
                        </td>
                        <td>
                            <span class="etiqueta">TALLA GORRA:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['talla_gorra'] ?? 'N/A')); ?></span>
                        </td>
                        <td>
                            <span class="etiqueta">TALLA PATRIOTA:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['talla_patriota'] ?? 'N/A')); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span class="etiqueta">TELÉFONO CELULAR:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['telefono_celular'] ?? '')); ?></span>
                        </td>
                        <td colspan="2">
                            <span class="etiqueta">TELÉFONO HABITACIÓN:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['telefono_habitacion'] ?? '')); ?></span>
                        </td>
                        <td>
                            <span class="etiqueta">TELÉFONO EMERGENCIA:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['contacto_emergencia_telefono'] ?? '')); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <span class="etiqueta">CORREO ELECTRÓNICO:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['correo'] ?? '')); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <span class="etiqueta">DIRECCIÓN DE HABITACIÓN:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['direccion'] ?? '')); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <span class="etiqueta">DIRECCIÓN DE EMERGENCIA:</span>
                            <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['direccion_emergencia'] ?? '')); ?></span>
                        </td>
                    </tr>
                </table>

                <div class="seccion-exposicion">
                    <div class="titulo-exposicion">EXPOSICIÓN:</div>
                    <div class="contenedor-exposicion-renglones">
                        <?php echo nl2br(htmlspecialchars(strtoupper($persona['antecedentes_observaciones'] ?? ''))); ?>
                    </div>
                </div>
            </div>

            <div class="bloque-firmas">
                <div class="esquema-huellas">
                    <div class="caja-huella"></div>
                    <div class="linea-conexion"></div>
                    <div class="caja-huella"></div>
                </div>
                <div class="linea-firma"></div>
                <div class="caja-firma-texto">
                    FIRMA / HUELLAS
                </div>
            </div>
        </div>
    </div>


    <!-- ================= HOJA 2: REGISTRO FOTOGRÁFICO ================= -->
    <div class="vista-previa-web">
        <div class="pagina-anexos">
            <div class="titulo-doc" style="margin-bottom: 5px;">
                REGISTRO FOTOGRÁFICO Y DOCUMENTAL (ANEXOS)
            </div>

            <table class="grid-fotos">
                <tr>
                    <td class="celda-foto" style="width: 33.33%;">
                        <?php if (!empty($fotos_persona['foto_gala']) && file_exists($fotos_persona['foto_gala'])): ?>
                            <img src="<?php echo $fotos_persona['foto_gala']; ?>" class="imagen-oficial" alt="Foto Gala">
                        <?php else: ?>
                            <div style="color: #777;">[ SIN FOTO GALA ]</div>
                        <?php endif; ?>
                    </td>
                    <td class="celda-foto" style="width: 33.33%;">
                        <?php if (!empty($fotos_persona['foto_patriota_tiuna']) && file_exists($fotos_persona['foto_patriota_tiuna'])): ?>
                            <img src="<?php echo $fotos_persona['foto_patriota_tiuna']; ?>" class="imagen-oficial" alt="Foto Patriota Tiuna">
                        <?php else: ?>
                            <div style="color: #777;">[ SIN FOTO PATRIOTA TIUNA ]</div>
                        <?php endif; ?>
                    </td>
                    <td class="celda-foto" style="width: 33.33%;">
                        <?php if (!empty($fotos_persona['foto_interior_cuartel']) && file_exists($fotos_persona['foto_interior_cuartel'])): ?>
                            <img src="<?php echo $fotos_persona['foto_interior_cuartel']; ?>" class="imagen-oficial" alt="Foto Interior Cuartel">
                        <?php else: ?>
                            <div style="color: #777;">[ SIN FOTO CUARTEL ]</div>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="celda-foto" style="text-align: center; vertical-align: middle; padding-top: 8px;">
                        <table style="margin: 0 auto; border-collapse: collapse;">
                            <tr>
                                <td style="text-align: center; padding: 0 15px; vertical-align: middle;">
                                    <?php if (!empty($persona['foto_carnet_frente']) && file_exists($persona['foto_carnet_frente'])): ?>
                                        <img src="<?php echo $persona['foto_carnet_frente']; ?>" class="imagen-carnet" alt="Carnet Frente">
                                    <?php else: ?>
                                        <div style="color: #777;">[ SIN CARNET FRENTE ]</div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; padding: 0 15px; vertical-align: middle;">
                                    <?php if (!empty($persona['foto_carnet_reverso']) && file_exists($persona['foto_carnet_reverso'])): ?>
                                        <img src="<?php echo $persona['foto_carnet_reverso']; ?>" class="imagen-carnet" alt="Carnet Reverso">
                                    <?php else: ?>
                                        <div style="color: #777;">[ SIN CARNET REVERSO ]</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="celda-foto" colspan="3" style="width: 100%; text-align: center; vertical-align: middle; padding-top: 8px;">
                        <?php if (!empty($persona['foto_cedula']) && file_exists($persona['foto_cedula'])): ?>
                            <img src="<?php echo $persona['foto_cedula']; ?>" class="imagen-cedula" alt="Cédula">
                        <?php else: ?>
                            <div style="color: #777;">[ SIN CÉDULA REGISTRADA ]</div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>


    <!-- ================= HOJA 3: DATOS FAMILIARES Y MÉDICOS ================= -->
    <div class="vista-previa-web">
        <div class="pagina">
            <div>
                <table class="tabla-cabecera">
                    <tr>
                        <td style="width: 20%; text-align: left;">
                            <img src="imagen/logo_izq.png" class="logo-izq" alt="Logo Izquierdo" onerror="this.style.display='none'">
                        </td>
                        <td style="width: 60%;">
                            <div class="membrete">
                                REPÚBLICA BOLIVARIANA DE VENEZUELA<br>
                                MINISTERIO DEL PODER POPULAR PARA LA DEFENSA<br>
                                FUERZA ARMADA NACIONAL BOLIVARIANA<br>
                                GUARDIA NACIONAL BOLIVARIANA<br>
                                DIRECCIÓN DE PERSONAL
                            </div>
                        </td>
                        <td style="width: 20%; text-align: right;">
                            <img src="imagen/logo_der.jpg" class="logo-der" alt="Logo Derecho" onerror="this.style.display='none'">
                        </td>
                    </tr>
                </table>

                <div class="titulo-doc">
                    EXPEDIENTE DE DATOS FAMILIARES Y MÉDICOS
                </div>

                <div class="seccion-titulo">2. DATOS FAMILIARES</div>

                <table class="tabla-familiar">
                    <tr>
                        <td colspan="2" style="width: 50%;">
                            <span class="etiqueta-pequenha">NOMBRE Y APELLIDOS DE LA ESPOSA (O):</span>
                            <span class="valor-campo">
                                <?php 
                                if (!empty($persona['nombre_conyuge'])) {
                                    echo htmlspecialchars(strtoupper($persona['nombre_conyuge']));
                                } else {
                                    echo '<span style="color: #666; font-weight: normal;">NO ESTÁ CASADO(A)</span>';
                                }
                                ?>
                            </span>
                        </td>
                        <td style="width: 25%;">
                            <span class="etiqueta-pequenha">Cédula:</span>
                            <span class="valor-campo"><?php echo htmlspecialchars(strtoupper($persona['cedula_conyuge'] ?? 'N/A')); ?></span>
                        </td>
                        <td style="width: 25%;">
                            <span class="etiqueta-pequenha">Teléfono Celular:</span>
                            <span class="valor-campo"><?php echo htmlspecialchars(strtoupper($persona['telefono_conyuge'] ?? 'N/A')); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 15%;">
                            <span class="etiqueta-pequenha">Nº DE HIJOS:</span>
                            <span class="valor-campo" style="text-align: center; display: block;"><?php echo htmlspecialchars(count($hijos)); ?></span>
                        </td>
                        <td colspan="3">
                            <span class="etiqueta-pequenha">DIRECCIÓN DOMICILIARIA DE LA ESPOSA E HIJOS:</span>
                            <span class="valor-campo"><?php echo htmlspecialchars(strtoupper(!empty($persona['nombre_conyuge']) ? $persona['direccion'] : 'N/A')); ?></span>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2" style="font-weight: bold; text-align: center; background: #f4f4f4; font-size: 7.5pt;">NOMBRE HIJOS</td>
                        <td style="font-weight: bold; text-align: center; background: #f4f4f4; font-size: 7.5pt;">FECHA DE NACIMIENTO</td>
                        <td style="font-weight: bold; text-align: center; background: #f4f4f4; font-size: 7.5pt;">EDAD</td>
                    </tr>
                    <?php if (count($hijos) > 0): ?>
                        <?php foreach ($hijos as $h): ?>
                        <tr>
                            <td colspan="2" class="valor-campo"><?php echo htmlspecialchars(strtoupper(($h['nombres'] ?? '') . ' ' . ($h['apellidos'] ?? ''))); ?></td>
                            <td class="valor-campo" style="text-align: center;"><?php echo htmlspecialchars(strtoupper($h['fecha_nac'] ?? '')); ?></td>
                            <td class="valor-campo" style="text-align: center;"><?php echo htmlspecialchars(strtoupper($h['edad'] ?? '')); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="valor-campo" style="color: #666; text-align: center;">SIN REGISTRO DE HIJOS</td>
                            <td style="text-align: center;">-</td>
                            <td style="text-align: center;">-</td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td colspan="3">
                            <span class="etiqueta-pequenha">NOMBRES Y APELLIDOS DEL PADRE:</span>
                            <span class="valor-campo"><?php echo htmlspecialchars(strtoupper($persona['nombre_padre'] ?? '')); ?></span>
                        </td>
                        <td>
                            <span class="etiqueta-pequenha">C.I.:</span>
                            <span class="valor-campo"><?php echo htmlspecialchars(strtoupper($persona['cedula_padre'] ?? '')); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            VIVE: SI [ <span style="font-weight:bold;"><?php echo marcarOpcion($persona['padre_vive'] ?? '', 'Sí'); ?></span> ] &nbsp;&nbsp; NO [ <span style="font-weight:bold;"><?php echo marcarOpcion($persona['padre_vive'] ?? '', 'No'); ?></span> ] 
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; TELÉFONOS: <span class="valor-campo"><?php echo htmlspecialchars(strtoupper($persona['telefono_padre'] ?? '')); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <span class="etiqueta-pequenha">DIRECCIÓN DOMICILIARIA:</span>
                            <span class="valor-campo"><?php echo htmlspecialchars(strtoupper($persona['direccion_padre'] ?? '')); ?></span>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="3">
                            <span class="etiqueta-pequenha">NOMBRES Y APELLIDOS DE LA MADRE:</span>
                            <span class="valor-campo"><?php echo htmlspecialchars(strtoupper($persona['nombre_madre'] ?? '')); ?></span>
                        </td>
                        <td>
                            <span class="etiqueta-pequenha">C.I.:</span>
                            <span class="valor-campo"><?php echo htmlspecialchars(strtoupper($persona['cedula_madre'] ?? '')); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            VIVE: SI [ <span style="font-weight:bold;"><?php echo marcarOpcion($persona['madre_vive'] ?? '', 'Sí'); ?></span> ] &nbsp;&nbsp; NO [ <span style="font-weight:bold;"><?php echo marcarOpcion($persona['madre_vive'] ?? '', 'No'); ?></span> ] 
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; TELÉFONOS: <span class="valor-campo"><?php echo htmlspecialchars(strtoupper($persona['telefono_madre'] ?? '')); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <span class="etiqueta-pequenha">DIRECCIÓN DOMICILIARIA:</span>
                            <span class="valor-campo"><?php echo htmlspecialchars(strtoupper($persona['direccion_madre'] ?? '')); ?></span>
                        </td>
                    </tr>
                </table>

                <div class="seccion-titulo">3. ANTECEDENTES MÉDICOS</div>

                <table class="tabla-antecedentes">
                    <thead>
                        <tr>
                            <th>PATOLOGÍA / AFECCIÓN</th>
                            <th>SI</th>
                            <th>NO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $patologias = [
                            'hipertenso' => 'HIPERTENSO / TENSIÓN ALTA',
                            'cardiologicos' => 'ANTECEDENTES CARDIOLÓGICOS',
                            'renales' => 'AFECCIONES RENALES',
                            'testiculares' => 'AFECCIONES TESTICULARES',
                            'pelvicos' => 'AFECCIONES PÉLVICAS',
                            'infeccion_respiratoria' => 'INFECCIÓN RESPIRATORIA',
                            'sinusitis' => 'SINUSITIS CRÓNICA',
                            'bronquitis' => 'BRONQUITIS',
                            'neumonia' => 'NEUMONÍA',
                            'asma' => 'ASMA BRONQUIAL'
                        ];
                        foreach ($patologias as $key => $label):
                        ?>
                        <tr>
                            <td class="campo-nombre"><?php echo $label; ?></td>
                            <td class="opcion"><span class="linea-x"><?php echo marcarOpcion($persona[$key] ?? '', 'Sí'); ?></span></td>
                            <td class="opcion"><span class="linea-x"><?php echo marcarOpcion($persona[$key] ?? '', 'No'); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="tratamiento-contenedor">
                    <div style="font-size: 7.5pt; margin-bottom: 2px;">TRATAMIENTO MÉDICO ASIGNADO:</div>
                    <div class="linea-tratamiento"><?php echo htmlspecialchars(strtoupper($persona['tratamiento_asignado'])); ?></div>
                </div>
            </div>

            <div class="footer-confidencial">
                CONFIDENCIAL
            </div>
        </div>
    </div>

</body>
</html>