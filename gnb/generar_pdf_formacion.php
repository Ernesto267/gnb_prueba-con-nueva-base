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

$stmt = $pdo->prepare("SELECT * FROM personal WHERE id = :id");
$stmt->execute(['id' => $id]);
$persona = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$persona) {
    header("Location: consulta_personal.php");
    exit();
}

// Obtener la formación desde la tabla normalizada personal_cursos
$stmt_c = $pdo->prepare("SELECT * FROM personal_cursos WHERE personal_id = :id");
$stmt_c->execute(['id' => $id]);
$cursos_lista = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

$titulos = array_filter($cursos_lista, fn($c) => strcasecmp($c['tipo'] ?? '', 'Titulo') === 0);
$cursos = array_filter($cursos_lista, fn($c) => strcasecmp($c['tipo'] ?? '', 'Curso') === 0);
$talleres = array_filter($cursos_lista, fn($c) => strcasecmp($c['tipo'] ?? '', 'Taller') === 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>REPORTE DE FORMACIÓN Y CURSOS - GNB</title>
    <style>
        @page {
            size: letter;
            margin: 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }
        .pagina {
            width: 100%;
            box-sizing: border-box;
        }
        .tabla-cabecera {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .tabla-cabecera td {
            border: none;
            vertical-align: middle;
        }
        .logo-izq {
            width: 55px;
            height: 55px;
            object-fit: contain;
        }
        .logo-der {
            width: 110px;
            height: 110px;
            object-fit: contain;
            text-align: right;
        }
        .membrete {
            text-align: center;
            font-weight: bold;
            font-size: 8.5pt;
            line-height: 1.25;
        }
        .titulo-doc {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            text-decoration: underline;
            margin: 15px 0;
            text-transform: uppercase;
        }
        .info-personal {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 15px;
        }
        .info-personal td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 9pt;
        }
        .etiqueta {
            font-size: 7pt;
            font-weight: bold;
            display: block;
            text-transform: uppercase;
            color: #444;
        }
        .valor {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            display: block;
            margin-top: 2px;
        }
        .seccion-titulo {
            font-weight: bold;
            font-size: 10pt;
            margin-top: 15px;
            margin-bottom: 5px;
            text-transform: uppercase;
            background-color: #0d9488;
            color: #fff;
            padding: 4px 8px;
        }
        .tabla-cursos {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 10px;
        }
        .tabla-cursos th, .tabla-cursos td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 8.5pt;
            text-align: left;
        }
        .tabla-cursos th {
            background-color: #f2f2f2;
            text-transform: uppercase;
            font-size: 8pt;
        }
        .btn-imprimir {
            position: fixed;
            top: 10px;
            right: 10px;
            background-color: #0d9488;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            z-index: 1000;
        }
        @media print {
            .btn-imprimir { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

    <button class="btn-imprimir" onclick="window.print()">🖨️ Imprimir Reporte</button>

    <div class="pagina">
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
            REPORTE GENERAL DE FORMACIÓN Y CAPACITACIÓN PROFESIONAL
        </div>

        <table class="info-personal">
            <tr>
                <td style="width: 50%;">
                    <span class="etiqueta">Apellidos y Nombres:</span>
                    <span class="valor"><?php echo htmlspecialchars(strtoupper(($persona['apellidos'] ?? '') . ' ' . ($persona['nombres'] ?? ''))); ?></span>
                </td>
                <td style="width: 50%;">
                    <span class="etiqueta">Cédula de Identidad:</span>
                    <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['cedula'] ?? '')); ?></span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="etiqueta">Grado / Jerarquía:</span>
                    <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['grado'] ?? '')); ?></span>
                </td>
                <td>
                    <span class="etiqueta">Especialidad:</span>
                    <span class="valor"><?php echo htmlspecialchars(strtoupper($persona['especialidad'] ?? '')); ?></span>
                </td>
            </tr>
        </table>

        <!-- TÍTULOS -->
        <div class="seccion-titulo">1. Títulos Académicos</div>
        <?php if (count($titulos) > 0): ?>
            <table class="tabla-cursos">
                <thead>
                    <tr>
                        <th>Nombre del Título</th>
                        <th>Institución</th>
                        <th style="width: 80px; text-align: center;">Año</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($titulos as $t): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(strtoupper($t['nombre'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars(strtoupper($t['institucion'] ?? '')); ?></td>
                        <td style="text-align: center;"><?php echo htmlspecialchars($t['ano'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="font-style: italic; color: #555; font-size: 8.5pt; margin-bottom: 10px;">No registra títulos académicos.</p>
        <?php endif; ?>

        <!-- CURSOS -->
        <div class="seccion-titulo">2. Cursos Militares / Profesionales</div>
        <?php if (count($cursos) > 0): ?>
            <table class="tabla-cursos">
                <thead>
                    <tr>
                        <th>Nombre del Curso</th>
                        <th>Institución</th>
                        <th style="width: 80px; text-align: center;">Año</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cursos as $c): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(strtoupper($c['nombre'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars(strtoupper($c['institucion'] ?? '')); ?></td>
                        <td style="text-align: center;"><?php echo htmlspecialchars($c['ano'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="font-style: italic; color: #555; font-size: 8.5pt; margin-bottom: 10px;">No registra cursos militares o profesionales.</p>
        <?php endif; ?>

        <!-- TALLERES -->
        <div class="seccion-titulo">3. Talleres y Jornadas</div>
        <?php if (count($talleres) > 0): ?>
            <table class="tabla-cursos">
                <thead>
                    <tr>
                        <th>Nombre del Taller / Jornada</th>
                        <th>Institución</th>
                        <th style="width: 80px; text-align: center;">Año</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($talleres as $tal): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(strtoupper($tal['nombre'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars(strtoupper($tal['institucion'] ?? '')); ?></td>
                        <td style="text-align: center;"><?php echo htmlspecialchars($tal['ano'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="font-style: italic; color: #555; font-size: 8.5pt; margin-bottom: 10px;">No registra talleres o jornadas.</p>
        <?php endif; ?>

    </div>

</body>
</html>