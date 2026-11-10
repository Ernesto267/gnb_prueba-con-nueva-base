<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    exit("Acceso no autorizado");
}

$preset = $_GET['preset'] ?? '';

// Función auxiliar para generar la cabecera con namespaces de Excel y cuadrícula visible
function generarCabeceraExcel($filename) {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename={$filename}_" . date('Y-m-d') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<!--[if gte mso 9]>';
    echo '<xml>';
    echo '<x:ExcelWorkbook>';
    echo '<x:ExcelWorksheets>';
    echo '<x:ExcelWorksheet>';
    echo '<x:Name>Reporte ZODI 11</x:Name>';
    echo '<x:WorksheetOptions>';
    echo '<x:DisplayGridlines/>';
    echo '</x:WorksheetOptions>';
    echo '</x:ExcelWorksheet>';
    echo '</x:ExcelWorksheets>';
    echo '</x:ExcelWorkbook>';
    echo '</xml>';
    echo '<![endif]-->';
    echo '</head>';
    echo '<body>';
}

// Estilos compartidos para celdas de fotos e imágenes centradas
$estilos_comunes_excel = "
    .titulo-inst { font-family: Arial, sans-serif; font-size: 10pt; font-weight: bold; text-align: center; background-color: #ffffff; }
    .titulo-sub { font-family: Arial, sans-serif; font-size: 11pt; font-weight: bold; text-align: center; background-color: #ffffff; padding: 10px 0; }
    .tabla-header { background-color: #008b8b; color: #ffffff; font-family: Arial, sans-serif; font-size: 8pt; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000000; padding: 6px; }
    .celda-dato { font-family: Arial, sans-serif; font-size: 8pt; text-align: center; vertical-align: middle; border: 1px solid #000000; padding: 4px; }
    .celda-texto { font-family: Arial, sans-serif; font-size: 8pt; text-align: center; vertical-align: middle; border: 1px solid #000000; padding: 4px; }
    .fila-fila { height: 76px !important; }
    .celda-foto { font-family: Arial, sans-serif; font-size: 8pt; text-align: center !important; vertical-align: middle !important; border: 1px solid #000000; padding: 0px !important; height: 76px !important; }
    .celda-largo { font-family: Arial, sans-serif; font-size: 8pt; text-align: left; vertical-align: middle; border: 1px solid #000000; padding: 4px; word-wrap: break-word; white-space: normal; height: auto !important; }
    .img-foto { width: 48px !important; height: 72px !important; max-width: 48px !important; max-height: 72px !important; object-fit: cover; border-radius: 2px; display: block; margin: 0 auto; mso-data-placement: same-cell; }
";

// ==========================================
// CASO 1A: PRESET DE DESCARGA DIRECTA: RELACIÓN HIJOS
// ==========================================
if ($preset === 'relacion_hijos') {
    $stmt = $pdo->query("SELECT * FROM personal ORDER BY apellidos ASC");
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    generarCabeceraExcel("RELACION_HIJOS_LC11");

    echo "<style>{$estilos_comunes_excel}</style>";
    echo "<table border='1' cellspacing='0' cellpadding='4'>";

    $tot_col = 17;
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>REPÚBLICA BOLIVARIANA DE VENEZUELA</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>MINISTERIO DEL PODER POPULAR PARA LA DEFENSA</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>COMANDO ESTRATÉGICO OPERACIONAL DE LA FANB</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>REGIÓN ESTRATÉGICA DE DEFENSA INTEGRAL NRO. 1 OCCIDENTAL</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>ZONA OPERATIVA DE DEFENSA INTEGRAL NRO. 11 \"ZULIA\"</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>COMANDO</th></tr>";
    echo "<tr><td colspan='{$tot_col}'>&nbsp;</td></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-sub'>RELACION DE HIJOS DEL PERSONAL MILITAR DE LA ZODI NRO. 11 ZULIA</th></tr>";
    echo "<tr><td colspan='{$tot_col}'>&nbsp;</td></tr>";

    echo "<tr>";
    echo "<th rowspan='2' class='tabla-header'>NRO</th>";
    echo "<th rowspan='2' class='tabla-header'>UNIDAD/ BRIGADA</th>";
    echo "<th rowspan='2' class='tabla-header'>DESTACAMENTO/BATALLÓN</th>";
    echo "<th rowspan='2' class='tabla-header'>COMPONENTE</th>";
    echo "<th rowspan='2' class='tabla-header'>CATEGORIA</th>";
    echo "<th rowspan='2' class='tabla-header'>GRADO/JQUIA</th>";
    echo "<th rowspan='2' class='tabla-header'>PRIMER NOMBRE</th>";
    echo "<th rowspan='2' class='tabla-header'>SEGUNDO NOMBRE</th>";
    echo "<th rowspan='2' class='tabla-header'>PRIMER APELLIDO</th>";
    echo "<th rowspan='2' class='tabla-header'>SEGUNDO APELLIDO</th>";
    echo "<th rowspan='2' class='tabla-header'>TIENE HIJOS CON EDADES ENTRE LOS 0 MESES Y 12 AÑOS</th>";
    echo "<th colspan='6' class='tabla-header'>CANTIDAD</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<th colspan='2' class='tabla-header'>0 MESES A 3 AÑOS</th>";
    echo "<th colspan='2' class='tabla-header'>4 AÑOS A 7 AÑOS</th>";
    echo "<th colspan='2' class='tabla-header'>8 AÑOS A 12 AÑOS</th>";
    echo "</tr>";

    echo "<tr>";
    for ($i = 0; $i < 11; $i++) { echo "<th></th>"; }
    echo "<th class='tabla-header'>F</th><th class='tabla-header'>M</th>";
    echo "<th class='tabla-header'>F</th><th class='tabla-header'>M</th>";
    echo "<th class='tabla-header'>F</th><th class='tabla-header'>M</th>";
    echo "</tr>";

    $nro = 1;
    $stmt_hijos = $pdo->prepare("SELECT * FROM personal_hijos WHERE personal_id = ?");

    foreach ($registros as $row) {
        $stmt_hijos->execute([$row['id']]);
        $hijos = $stmt_hijos->fetchAll(PDO::FETCH_ASSOC);
        
        $tiene_hijos_rango = 'NO';
        $c_03_f = 0; $c_03_m = 0;
        $c_47_f = 0; $c_47_m = 0;
        $c_812_f = 0; $c_812_m = 0;

        $hoy = new DateTime();
        if (!empty($hijos)) {
            foreach ($hijos as $hijo) {
                if (!empty($hijo['fecha_nac'])) {
                    $fnac = new DateTime($hijo['fecha_nac']);
                    $diff = $hoy->diff($fnac);
                    $edad_anios = $diff->y;
                    $edad_meses_total = ($diff->y * 12) + $diff->m;

                    if ($edad_anios <= 12) {
                        $tiene_hijos_rango = 'SI';
                        $sexo_hijo = strtoupper($hijo['sexo'] ?? 'M');

                        if ($edad_meses_total >= 0 && $edad_anios <= 3) {
                            if ($sexo_hijo === 'F') { $c_03_f++; } else { $c_03_m++; }
                        } elseif ($edad_anios >= 4 && $edad_anios <= 7) {
                            if ($sexo_hijo === 'F') { $c_47_f++; } else { $c_47_m++; }
                        } elseif ($edad_anios >= 8 && $edad_anios <= 12) {
                            if ($sexo_hijo === 'F') { $c_812_f++; } else { $c_812_m++; }
                        }
                    }
                }
            }
        }

        $nombres_arr = explode(' ', trim($row['nombres'] ?? ''));
        $p_nombre = $nombres_arr[0] ?? '';
        $s_nombre = $nombres_arr[1] ?? '';

        $apellidos_arr = explode(' ', trim($row['apellidos'] ?? ''));
        $p_apellido = $apellidos_arr[0] ?? '';
        $s_apellido = $apellidos_arr[1] ?? '';

        echo "<tr>";
        echo "<td class='celda-dato'><strong>{$nro}</strong></td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($row['unidad_brigada'] ?? 'LABORATORIO CRIMINALISTICO NRO. 11')) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($row['destacamento_batallon'] ?? 'SLCCT')) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['componente'] ?? 'GNB')) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['categoria'] ?? '')) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['grado'] ?? '')) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($p_nombre)) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($s_nombre)) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($p_apellido)) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($s_apellido)) . "</td>";
        echo "<td class='celda-dato'>" . $tiene_hijos_rango . "</td>";
        
        echo "<td class='celda-dato'>" . ($c_03_f > 0 ? $c_03_f : '') . "</td>";
        echo "<td class='celda-dato'>" . ($c_03_m > 0 ? $c_03_m : '') . "</td>";
        echo "<td class='celda-dato'>" . ($c_47_f > 0 ? $c_47_f : '') . "</td>";
        echo "<td class='celda-dato'>" . ($c_47_m > 0 ? $c_47_m : '') . "</td>";
        echo "<td class='celda-dato'>" . ($c_812_f > 0 ? $c_812_f : '') . "</td>";
        echo "<td class='celda-dato'>" . ($c_812_m > 0 ? $c_812_m : '') . "</td>";
        echo "</tr>";
        $nro++;
    }

    echo "</table></body></html>";
    exit();
}

// ==========================================
// CASO 1B: PRESET DE DESCARGA DIRECTA: RELACIÓN UNIFORMES
// ==========================================
if ($preset === 'relacion_uniformes') {
    $stmt = $pdo->query("SELECT * FROM personal ORDER BY apellidos ASC");
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    generarCabeceraExcel("RELACION_UNIFORMES_LC11");

    echo "<style>{$estilos_comunes_excel}</style>";
    echo "<table border='1' cellspacing='0' cellpadding='4'>";

    $tot_col = 14;
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>REPÚBLICA BOLIVARIANA DE VENEZUELA</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>MINISTERIO DEL PODER POPULAR PARA LA DEFENSA</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>COMANDO ESTRATÉGICO OPERACIONAL DE LA FANB</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>REGIÓN ESTRATÉGICA DE DEFENSA INTEGRAL NRO. 1 OCCIDENTAL</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>ZONA OPERATIVA DE DEFENSA INTEGRAL NRO. 11 \"ZULIA\"</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>COMANDO</th></tr>";
    echo "<tr><td colspan='{$tot_col}'>&nbsp;</td></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-sub'>RELACION DEL PERSONAL MILITAR DE LA ZODI NRO. 11 ZULIA</th></tr>";
    echo "<tr><td colspan='{$tot_col}'>&nbsp;</td></tr>";

    echo "<tr>";
    echo "<th class='tabla-header'>NRO</th>";
    echo "<th class='tabla-header'>UNIDAD/ BRIGADA</th>";
    echo "<th class='tabla-header'>DESTACAMENTO/BATALLÓN</th>";
    echo "<th class='tabla-header'>COMPONENTE</th>";
    echo "<th class='tabla-header'>CATEGORIA</th>";
    echo "<th class='tabla-header'>GRADO/JQUIA</th>";
    echo "<th class='tabla-header'>PRIMER NOMBRE</th>";
    echo "<th class='tabla-header'>SEGUNDO NOMBRE</th>";
    echo "<th class='tabla-header'>PRIMER APELLIDO</th>";
    echo "<th class='tabla-header'>SEGUNDO APELLIDO</th>";
    echo "<th class='tabla-header'>TALLA UNIFORME</th>";
    echo "<th class='tabla-header'>TALLA CAMISA</th>";
    echo "<th class='tabla-header'>TALLA PANTALÓN</th>";
    echo "<th class='tabla-header'>CALZADO</th>";
    echo "</tr>";

    $nro = 1;
    foreach ($registros as $row) {
        $nombres_arr = explode(' ', trim($row['nombres'] ?? ''));
        $p_nombre = $nombres_arr[0] ?? '';
        $s_nombre = $nombres_arr[1] ?? '';

        $apellidos_arr = explode(' ', trim($row['apellidos'] ?? ''));
        $p_apellido = $apellidos_arr[0] ?? '';
        $s_apellido = $apellidos_arr[1] ?? '';

        echo "<tr>";
        echo "<td class='celda-dato'><strong>{$nro}</strong></td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($row['unidad_brigada'] ?? 'LABORATORIO CRIMINALISTICO NRO. 11')) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($row['destacamento_batallon'] ?? 'SLCCT')) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['componente'] ?? 'GNB')) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['categoria'] ?? '')) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['grado'] ?? '')) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($p_nombre)) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($s_nombre)) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($p_apellido)) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($s_apellido)) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['talla_patriota'] ?? '')) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['talla_camisa'] ?? '')) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['talla_pantalon'] ?? '')) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['talla_zapato'] ?? '')) . "</td>";
        echo "</tr>";
        $nro++;
    }

    echo "</table></body></html>";
    exit();
}

// ==========================================
// CASO 1C: PRESET DE DESCARGA DIRECTA: DATOS PERSONALES
// ==========================================
if ($preset === 'datos_personales') {
    $stmt = $pdo->query("SELECT * FROM personal ORDER BY apellidos ASC");
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    generarCabeceraExcel("DATOS_PERSONALES_LC11");

    echo "<style>{$estilos_comunes_excel}</style>";
    echo "<table border='1' cellspacing='0' cellpadding='4'>";

    $tot_col = 20;
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>REPÚBLICA BOLIVARIANA DE VENEZUELA</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>MINISTERIO DEL PODER POPULAR PARA LA DEFENSA</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>COMANDO ESTRATÉGICO OPERACIONAL DE LA FANB</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>REGIÓN ESTRATÉGICA DE DEFENSA INTEGRAL NRO. 1 OCCIDENTAL</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>ZONA OPERATIVA DE DEFENSA INTEGRAL NRO. 11 \"ZULIA\"</th></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-inst'>COMANDO</th></tr>";
    echo "<tr><td colspan='{$tot_col}'>&nbsp;</td></tr>";
    echo "<tr><th colspan='{$tot_col}' class='titulo-sub'>RELACION DEL PERSONAL MILITAR DE LA ZODI NRO. 11 ZULIA</th></tr>";
    echo "<tr><td colspan='{$tot_col}'>&nbsp;</td></tr>";

    echo "<tr>";
    echo "<th class='tabla-header'>NRO</th>";
    echo "<th class='tabla-header'>UNIDAD/ BRIGADA</th>";
    echo "<th class='tabla-header'>DESTACAMENTO/BATALLÓN</th>";
    echo "<th class='tabla-header'>COMPONENTE</th>";
    echo "<th class='tabla-header'>CATEGORIA</th>";
    echo "<th width='52' class='tabla-header'>FOTO<br style='mso-data-placement:same-cell;' />BICENTENARIO</th>";
    echo "<th width='52' class='tabla-header'>FOTO<br style='mso-data-placement:same-cell;' />TÁCTICO</th>";
    echo "<th class='tabla-header'>GRADO/JQUIA</th>";
    echo "<th class='tabla-header'>PRIMER NOMBRE</th>";
    echo "<th class='tabla-header'>SEGUNDO NOMBRE</th>";
    echo "<th class='tabla-header'>PRIMER APELLIDO</th>";
    echo "<th class='tabla-header'>SEGUNDO APELLIDO</th>";
    echo "<th class='tabla-header'>FECHA DE NACIMIENTO</th>";
    echo "<th class='tabla-header'>CÉDULA DE IDENTIDAD</th>";
    echo "<th class='tabla-header'>SEXO</th>";
    echo "<th class='tabla-header'>GRUPO SANGUÍNEO</th>";
    echo "<th class='tabla-header'>TELÉFONO CELULAR</th>";
    echo "<th class='tabla-header'>TELÉFONO DE EMERGENCIA</th>";
    echo "<th class='tabla-header'>DIRECCIÓN DOMICILIARIA</th>";
    echo "<th class='tabla-header'>CORREO ELECTRONICO</th>";
    echo "</tr>";

    $nro = 1;
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $base_url = $protocolo . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';

    $stmt_f = $pdo->prepare("SELECT * FROM personal_fotos WHERE personal_id = ?");

    foreach ($registros as $row) {
        $nombres_arr = explode(' ', trim($row['nombres'] ?? ''));
        $p_nombre = $nombres_arr[0] ?? '';
        $s_nombre = $nombres_arr[1] ?? '';

        $apellidos_arr = explode(' ', trim($row['apellidos'] ?? ''));
        $p_apellido = $apellidos_arr[0] ?? '';
        $s_apellido = $apellidos_arr[1] ?? '';

        $stmt_f->execute([$row['id']]);
        $fotos = $stmt_f->fetch(PDO::FETCH_ASSOC) ?: [];

        $foto_gala = trim($fotos['foto_gala'] ?? '');
        $foto_tiuna = trim($fotos['foto_patriota_tiuna'] ?? '');

        $ruta_fisica_gala = ltrim(str_replace(['../', './'], '', $foto_gala), '/\\');
        $ruta_fisica_tiuna = ltrim(str_replace(['../', './'], '', $foto_tiuna), '/\\');

        $html_foto_gala = (!empty($ruta_fisica_gala) && file_exists($ruta_fisica_gala)) 
            ? "<img src='{$base_url}{$ruta_fisica_gala}' width='48' height='70' class='img-foto'>" 
            : "<span style='color:#666; font-style:italic;'>SIN FOTO</span>";

        $html_foto_tiuna = (!empty($ruta_fisica_tiuna) && file_exists($ruta_fisica_tiuna)) 
            ? "<img src='{$base_url}{$ruta_fisica_tiuna}' width='48' height='70' class='img-foto'>" 
            : "<span style='color:#666; font-style:italic;'>SIN FOTO</span>";

        echo "<tr class='fila-fila'>";
        echo "<td class='celda-dato'><strong>{$nro}</strong></td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($row['unidad_brigada'] ?? 'LABORATORIO CRIMINALISTICO NRO. 11')) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($row['destacamento_batallon'] ?? 'SLCCT')) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['componente'] ?? 'GNB')) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['categoria'] ?? '')) . "</td>";
        echo "<td width='52' align='center' valign='middle' class='celda-foto'>{$html_foto_gala}</td>";
        echo "<td width='52' align='center' valign='middle' class='celda-foto'>{$html_foto_tiuna}</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['grado'] ?? '')) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($p_nombre)) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($s_nombre)) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($p_apellido)) . "</td>";
        echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($s_apellido)) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars($row['fecha_nacimiento'] ?? '') . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars($row['cedula'] ?? '') . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['sexo'] ?? '')) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars(strtoupper($row['grupo_sang'] ?? '')) . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars($row['telefono_celular'] ?? '') . "</td>";
        echo "<td class='celda-dato'>" . htmlspecialchars($row['contacto_emergencia_telefono'] ?? '') . "</td>";
        echo "<td class='celda-largo'>" . htmlspecialchars(strtoupper($row['direccion'] ?? '')) . "</td>";
        echo "<td class='celda-largo'>" . strtolower($row['correo'] ?? '') . "</td>";
        echo "</tr>";
        $nro++;
    }

    echo "</table></body></html>";
    exit();
}

// ==========================================
// CASO 2: GENERADOR MANUAL (ORDENADO CRONOLÓGICO)
// ==========================================
$mapeo_campos = [
    'col_primer_nombre' => ['_separar_primer_nombre', 'PRIMER NOMBRE'],
    'col_segundo_nombre' => ['_separar_segundo_nombre', 'SEGUNDO NOMBRE'],
    'col_primer_apellido' => ['_separar_primer_apellido', 'PRIMER APELLIDO'],
    'col_segundo_apellido' => ['_separar_segundo_apellido', 'SEGUNDO APELLIDO'],

    'col_unidad_brigada' => ['unidad_brigada', 'UNIDAD/ BRIGADA'],
    'col_destacamento_batallon' => ['destacamento_batallon', 'DESTACAMENTO/BATALLÓN'],
    'col_componente' => ['componente', 'COMPONENTE'],
    'col_categoria' => ['categoria', 'CATEGORIA'],
    'col_grado' => ['grado', 'GRADO/JQUIA'],

    'col_cedula' => ['cedula', 'CÉDULA DE IDENTIDAD'],
    'col_numero_carnet' => ['numero_carnet', 'NÚMERO DE CARNET'],
    'col_sexo' => ['sexo', 'SEXO'],
    'col_estado_civil' => ['estado_civil', 'ESTADO CIVIL'],
    'col_fecha_nacimiento' => ['fecha_nacimiento', 'FECHA DE NACIMIENTO'],
    'col_edad' => ['edad', 'EDAD'],
    'col_grupo_sang' => ['grupo_sang', 'GRUPO SANGUÍNEO'],
    'col_lugar_nacimiento' => ['lugar_nacimiento', 'LUGAR DE NACIMIENTO'],
    
    'col_especialidad' => ['especialidad', 'ESPECIALIDAD'],
    'col_escuela_graduacion' => ['escuela_graduacion', 'ESCUELA DE GRADUACIÓN'],
    'col_promocion' => ['promocion', 'PROMOCIÓN'],
    'col_ano_graduacion' => ['ano_graduacion', 'AÑO DE GRADUACIÓN'],
    'col_fecha_ingreso' => ['fecha_ingreso', 'FECHA DE INGRESO'],
    'col_anos_servicio' => ['anos_servicio', 'AÑOS DE SERVICIO'],
    'col_meses_servicio' => ['meses_servicio', 'MESES DE SERVICIO'],
    'col_talla_zapato' => ['talla_zapato', 'CALZADO'],
    'col_talla_camisa' => ['talla_camisa', 'TALLA CAMISA'],
    'col_talla_pantalon' => ['talla_pantalon', 'TALLA PANTALÓN'],
    'col_talla_gorra' => ['talla_gorra', 'TALLA GORRA'],
    'col_talla_patriota' => ['talla_patriota', 'TALLA PATRIOTA'],
    'col_exposicion' => ['antecedentes_observaciones', 'OBSERVACIONES'],

    'col_telefono_celular' => ['telefono_celular', 'TELÉFONO CELULAR'],
    'col_telefono_habitacion' => ['telefono_habitacion', 'TELÉFONO HABITACIÓN'],
    'col_contacto_emergencia_telefono' => ['contacto_emergencia_telefono', 'TELÉFONO EMERGENCIA'],
    'col_correo' => ['correo', 'CORREO ELECTRÓNICO'],
    'col_direccion' => ['direccion', 'DIRECCIÓN DOMICILIARIA'],
    'col_direccion_emergencia' => ['direccion_emergencia', 'DIRECCIÓN EMERGENCIA'],

    'col_foto_gala' => ['foto_gala', 'FOTOGRAFIA GALA'],
    'col_foto_patriota_tiuna' => ['foto_patriota_tiuna', 'FOTOGRAFIA TACTICO'],
    'col_foto_interior_cuartel' => ['foto_interior_cuartel', 'FOTO CUARTEL'],
    'col_foto_civil' => ['foto_civil', 'FOTO CIVIL'],

    // CORREGIDO: Se apunta a '_tabla_nombre_conyuge' para que coincida con la BD
    'col_nombre_conyuge' => ['_tabla_nombre_conyuge', 'NOMBRE CÓNYUGE'],
    'col_cedula_conyuge' => ['_tabla_cedula_conyuge', 'CÉDULA CÓNYUGE'],
    'col_telefono_conyuge' => ['_tabla_telefono_conyuge', 'TELÉFONO CÓNYUGE'],
    'col_num_hijos' => ['_tabla_num_hijos', 'NÚMERO HIJOS'],
    'col_hijos_data' => ['_tabla_hijos_data', 'DETALLE HIJOS'],
    'col_nombre_padre' => ['_tabla_nombre_padre', 'NOMBRE PADRE'],
    'col_cedula_padre' => ['_tabla_cedula_padre', 'CÉDULA PADRE'],
    'col_padre_vive' => ['_tabla_padre_vive', 'PADRE VIVE'],
    'col_nombre_madre' => ['_tabla_nombre_madre', 'NOMBRE MADRE'],
    'col_cedula_madre' => ['_tabla_cedula_madre', 'CÉDULA MADRE'],
    'col_madre_vive' => ['_tabla_madre_vive', 'MADRE VIVE'],

    'col_hipertenso' => ['hipertenso', 'HIPERTENSO'],
    'col_cardiologicos' => ['cardiologicos', 'CARDIOLÓGICOS'],
    'col_renales' => ['renales', 'RENALES'],
    'col_testiculares' => ['testiculares', 'TESTICULARES'],
    'col_pelvicos' => ['pelvicos', 'PÉLVICOS'],
    'col_infeccion_respiratoria' => ['infeccion_respiratoria', 'INFECCIÓN RESPIRATORIA'],
    'col_sinusitis' => ['sinusitis', 'SINUSITIS'],
    'col_bronquitis' => ['bronquitis', 'BRONQUITIS'],
    'col_neumonia' => ['neumonia', 'NEUMONÍA'],
    'col_asma' => ['asma', 'ASMA'],
    'col_tratamiento_asignado' => ['tratamiento_asignado', 'TRATAMIENTO ASIGNADO']
];

$cols_dinamicas = [];
$orden_recibido = trim($_POST['orden_columnas'] ?? '');

if (!empty($orden_recibido)) {
    $keys_ordenadas = explode(',', $orden_recibido);
    foreach ($keys_ordenadas as $post_key) {
        $post_key = trim($post_key);
        if (isset($_POST[$post_key]) && $_POST[$post_key] === 'si' && isset($mapeo_campos[$post_key])) {
            $cols_dinamicas[] = [
                'key' => $mapeo_campos[$post_key][0],
                'label' => $mapeo_campos[$post_key][1],
                'post_key' => $post_key
            ];
        }
    }
} else {
    foreach ($mapeo_campos as $post_key => $info) {
        if (isset($_POST[$post_key]) && $_POST[$post_key] === 'si') {
            $cols_dinamicas[] = [
                'key' => $info[0],
                'label' => $info[1],
                'post_key' => $post_key
            ];
        }
    }
}

$tipo_filtro = $_POST['funcionario_id'] ?? 'todos';

if ($tipo_filtro === 'todos') {
    $stmt = $pdo->query("SELECT * FROM personal ORDER BY apellidos ASC");
} elseif ($tipo_filtro === 'hombres_todos') {
    $stmt = $pdo->query("SELECT * FROM personal WHERE sexo = 'Masculino' OR sexo = 'M' ORDER BY apellidos ASC");
} elseif ($tipo_filtro === 'mujeres_todas') {
    $stmt = $pdo->query("SELECT * FROM personal WHERE sexo = 'Femenino' OR sexo = 'F' ORDER BY apellidos ASC");
} else {
    $stmt = $pdo->prepare("SELECT * FROM personal WHERE id = ?");
    $stmt->execute([$tipo_filtro]);
}
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

generarCabeceraExcel("RELACION_PERSONAL_LC11");

echo "<style>{$estilos_comunes_excel}</style>";
echo "<table border='1' cellspacing='0' cellpadding='4'>";

$total_columnas = 1 + count($cols_dinamicas);

echo "<tr><th colspan='{$total_columnas}' class='titulo-inst'>REPÚBLICA BOLIVARIANA DE VENEZUELA</th></tr>";
echo "<tr><th colspan='{$total_columnas}' class='titulo-inst'>MINISTERIO DEL PODER POPULAR PARA LA DEFENSA</th></tr>";
echo "<tr><th colspan='{$total_columnas}' class='titulo-inst'>COMANDO ESTRATÉGICO OPERACIONAL DE LA FANB</th></tr>";
echo "<tr><th colspan='{$total_columnas}' class='titulo-inst'>REGIÓN ESTRATÉGICA DE DEFENSA INTEGRAL NRO. 1 OCCIDENTAL</th></tr>";
echo "<tr><th colspan='{$total_columnas}' class='titulo-inst'>ZONA OPERATIVA DE DEFENSA INTEGRAL NRO. 11 \"ZULIA\"</th></tr>";
echo "<tr><th colspan='{$total_columnas}' class='titulo-inst'>COMANDO</th></tr>";
echo "<tr><td colspan='{$total_columnas}'>&nbsp;</td></tr>";
echo "<tr><th colspan='{$total_columnas}' class='titulo-sub'>RELACION DEL PERSONAL MILITAR DE LA ZODI NRO. 11 ZULIA</th></tr>";
echo "<tr><td colspan='{$total_columnas}'>&nbsp;</td></tr>";

echo "<tr>";
echo "<th class='tabla-header'>NRO</th>";

foreach ($cols_dinamicas as $col) {
    $es_foto = in_array($col['key'], ['foto_gala', 'foto_patriota_tiuna', 'foto_interior_cuartel', 'foto_civil']);
    $attr_w = $es_foto ? " width='52'" : "";
    echo "<th{$attr_w} class='tabla-header'>" . htmlspecialchars($col['label']) . "</th>";
}
echo "</tr>";

$nro = 1;
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$base_url = $protocolo . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';

$stmt_fotos_g = $pdo->prepare("SELECT * FROM personal_fotos WHERE personal_id = ?");
$stmt_fam_g = $pdo->prepare("SELECT * FROM personal_familiares WHERE personal_id = ?");
$stmt_hijos_g = $pdo->prepare("SELECT * FROM personal_hijos WHERE personal_id = ?");
$stmt_med_g = $pdo->prepare("SELECT * FROM personal_medico WHERE personal_id = ?");

foreach ($registros as $row) {
    $pid = $row['id'];

    $stmt_fotos_g->execute([$pid]);
    $fotos_json = $stmt_fotos_g->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmt_fam_g->execute([$pid]);
    $fam_json = $stmt_fam_g->fetch(PDO::FETCH_ASSOC) ?: [];

    $stmt_hijos_g->execute([$pid]);
    $hijos_arr_g = $stmt_hijos_g->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmt_med_g->execute([$pid]);
    $med_json = $stmt_med_g->fetch(PDO::FETCH_ASSOC) ?: [];

    $row_completo = array_merge($row, $fam_json, $med_json);
    $row_completo['num_hijos'] = count($hijos_arr_g);

    $nombres_arr = explode(' ', trim($row['nombres'] ?? ''));
    $p_nombre = $nombres_arr[0] ?? '';
    $s_nombre = $nombres_arr[1] ?? '';

    $apellidos_arr = explode(' ', trim($row['apellidos'] ?? ''));
    $p_apellido = $apellidos_arr[0] ?? '';
    $s_apellido = $apellidos_arr[1] ?? '';

    echo "<tr class='fila-fila'>";
    echo "<td class='celda-dato'><strong>{$nro}</strong></td>";

    foreach ($cols_dinamicas as $col) {
        $colName = $col['key'];

        if ($colName === '_separar_primer_nombre') {
            echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($p_nombre)) . "</td>";
        } elseif ($colName === '_separar_segundo_nombre') {
            echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($s_nombre)) . "</td>";
        } elseif ($colName === '_separar_primer_apellido') {
            echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($p_apellido)) . "</td>";
        } elseif ($colName === '_separar_segundo_apellido') {
            echo "<td class='celda-texto'>" . htmlspecialchars(strtoupper($s_apellido)) . "</td>";
        } elseif ($colName === '_tabla_hijos_data') {
            $txt_hijos = [];
            foreach($hijos_arr_g as $h) {
                $txt_hijos[] = ($h['nombres'] ?? '') . ' ' . ($h['apellidos'] ?? '');
            }
            $val_h = !empty($txt_hijos) ? implode(', ', $txt_hijos) : 'NINGUNO';
            echo "<td class='celda-largo'>" . htmlspecialchars(strtoupper($val_h)) . "</td>";
        } 
        elseif (in_array($colName, ['foto_gala', 'foto_patriota_tiuna', 'foto_interior_cuartel', 'foto_civil'])) {
            $ruta_foto = trim($fotos_json[$colName] ?? '');
            $ruta_fisica = ltrim(str_replace(['../', './'], '', $ruta_foto), '/\\');
            
            $html_img = (!empty($ruta_fisica) && file_exists($ruta_fisica)) 
                ? "<img src='{$base_url}{$ruta_fisica}' width='48' height='70' class='img-foto'>" 
                : "<span style='color:#666; font-style:italic;'>SIN FOTO</span>";

            echo "<td width='52' align='center' valign='middle' class='celda-foto'>{$html_img}</td>";
        } else {
            $val_key = str_replace('_tabla_', '', $colName);
            $valor = strtoupper($row_completo[$val_key] ?? '');
            
            $clase_celda = in_array($val_key, ['direccion', 'correo', 'antecedentes_observaciones', 'tratamiento_asignado']) ? 'celda-largo' : 'celda-dato';
            
            echo "<td class='{$clase_celda}'>" . htmlspecialchars($valor) . "</td>";
        }
    }
    echo "</tr>";
    $nro++;
}

echo "</table></body></html>";
exit();
?>