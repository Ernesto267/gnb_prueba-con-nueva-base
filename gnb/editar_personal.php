<?php
session_start();
require_once 'conexion.php';

// Verificar sesión de usuario
if (!isset($_SESSION['usuario_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'error', 'mensaje' => 'Sesión expirada. Por favor vuelva a iniciar sesión.']);
        exit();
    }
    header("Location: index.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: consulta_personal.php");
    exit();
}

// 1. Obtener datos del funcionario de la tabla principal 'personal'
$stmt = $pdo->prepare("SELECT * FROM personal WHERE id = ?");
$stmt->execute([$id]);
$persona = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$persona) {
    header("Location: consulta_personal.php");
    exit();
}

// Separar nombres y apellidos si están unidos en la BD para los inputs individuales
$nombres_completos = explode(' ', trim($persona['nombres'] ?? ''));
$primer_nombre_bd = $nombres_completos[0] ?? '';
$segundo_nombre_bd = implode(' ', array_slice($nombres_completos, 1));

$apellidos_completos = explode(' ', trim($persona['apellidos'] ?? ''));
$primer_apellido_bd = $apellidos_completos[0] ?? '';
$segundo_apellido_bd = implode(' ', array_slice($apellidos_completos, 1));

// Separar tipo de cédula y número (Ej: V-30748731)
$cedula_completa = $persona['cedula'] ?? '';
$partes_cedula = explode('-', $cedula_completa);
$tipo_cedula_bd = count($partes_cedula) > 1 ? $partes_cedula[0] : 'V';
$numero_cedula_bd = count($partes_cedula) > 1 ? $partes_cedula[1] : $cedula_completa;

// 2. Obtener datos de la tabla relacional 'personal_fotos'
$stmt_fotos = $pdo->prepare("SELECT * FROM personal_fotos WHERE personal_id = ?");
$stmt_fotos->execute([$id]);
$fotos_data = $stmt_fotos->fetch(PDO::FETCH_ASSOC) ?: [];

// 3. Obtener datos de 'personal_medico'
$stmt_medico = $pdo->prepare("SELECT * FROM personal_medico WHERE personal_id = ?");
$stmt_medico->execute([$id]);
$medico_data = $stmt_medico->fetch(PDO::FETCH_ASSOC) ?: [];

// 4. Obtener datos de 'personal_familiares'
$stmt_fam = $pdo->prepare("SELECT * FROM personal_familiares WHERE personal_id = ?");
$stmt_fam->execute([$id]);
$fam_data = $stmt_fam->fetch(PDO::FETCH_ASSOC) ?: [];

// Separar cédula del cónyuge
$cedula_conyuge_completa = $fam_data['cedula_conyuge'] ?? '';
$partes_c_conyuge = explode('-', $cedula_conyuge_completa);
$tipo_cedula_conyuge_bd = count($partes_c_conyuge) > 1 ? $partes_c_conyuge[0] : 'V';
$numero_cedula_conyuge_bd = count($partes_c_conyuge) > 1 ? $partes_c_conyuge[1] : $cedula_conyuge_completa;

// Separar cédula del padre
$cedula_padre_completa = $fam_data['cedula_padre'] ?? '';
$partes_c_padre = explode('-', $cedula_padre_completa);
$tipo_cedula_padre_bd = count($partes_c_padre) > 1 ? $partes_c_padre[0] : 'V';
$numero_cedula_padre_bd = count($partes_c_padre) > 1 ? $partes_c_padre[1] : $cedula_padre_completa;

// Separar cédula de la madre
$cedula_madre_completa = $fam_data['cedula_madre'] ?? '';
$partes_c_madre = explode('-', $cedula_madre_completa);
$tipo_cedula_madre_bd = count($partes_c_madre) > 1 ? $partes_c_madre[0] : 'V';
$numero_cedula_madre_bd = count($partes_c_madre) > 1 ? $partes_c_madre[1] : $cedula_madre_completa;

// 5. Obtener datos de 'personal_cursos'
$stmt_cursos = $pdo->prepare("SELECT * FROM personal_cursos WHERE personal_id = ?");
$stmt_cursos->execute([$id]);
$cursos_dec = $stmt_cursos->fetchAll(PDO::FETCH_ASSOC) ?: [];

// 6. Obtener datos de 'personal_hijos'
$stmt_hijos = $pdo->prepare("SELECT * FROM personal_hijos WHERE personal_id = ?");
$stmt_hijos->execute([$id]);
$hijos_dec = $stmt_hijos->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Procesar solicitud AJAX POST para actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_personal'])) {
    header('Content-Type: application/json');
    
    // Datos del expediente
    $numero_carnet = trim($_POST['numero_carnet'] ?? '');
    $unidad_brigada = trim($_POST['unidad_brigada'] ?? 'LABORATORIO CRIMINALISTICO NRO. 11');
    $destacamento_batallon = trim($_POST['destacamento_batallon'] ?? 'SLCCT');
    $componente = trim($_POST['componente'] ?? 'GNB');
    $grado = trim($_POST['grado'] ?? '');
    
    $primer_nombre = trim($_POST['primer_nombre'] ?? '');
    $segundo_nombre = trim($_POST['segundo_nombre'] ?? '');
    $primer_apellido = trim($_POST['primer_apellido'] ?? '');
    $segundo_apellido = trim($_POST['segundo_apellido'] ?? '');

    $nombres = trim($primer_nombre . ' ' . $segundo_nombre);
    $apellidos = trim($primer_apellido . ' ' . $segundo_apellido);

    $tipo_cedula = trim($_POST['tipo_cedula'] ?? 'V');
    $numero_cedula_bruto = trim($_POST['cedula'] ?? '');
    $cedula = !empty($numero_cedula_bruto) ? $tipo_cedula . '-' . $numero_cedula_bruto : $persona['cedula'];

    $sexo = trim($_POST['sexo'] ?? 'M');
    $antecedentes_observaciones = trim($_POST['exposicion'] ?? '');
    $lugar_nacimiento = trim($_POST['lugar_nacimiento'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
    $promocion = trim($_POST['promocion'] ?? '');
    $escuela_graduacion = trim($_POST['escuela_graduacion'] ?? '');
    $fecha_ingreso = $_POST['fecha_ingreso'] ?? null;
    $categoria = trim($_POST['categoria'] ?? '');
    $especialidad = trim($_POST['especialidad'] ?? '');
    $ano_graduacion = !empty($_POST['ano_graduacion']) ? (int)$_POST['ano_graduacion'] : null;
    $estado_civil = $_POST['estado_civil'] ?? 'Soltero/a';
    $esta_casado = $_POST['esta_casado'] ?? 'No';
    $edad = (!empty($_POST['edad']) && (int)$_POST['edad'] >= 0) ? (int)$_POST['edad'] : null;
    $grupo_sang = trim($_POST['grupo_sang'] ?? '');
    $anos_servicio = !empty($_POST['anos_servicio']) ? (int)$_POST['anos_servicio'] : null;
    $meses_servicio = !empty($_POST['meses_servicio']) ? (int)$_POST['meses_servicio'] : null;

    if (empty($primer_nombre) || empty($primer_apellido)) {
        echo json_encode([
            'status' => 'error',
            'mensaje' => 'Por favor complete los campos obligatorios: Primer Nombre y Primer Apellido.',
            'campos_invalidos' => array_filter([
                empty($primer_nombre) ? 'primer_nombre' : null,
                empty($primer_apellido) ? 'primer_apellido' : null
            ])
        ]);
        exit();
    }

    // Tallas
    $talla_zapato = trim($_POST['talla_zapato'] ?? '');
    $talla_camisa = trim($_POST['talla_camisa'] ?? '');
    $talla_pantalon = trim($_POST['talla_pantalon'] ?? '');
    $talla_gorra = trim($_POST['talla_gorra'] ?? '');
    $talla_patriota = trim($_POST['talla_patriota'] ?? '');

    // Directorio para subidas
    $directorio_destino = 'uploads/documentos/';
    if (!file_exists($directorio_destino)) {
        mkdir($directorio_destino, 0777, true);
    }

    // Cursos, Talleres, Títulos
    $num_cursos = !empty($_POST['num_cursos']) ? (int)$_POST['num_cursos'] : 0;
    $cursos_array = [];
    for ($i = 1; $i <= $num_cursos; $i++) {
        if (!empty($_POST["curso_{$i}_nombre"])) {
            $soporte_existente = $_POST["curso_{$i}_soporte_existente"] ?? null;
            $ruta_soporte_curso = $soporte_existente;

            if (isset($_FILES["curso_{$i}_soporte"]) && $_FILES["curso_{$i}_soporte"]["error"] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES["curso_{$i}_soporte"]["name"], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                    $nombre_archivo = "formacion_{$i}_" . preg_replace('/[^A-Za-z0-9\-]/', '', $cedula) . '_' . time() . '.' . $ext;
                    $ruta_completa = $directorio_destino . $nombre_archivo;
                    if (move_uploaded_file($_FILES["curso_{$i}_soporte"]["tmp_name"], $ruta_completa)) {
                        $ruta_soporte_curso = $ruta_completa;
                    }
                }
            }
            $cursos_array[] = [
                'tipo' => $_POST["curso_{$i}_tipo"] ?? 'Curso',
                'nombre' => trim($_POST["curso_{$i}_nombre"] ?? ''),
                'institucion' => $_POST["curso_{$i}_institucion"] ?? '',
                'ano' => !empty($_POST["curso_{$i}_ano"]) ? (int)$_POST["curso_{$i}_ano"] : null,
                'soporte' => $ruta_soporte_curso
            ];
        }
    }

    // Contactos
    $direccion = trim($_POST['direccion'] ?? '');
    $direccion_emergencia = trim($_POST['direccion_emergencia'] ?? '');
    $telefono_celular = trim($_POST['telefono_celular'] ?? '');
    $telefono_habitacion = trim($_POST['telefono_habitacion'] ?? '');
    $contacto_emergencia_telefono = trim($_POST['contacto_emergencia_telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    // Datos Familiares (Cónyuge)
    $tipo_cedula_conyuge = trim($_POST['tipo_cedula_conyuge'] ?? 'V');
    $cedula_conyuge_bruto = trim($_POST['cedula_conyuge'] ?? '');
    $cedula_conyuge_completa = ($esta_casado === 'Sí' && !empty($cedula_conyuge_bruto)) ? $tipo_cedula_conyuge . '-' . $cedula_conyuge_bruto : '';

    $nombre_conyuge = ($esta_casado === 'Sí') ? trim($_POST['nombre_conyuge'] ?? '') : '';
    $telefono_conyuge = ($esta_casado === 'Sí') ? trim($_POST['telefono_conyuge'] ?? '') : '';
    
    $ruta_cedula_conyuge = $fam_data['foto_cedula_conyuge'] ?? null;
    if ($esta_casado === 'Sí' && isset($_FILES['foto_cedula_conyuge']) && $_FILES['foto_cedula_conyuge']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_cedula_conyuge']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
            $nombre_archivo = 'cedula_conyuge_' . preg_replace('/[^A-Za-z0-9\-]/', '', $cedula) . '_' . time() . '.' . $ext;
            $ruta_completa = $directorio_destino . $nombre_archivo;
            if (move_uploaded_file($_FILES['foto_cedula_conyuge']['tmp_name'], $ruta_completa)) {
                $ruta_cedula_conyuge = $ruta_completa;
            }
        }
    }

    // Hijos
    $num_hijos = !empty($_POST['num_hijos']) ? (int)$_POST['num_hijos'] : 0;
    $hijos_array = [];
    for ($i = 1; $i <= $num_hijos; $i++) {
        if (!empty($_POST["hijo_{$i}_nombres"])) {
            $prev_hijo = $hijos_dec[$i - 1] ?? [];
            
            $ruta_hijo_cedula = $prev_hijo['cedula_doc'] ?? null;
            $ruta_hijo_partida_frente = $prev_hijo['partida_frente'] ?? null;
            $ruta_hijo_partida_reverso = $prev_hijo['partida_reverso'] ?? null;

            if (isset($_FILES["hijo_{$i}_cedula"]) && $_FILES["hijo_{$i}_cedula"]["error"] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES["hijo_{$i}_cedula"]["name"], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                    $nom_file = "hijo_{$i}_cedula_" . preg_replace('/[^A-Za-z0-9\-]/', '', $cedula) . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES["hijo_{$i}_cedula"]["tmp_name"], $directorio_destino . $nom_file)) {
                        $ruta_hijo_cedula = $directorio_destino . $nom_file;
                    }
                }
            }
            if (isset($_FILES["hijo_{$i}_partida_frente"]) && $_FILES["hijo_{$i}_partida_frente"]["error"] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES["hijo_{$i}_partida_frente"]["name"], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                    $nom_file = "hijo_{$i}_partida_frente_" . preg_replace('/[^A-Za-z0-9\-]/', '', $cedula) . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES["hijo_{$i}_partida_frente"]["tmp_name"], $directorio_destino . $nom_file)) {
                        $ruta_hijo_partida_frente = $directorio_destino . $nom_file;
                    }
                }
            }
            if (isset($_FILES["hijo_{$i}_partida_reverso"]) && $_FILES["hijo_{$i}_partida_reverso"]["error"] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES["hijo_{$i}_partida_reverso"]["name"], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                    $nom_file = "hijo_{$i}_partida_reverso_" . preg_replace('/[^A-Za-z0-9\-]/', '', $cedula) . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES["hijo_{$i}_partida_reverso"]["tmp_name"], $directorio_destino . $nom_file)) {
                        $ruta_hijo_partida_reverso = $directorio_destino . $nom_file;
                    }
                }
            }

            $hijos_array[] = [
                'nombres' => $_POST["hijo_{$i}_nombres"] ?? '',
                'apellidos' => $_POST["hijo_{$i}_apellidos"] ?? '',
                'sexo' => $_POST["hijo_{$i}_sexo"] ?? 'M',
                'fecha_nac' => !empty($_POST["hijo_{$i}_fecha_nac"]) ? $_POST["hijo_{$i}_fecha_nac"] : null,
                'edad' => (!empty($_POST["hijo_{$i}_edad"]) && (int)$_POST["hijo_{$i}_edad"] >= 0) ? (int)$_POST["hijo_{$i}_edad"] : null,
                'cedula_doc' => $ruta_hijo_cedula,
                'partida_frente' => $ruta_hijo_partida_frente,
                'partida_reverso' => $ruta_hijo_partida_reverso
            ];
        }
    }

    // Datos Padre
    $tipo_cedula_padre = trim($_POST['tipo_cedula_padre'] ?? 'V');
    $cedula_padre_bruto = trim($_POST['cedula_padre'] ?? '');
    $cedula_padre = !empty($cedula_padre_bruto) ? $tipo_cedula_padre . '-' . $cedula_padre_bruto : '';

    $nombre_padre = trim($_POST['nombre_padre'] ?? '');
    $padre_vive = $_POST['padre_vive'] ?? 'Sí';
    $telefono_padre = trim($_POST['telefono_padre'] ?? '');
    $direccion_padre = trim($_POST['direccion_padre'] ?? '');
    
    $ruta_cedula_padre = $fam_data['foto_cedula_padre'] ?? null;
    if (isset($_FILES['foto_cedula_padre']) && $_FILES['foto_cedula_padre']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_cedula_padre']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
            $nombre_archivo = 'cedula_padre_' . preg_replace('/[^A-Za-z0-9\-]/', '', $cedula) . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto_cedula_padre']['tmp_name'], $directorio_destino . $nombre_archivo)) {
                $ruta_cedula_padre = $directorio_destino . $nombre_archivo;
            }
        }
    }

    // Datos Madre
    $tipo_cedula_madre = trim($_POST['tipo_cedula_madre'] ?? 'V');
    $cedula_madre_bruto = trim($_POST['cedula_madre'] ?? '');
    $cedula_madre = !empty($cedula_madre_bruto) ? $tipo_cedula_madre . '-' . $cedula_madre_bruto : '';

    $nombre_madre = trim($_POST['nombre_madre'] ?? '');
    $madre_vive = $_POST['madre_vive'] ?? 'Sí';
    $telefono_madre = trim($_POST['telefono_madre'] ?? '');
    $direccion_madre = trim($_POST['direccion_madre'] ?? '');
    
    $ruta_cedula_madre = $fam_data['foto_cedula_madre'] ?? null;
    if (isset($_FILES['foto_cedula_madre']) && $_FILES['foto_cedula_madre']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_cedula_madre']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
            $nombre_archivo = 'cedula_madre_' . preg_replace('/[^A-Za-z0-9\-]/', '', $cedula) . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto_cedula_madre']['tmp_name'], $directorio_destino . $nombre_archivo)) {
                $ruta_cedula_madre = $directorio_destino . $nombre_archivo;
            }
        }
    }

    // Archivos Fotográficos (para la tabla personal_fotos)
    $ruta_foto_cedula = $fotos_data['foto_cedula'] ?? null;
    if (isset($_FILES['foto_cedula']) && $_FILES['foto_cedula']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_cedula']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
            $nombre_archivo = 'cedula_' . preg_replace('/[^A-Za-z0-9\-]/', '', $cedula) . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto_cedula']['tmp_name'], $directorio_destino . $nombre_archivo)) {
                $ruta_foto_cedula = $directorio_destino . $nombre_archivo;
            }
        }
    }

    $ruta_carnet_frente = $fotos_data['foto_carnet_frente'] ?? null;
    if (isset($_FILES['foto_carnet_frente']) && $_FILES['foto_carnet_frente']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_carnet_frente']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $nombre_archivo = 'carnet_frente_' . preg_replace('/[^A-Za-z0-9\-]/', '', $cedula) . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto_carnet_frente']['tmp_name'], $directorio_destino . $nombre_archivo)) {
                $ruta_carnet_frente = $directorio_destino . $nombre_archivo;
            }
        }
    }

    $ruta_carnet_reverso = $fotos_data['foto_carnet_reverso'] ?? null;
    if (isset($_FILES['foto_carnet_reverso']) && $_FILES['foto_carnet_reverso']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_carnet_reverso']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $nombre_archivo = 'carnet_reverso_' . preg_replace('/[^A-Za-z0-9\-]/', '', $cedula) . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto_carnet_reverso']['tmp_name'], $directorio_destino . $nombre_archivo)) {
                $ruta_carnet_reverso = $directorio_destino . $nombre_archivo;
            }
        }
    }

    $ruta_partida_frente = $fotos_data['foto_partida_frente'] ?? null;
    if (isset($_FILES['foto_partida_frente']) && $_FILES['foto_partida_frente']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_partida_frente']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
            $nombre_archivo = 'partida_frente_' . preg_replace('/[^A-Za-z0-9\-]/', '', $cedula) . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto_partida_frente']['tmp_name'], $directorio_destino . $nombre_archivo)) {
                $ruta_partida_frente = $directorio_destino . $nombre_archivo;
            }
        }
    }

    $ruta_partida_reverso = $fotos_data['foto_partida_reverso'] ?? null;
    if (isset($_FILES['foto_partida_reverso']) && $_FILES['foto_partida_reverso']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_partida_reverso']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
            $nombre_archivo = 'partida_reverso_' . preg_replace('/[^A-Za-z0-9\-]/', '', $cedula) . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['foto_partida_reverso']['tmp_name'], $directorio_destino . $nombre_archivo)) {
                $ruta_partida_reverso = $directorio_destino . $nombre_archivo;
            }
        }
    }

    // Fotografías Oficiales
    $rutas_fotos_oficiales = [
        'foto_gala' => $fotos_data['foto_gala'] ?? null, 
        'foto_patriota_tiuna' => $fotos_data['foto_patriota_tiuna'] ?? null, 
        'foto_interior_cuartel' => $fotos_data['foto_interior_cuartel'] ?? null,
        'foto_civil' => $fotos_data['foto_civil'] ?? null
    ];
    foreach (['foto_gala', 'foto_patriota_tiuna', 'foto_interior_cuartel', 'foto_civil'] as $campo_archivo) {
        if (isset($_FILES[$campo_archivo]) && $_FILES[$campo_archivo]['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES[$campo_archivo]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $nombre_archivo = "{$campo_archivo}_" . preg_replace('/[^A-Za-z0-9\-]/', '', $cedula) . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES[$campo_archivo]['tmp_name'], $directorio_destino . $nombre_archivo)) {
                    $rutas_fotos_oficiales[$campo_archivo] = $directorio_destino . $nombre_archivo;
                }
            }
        }
    }

    // Antecedentes Médicos
    $hipertenso = $_POST['hipertenso'] ?? 'No';
    $cardiologicos = $_POST['cardiologicos'] ?? 'No';
    $renales = $_POST['renales'] ?? 'No';
    $testiculares = $_POST['testiculares'] ?? 'No';
    $pelvicos = $_POST['pelvicos'] ?? 'No';
    $infeccion_respiratoria = $_POST['infeccion_respiratoria'] ?? 'No';
    $sinusitis = $_POST['sinusitis'] ?? 'No';
    $bronquitis = $_POST['bronquitis'] ?? 'No';
    $neumonia = $_POST['neumonia'] ?? 'No';
    $asma = $_POST['asma'] ?? 'No';
    $tratamiento_asignado = trim($_POST['tratamiento_asignado'] ?? '');

    try {
        $pdo->beginTransaction();

        // A. Actualizar tabla principal 'personal'
        $sql = "UPDATE personal SET 
                    numero_carnet = :numero_carnet, 
                    unidad_brigada = :unidad_brigada,
                    destacamento_batallon = :destacamento_batallon,
                    componente = :componente,
                    grado = :grado, 
                    nombres = :nombres,
                    apellidos = :apellidos,
                    cedula = :cedula,
                    sexo = :sexo,
                    antecedentes_observaciones = :antecedentes_observaciones, 
                    lugar_nacimiento = :lugar_nacimiento,
                    fecha_nacimiento = :fecha_nacimiento,
                    promocion = :promocion, 
                    escuela_graduacion = :escuela_graduacion, 
                    fecha_ingreso = :fecha_ingreso, 
                    categoria = :categoria, 
                    especialidad = :especialidad, 
                    ano_graduacion = :ano_graduacion, 
                    estado_civil = :estado_civil,
                    edad = :edad,
                    grupo_sang = :grupo_sang,
                    anos_servicio = :anos_servicio, 
                    meses_servicio = :meses_servicio, 
                    talla_zapato = :talla_zapato, 
                    talla_camisa = :talla_camisa, 
                    talla_pantalon = :talla_pantalon, 
                    talla_gorra = :talla_gorra, 
                    talla_patriota = :talla_patriota, 
                    direccion = :direccion, 
                    direccion_emergencia = :direccion_emergencia, 
                    telefono_celular = :telefono_celular, 
                    telefono_habitacion = :telefono_habitacion, 
                    contacto_emergencia_telefono = :contacto_emergencia_telefono, 
                    correo = :correo
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'numero_carnet' => $numero_carnet, 
            'unidad_brigada' => $unidad_brigada,
            'destacamento_batallon' => $destacamento_batallon,
            'componente' => $componente,
            'grado' => $grado, 
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'cedula' => $cedula,
            'sexo' => $sexo,
            'antecedentes_observaciones' => $antecedentes_observaciones, 
            'lugar_nacimiento' => $lugar_nacimiento,
            'fecha_nacimiento' => !empty($fecha_nacimiento) ? $fecha_nacimiento : null,
            'promocion' => $promocion, 
            'escuela_graduacion' => $escuela_graduacion, 
            'fecha_ingreso' => !empty($fecha_ingreso) ? $fecha_ingreso : null, 
            'categoria' => $categoria, 
            'especialidad' => $especialidad, 
            'ano_graduacion' => $ano_graduacion, 
            'estado_civil' => $estado_civil, 
            'edad' => $edad,
            'grupo_sang' => $grupo_sang,
            'anos_servicio' => $anos_servicio, 
            'meses_servicio' => $meses_servicio, 
            'talla_zapato' => $talla_zapato, 
            'talla_camisa' => $talla_camisa, 
            'talla_pantalon' => $talla_pantalon, 
            'talla_gorra' => $talla_gorra, 
            'talla_patriota' => $talla_patriota, 
            'direccion' => $direccion, 
            'direccion_emergencia' => $direccion_emergencia, 
            'telefono_celular' => $telefono_celular, 
            'telefono_habitacion' => $telefono_habitacion, 
            'contacto_emergencia_telefono' => $contacto_emergencia_telefono, 
            'correo' => $correo, 
            'id' => $id
        ]);

        // B. Actualizar o insertar en 'personal_fotos'
        $stmt_check_f = $pdo->prepare("SELECT id FROM personal_fotos WHERE personal_id = ?");
        $stmt_check_f->execute([$id]);
        if ($stmt_check_f->fetch()) {
            $stmt_up_f = $pdo->prepare("UPDATE personal_fotos SET foto_cedula = ?, foto_carnet_frente = ?, foto_carnet_reverso = ?, foto_partida_frente = ?, foto_partida_reverso = ?, foto_gala = ?, foto_patriota_tiuna = ?, foto_interior_cuartel = ?, foto_civil = ? WHERE personal_id = ?");
            $stmt_up_f->execute([
                $ruta_foto_cedula, $ruta_carnet_frente, $ruta_carnet_reverso, $ruta_partida_frente, $ruta_partida_reverso,
                $rutas_fotos_oficiales['foto_gala'], $rutas_fotos_oficiales['foto_patriota_tiuna'], 
                $rutas_fotos_oficiales['foto_interior_cuartel'], $rutas_fotos_oficiales['foto_civil'], $id
            ]);
        } else {
            $stmt_ins_f = $pdo->prepare("INSERT INTO personal_fotos (personal_id, foto_cedula, foto_carnet_frente, foto_carnet_reverso, foto_partida_frente, foto_partida_reverso, foto_gala, foto_patriota_tiuna, foto_interior_cuartel, foto_civil) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_ins_f->execute([
                $id, $ruta_foto_cedula, $ruta_carnet_frente, $ruta_carnet_reverso, $ruta_partida_frente, $ruta_partida_reverso,
                $rutas_fotos_oficiales['foto_gala'], $rutas_fotos_oficiales['foto_patriota_tiuna'], 
                $rutas_fotos_oficiales['foto_interior_cuartel'], $rutas_fotos_oficiales['foto_civil']
            ]);
        }

        // C. Sincronizar 'personal_cursos'
        $pdo->prepare("DELETE FROM personal_cursos WHERE personal_id = ?")->execute([$id]);
        if (!empty($cursos_array)) {
            $stmt_curso = $pdo->prepare("INSERT INTO personal_cursos (personal_id, tipo, nombre, institucion, ano, soporte) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($cursos_array as $curso) {
                $stmt_curso->execute([
                    $id, $curso['tipo'], $curso['nombre'], 
                    !empty($curso['institucion']) ? $curso['institucion'] : null, 
                    !empty($curso['ano']) ? $curso['ano'] : null, 
                    !empty($curso['soporte']) ? $curso['soporte'] : null
                ]);
            }
        }

        // D. Actualizar o insertar en 'personal_familiares'
        $stmt_check_fam = $pdo->prepare("SELECT id FROM personal_familiares WHERE personal_id = ?");
        $stmt_check_fam->execute([$id]);
        if ($stmt_check_fam->fetch()) {
            $stmt_up_fam = $pdo->prepare("UPDATE personal_familiares SET nombre_conyuge = ?, cedula_conyuge = ?, telefono_conyuge = ?, foto_cedula_conyuge = ?, nombre_padre = ?, cedula_padre = ?, padre_vive = ?, telefono_padre = ?, direccion_padre = ?, foto_cedula_padre = ?, nombre_madre = ?, cedula_madre = ?, madre_vive = ?, telefono_madre = ?, direccion_madre = ?, foto_cedula_madre = ? WHERE personal_id = ?");
            $stmt_up_fam->execute([
                !empty($nombre_conyuge) ? $nombre_conyuge : null, !empty($cedula_conyuge_completa) ? $cedula_conyuge_completa : null, !empty($telefono_conyuge) ? $telefono_conyuge : null, !empty($ruta_cedula_conyuge) ? $ruta_cedula_conyuge : null,
                !empty($nombre_padre) ? $nombre_padre : null, !empty($cedula_padre) ? $cedula_padre : null, !empty($padre_vive) ? $padre_vive : 'Sí', !empty($telefono_padre) ? $telefono_padre : null, !empty($direccion_padre) ? $direccion_padre : null, !empty($ruta_cedula_padre) ? $ruta_cedula_padre : null,
                !empty($nombre_madre) ? $nombre_madre : null, !empty($cedula_madre) ? $cedula_madre : null, !empty($madre_vive) ? $madre_vive : 'Sí', !empty($telefono_madre) ? $telefono_madre : null, !empty($direccion_madre) ? $direccion_madre : null, !empty($ruta_cedula_madre) ? $ruta_cedula_madre : null, $id
            ]);
        } else {
            $stmt_ins_fam = $pdo->prepare("INSERT INTO personal_familiares (personal_id, nombre_conyuge, cedula_conyuge, telefono_conyuge, foto_cedula_conyuge, nombre_padre, cedula_padre, padre_vive, telefono_padre, direccion_padre, foto_cedula_padre, nombre_madre, cedula_madre, madre_vive, telefono_madre, direccion_madre, foto_cedula_madre) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_ins_fam->execute([
                $id, !empty($nombre_conyuge) ? $nombre_conyuge : null, !empty($cedula_conyuge_completa) ? $cedula_conyuge_completa : null, !empty($telefono_conyuge) ? $telefono_conyuge : null, !empty($ruta_cedula_conyuge) ? $ruta_cedula_conyuge : null,
                !empty($nombre_padre) ? $nombre_padre : null, !empty($cedula_padre) ? $cedula_padre : null, !empty($padre_vive) ? $padre_vive : 'Sí', !empty($telefono_padre) ? $telefono_padre : null, !empty($direccion_padre) ? $direccion_padre : null, !empty($ruta_cedula_padre) ? $ruta_cedula_padre : null,
                !empty($nombre_madre) ? $nombre_madre : null, !empty($cedula_madre) ? $cedula_madre : null, !empty($madre_vive) ? $madre_vive : 'Sí', !empty($telefono_madre) ? $telefono_madre : null, !empty($direccion_madre) ? $direccion_madre : null, !empty($ruta_cedula_madre) ? $ruta_cedula_madre : null
            ]);
        }

        // E. Sincronizar 'personal_hijos'
        $pdo->prepare("DELETE FROM personal_hijos WHERE personal_id = ?")->execute([$id]);
        if (!empty($hijos_array)) {
            $stmt_hijo = $pdo->prepare("INSERT INTO personal_hijos (personal_id, nombres, apellidos, sexo, fecha_nac, edad, cedula_doc, partida_frente, partida_reverso) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($hijos_array as $hijo) {
                $stmt_hijo->execute([
                    $id, $hijo['nombres'], $hijo['apellidos'], $hijo['sexo'], $hijo['fecha_nac'], $hijo['edad'],
                    $hijo['cedula_doc'], $hijo['partida_frente'], $hijo['partida_reverso']
                ]);
            }
        }

        // F. Actualizar o insertar en 'personal_medico'
        $stmt_check_m = $pdo->prepare("SELECT id FROM personal_medico WHERE personal_id = ?");
        $stmt_check_m->execute([$id]);
        if ($stmt_check_m->fetch()) {
            $stmt_up_m = $pdo->prepare("UPDATE personal_medico SET hipertenso = ?, cardiologicos = ?, renales = ?, testiculares = ?, pelvicos = ?, infeccion_respiratoria = ?, sinusitis = ?, bronquitis = ?, neumonia = ?, asma = ?, tratamiento_asignado = ? WHERE personal_id = ?");
            $stmt_up_m->execute([
                $hipertenso, $cardiologicos, $renales, $testiculares, $pelvicos, $infeccion_respiratoria,
                $sinusitis, $bronquitis, $neumonia, $asma, !empty($tratamiento_asignado) ? $tratamiento_asignado : null, $id
            ]);
        } else {
            $stmt_ins_m = $pdo->prepare("INSERT INTO personal_medico (personal_id, hipertenso, cardiologicos, renales, testiculares, pelvicos, infeccion_respiratoria, sinusitis, bronquitis, neumonia, asma, tratamiento_asignado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_ins_m->execute([
                $id, $hipertenso, $cardiologicos, $renales, $testiculares, $pelvicos, $infeccion_respiratoria,
                $sinusitis, $bronquitis, $neumonia, $asma, !empty($tratamiento_asignado) ? $tratamiento_asignado : null
            ]);
        }

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'mensaje' => 'Expediente del funcionario actualizado exitosamente. Redirigiendo...'
        ]);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            'status' => 'error',
            'mensaje' => 'Error en la Base de Datos: ' . $e->getMessage()
        ]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Criminalístico N° 11 (Zulia) - Modificar Expediente</title>
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
            --input-bg: #0b1120; --section-bg: #1e293b;
        }
        [data-theme="light"] {
            --bg-body: #e2e8f0; --bg-navbar: #ffffff; --text-main: #0f172a; --text-muted: #475569;
            --card-bg: #ffffff; --card-border: #475569; --card-shadow: 0 15px 35px rgba(15, 23, 42, 0.12);
            --input-bg: #ffffff; --section-bg: #f8fafc;
        }
        body { background-color: var(--bg-body); min-height: 100vh; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; color: var(--text-main); transition: background 0.3s ease, color 0.3s ease; }
        .form-label { color: var(--text-main) !important; }
        .text-muted { color: var(--text-muted) !important; }
        .navbar-gnb { background: var(--bg-navbar); border-bottom: 1px solid var(--card-border); box-shadow: 0 4px 25px rgba(0,0,0,0.1); }
        .navbar-brand img { width: 140px; height: 140px; object-fit: contain; position: relative; top: 4px; margin-top: -40px; margin-bottom: -40px; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3)); }
        .btn-nav-custom { color: var(--text-main) !important; border-color: var(--card-border) !important; background: transparent; transition: all 0.2s ease; }
        .btn-nav-custom:hover { background: rgba(59, 130, 246, 0.1); color: #3b82f6 !important; }
        .step-card { border-radius: 1.5rem; box-shadow: var(--card-shadow); background: var(--card-bg); border: 1.5px solid var(--card-border); color: var(--text-main); }
        .form-control, .form-select { background-color: var(--input-bg) !important; color: var(--text-main) !important; border: 1.5px solid var(--card-border) !important; border-radius: 0.75rem; padding: 0.65rem 0.9rem; font-size: 0.9rem; transition: all 0.25s ease; }
        .form-control:focus, .form-select:focus { border-color: #3b82f6 !important; box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.2) !important; background-color: var(--card-bg) !important; }
        .form-control::placeholder { color: var(--text-muted) !important; opacity: 0.8; }
        .form-select option { background-color: var(--card-bg); color: var(--text-main); }
        .box-section-custom { background-color: var(--section-bg); border: 1.5px solid var(--card-border); border-radius: 1rem; padding: 1.25rem; box-shadow: 0 4px 12px rgba(0,0,0,0.03); color: var(--text-main); }
        .stepper-wrapper { display: flex; justify-content: space-between; margin-bottom: 2.5rem; position: relative; }
        .stepper-wrapper::before { content: ''; position: absolute; top: 24px; left: 5%; width: 90%; height: 4px; background-color: var(--card-border); z-index: 1; border-radius: 4px; }
        .stepper-item { position: relative; display: flex; flex-direction: column; align-items: center; flex: 1; z-index: 2; cursor: pointer; transition: transform 0.2s ease; }
        .stepper-item:hover { transform: translateY(-2px); }
        .stepper-item .step-counter { width: 52px; height: 52px; border-radius: 50%; background: var(--card-bg); border: 3px solid var(--card-border); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; color: var(--text-muted); margin-bottom: 10px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 6px 15px rgba(0,0,0,0.1); }
        .stepper-item .step-name { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); text-align: center; transition: color 0.3s ease; }
        .stepper-item.active .step-counter { border-color: #3b82f6; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #ffffff; transform: scale(1.15); box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4); }
        .stepper-item.active .step-name { color: #3b82f6; font-weight: 700; }
        .stepper-item.completed .step-counter { background: linear-gradient(135deg, #10b981, #059669); border-color: #10b981; color: #ffffff; box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3); }
        .stepper-item.completed .step-name { color: var(--text-main); }
        .antecedente-box { background: var(--section-bg); border: 1.5px solid var(--card-border); border-radius: 0.85rem; padding: 14px 18px; text-align: center; height: 100%; transition: all 0.2s ease; }
        .antecedente-box:hover { border-color: #3b82f6 !important; box-shadow: 0 6px 15px rgba(59, 130, 246, 0.15); }
        .preview-container { position: relative; display: none; margin-top: 10px; max-width: 150px; }
        .preview-img { max-height: 100px; width: 100%; object-fit: cover; border-radius: 8px; border: 2px dashed #3b82f6; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn-eliminar-foto { position: absolute; top: -8px; right: -8px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; font-size: 13px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 3px 8px rgba(220, 53, 69, 0.4); transition: transform 0.2s; }
        .btn-eliminar-foto:hover { transform: scale(1.1); }
        .seccion-formulario { display: none; }
        .seccion-formulario.activa { display: block; animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .is-invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important; }
        
        /* Ocultar flechas/recuadros blancos en campos de número en modo oscuro */
        [data-theme="dark"] input[type="number"]::-webkit-inner-spin-button,
        [data-theme="dark"] input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            background: transparent;
            opacity: 0;
        }
        [data-theme="dark"] input[type="number"] {
            color-scheme: dark;
        }

        /* Estilo Elegante Tipo Píldora Sólida Abajo a la Derecha */
        .alert-custom-success {
            background-color: #198754 !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 1.25rem !important;
            box-shadow: 0 10px 25px rgba(25, 135, 84, 0.3);
            font-weight: 500;
        }
        .alert-custom-success .btn-close {
            filter: brightness(0) invert(1);
        }
        .alert-custom-danger {
            background-color: #dc3545 !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 1.25rem !important;
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.3);
            font-weight: 500;
        }
        .alert-custom-danger .btn-close {
            filter: brightness(0) invert(1);
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-gnb shadow-lg py-3 sticky-top">
        <div class="container px-4">
            <a class="navbar-brand fw-bold fs-5 d-flex align-items-center" href="dashboard.php" style="color: var(--text-main);">
                <img src="imagen/logo_gnb.png?v=<?php echo time(); ?>" alt="Escudo GNB" class="me-3"> Laboratorio Criminalístico N<sup>o</sup> 11 (Zulia)
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="consulta_personal.php" class="btn btn-nav-custom btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Volver a Consulta
                </a>
                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-4 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCerrarSesion">
                    <i class="bi bi-box-arrow-right me-1"></i> Salir
                </button>
            </div>
        </div>
    </nav>

    <!-- CONTENEDOR FLOTANTE ABAJO A LA DERECHA -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
        <div id="alertaRespuesta" class="toast align-items-center border-0 shadow-lg d-none" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-semibold d-flex align-items-center py-3 px-4">
                    <i id="alertaIcono" class="bi fs-5 me-2"></i><span id="alertaTexto"></span>
                </div>
                <button type="button" class="btn-close me-2 m-auto" onclick="ocultarAlerta()"></button>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-extrabold mb-1" style="color: var(--text-main); letter-spacing: -0.5px;">Modificar Expediente Militar</h2>
                        <p class="mb-0 text-muted">Actualizando registro de: <strong><?php echo htmlspecialchars(($persona['apellidos'] ?? '') . ' ' . ($persona['nombres'] ?? '')); ?></strong> (C.I: <?php echo htmlspecialchars($persona['cedula'] ?? ''); ?>)</p>
                    </div>
                </div>

                <div class="stepper-wrapper">
                    <div class="stepper-item active" id="step-tab-1" onclick="cambiarSeccion(1)">
                        <div class="step-counter"><i class="bi bi-person-vcard"></i></div>
                        <div class="step-name">Identificación</div>
                    </div>
                    <div class="stepper-item" id="step-tab-2" onclick="cambiarSeccion(2)">
                        <div class="step-counter"><i class="bi bi-journal-bookmark-fill"></i></div>
                        <div class="step-name">Cursos y Títulos</div>
                    </div>
                    <div class="stepper-item" id="step-tab-3" onclick="cambiarSeccion(3)">
                        <div class="step-counter"><i class="bi bi-people-fill"></i></div>
                        <div class="step-name">Datos Familiares</div>
                    </div>
                    <div class="stepper-item" id="step-tab-4" onclick="cambiarSeccion(4)">
                        <div class="step-counter"><i class="bi bi-heart-pulse-fill"></i></div>
                        <div class="step-name">Médicos</div>
                    </div>
                </div>

                <div class="card step-card p-4 p-md-5">
                    <form id="formPersonal" action="editar_personal.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="actualizar_personal" value="1">

                        <!-- SECCIÓN 1 -->
                        <div id="seccion-1" class="seccion-formulario activa">
                            <h4 class="fw-bold text-primary mb-4 pb-2 border-bottom d-flex align-items-center" style="border-color: var(--card-border) !important;">
                                <i class="bi bi-person-vcard fs-3 me-2"></i> 1. Datos de Identificación y Expediente
                            </h4>

                            <h6 class="fw-bold mb-3 text-uppercase fs-7 text-primary tracking-wide"><i class="bi bi-person-badge me-1"></i> Identificación Personal</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label for="cedula" class="form-label fw-semibold">Cédula de Identidad</label>
                                    <div class="input-group">
                                        <select class="form-select px-2" id="tipo_cedula" name="tipo_cedula" style="max-width: 75px;">
                                            <option value="V" <?php echo ($tipo_cedula_bd === 'V') ? 'selected' : ''; ?>>V</option>
                                            <option value="E" <?php echo ($tipo_cedula_bd === 'E') ? 'selected' : ''; ?>>E</option>
                                            <option value="P" <?php echo ($tipo_cedula_bd === 'P') ? 'selected' : ''; ?>>P</option>
                                        </select>
                                        <input type="text" class="form-control" id="cedula" name="cedula" value="<?php echo htmlspecialchars($numero_cedula_bd); ?>" oninput="validarCedula(this)" required>
                                    </div>
                                    <div class="mt-2">
                                        <label for="foto_cedula" class="btn btn-sm btn-outline-primary w-100 fw-semibold rounded-pill">
                                            <i class="bi bi-card-image me-1"></i> Actualizar Cédula
                                        </label>
                                        <input type="file" id="foto_cedula" name="foto_cedula" accept="image/*,.pdf" style="display: none;" onchange="previewImagen(this, 'imgPreviewCedula', 'containerCedula')">
                                    </div>
                                    <div id="containerCedula" class="preview-container" style="<?php echo !empty($fotos_data['foto_cedula']) ? 'display:block;' : 'display:none;'; ?>">
                                        <img id="imgPreviewCedula" class="preview-img" src="<?php echo htmlspecialchars($fotos_data['foto_cedula'] ?? ''); ?>" alt="Previsualización Cédula">
                                        <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('foto_cedula', 'containerCedula')"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </div>

                                <div class="col-md-9">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label for="primer_nombre" class="form-label fw-semibold">Primer Nombre</label>
                                            <input type="text" class="form-control" id="primer_nombre" name="primer_nombre" value="<?php echo htmlspecialchars($primer_nombre_bd); ?>" oninput="soloLetras(this)" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="segundo_nombre" class="form-label fw-semibold">Segundo Nombre</label>
                                            <input type="text" class="form-control" id="segundo_nombre" name="segundo_nombre" value="<?php echo htmlspecialchars($segundo_nombre_bd); ?>" oninput="soloLetras(this)">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="primer_apellido" class="form-label fw-semibold">Primer Apellido</label>
                                            <input type="text" class="form-control" id="primer_apellido" name="primer_apellido" value="<?php echo htmlspecialchars($primer_apellido_bd); ?>" oninput="soloLetras(this)" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="segundo_apellido" class="form-label fw-semibold">Segundo Apellido</label>
                                            <input type="text" class="form-control" id="segundo_apellido" name="segundo_apellido" value="<?php echo htmlspecialchars($segundo_apellido_bd); ?>" oninput="soloLetras(this)">
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <label for="numero_carnet" class="form-label fw-semibold">Número de Carnet</label>
                                            <input type="text" class="form-control" id="numero_carnet" name="numero_carnet" value="<?php echo htmlspecialchars($persona['numero_carnet'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="sexo" class="form-label fw-semibold">Sexo</label>
                                            <select class="form-select" id="sexo" name="sexo">
                                                <option value="M" <?php echo (($persona['sexo'] ?? '') === 'M') ? 'selected' : ''; ?>>M</option>
                                                <option value="F" <?php echo (($persona['sexo'] ?? '') === 'F') ? 'selected' : ''; ?>>F</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="grupo_sang" class="form-label fw-semibold">Grupo Sanguíneo</label>
                                            <select class="form-select" id="grupo_sang" name="grupo_sang">
                                                <option value="" disabled>Seleccionar...</option>
                                                <?php 
                                                $grupos = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
                                                foreach($grupos as $g) {
                                                    $sel = (($persona['grupo_sang'] ?? '') === $g) ? 'selected' : '';
                                                    echo "<option value='$g' $sel>$g</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="estado_civil" class="form-label fw-semibold">Estado Civil</label>
                                            <select class="form-select" id="estado_civil" name="estado_civil" onchange="evaluarEstadoCivil()">
                                                <?php 
                                                $estados = ['Soltero/a', 'Casado/a', 'Divorciado/a', 'Viudo/a', 'Concubinato'];
                                                foreach($estados as $est) {
                                                    $sel = (($persona['estado_civil'] ?? '') === $est) ? 'selected' : '';
                                                    echo "<option value='$est' $sel>$est</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label for="fecha_nacimiento" class="form-label fw-semibold">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($persona['fecha_nacimiento'] ?? ''); ?>" onchange="calcularEdadPersonal()">
                                </div>
                                <div class="col-md-2">
                                    <label for="edad" class="form-label fw-semibold">Edad</label>
                                    <input type="number" class="form-control fw-bold text-info" id="edad" name="edad" min="0" onkeydown="evitarNegativos(event)" oninput="validarPositivo(this)" value="<?php echo htmlspecialchars($persona['edad'] ?? ''); ?>" placeholder="Edad" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="lugar_nacimiento" class="form-label fw-semibold">Lugar de Nacimiento</label>
                                    <input type="text" class="form-control" id="lugar_nacimiento" name="lugar_nacimiento" value="<?php echo htmlspecialchars($persona['lugar_nacimiento'] ?? ''); ?>" placeholder="Ciudad / Estado">
                                </div>
                            </div>

                            <hr class="my-4" style="border-color: var(--card-border);">

                            <h6 class="fw-bold mb-3 text-uppercase fs-7 text-primary tracking-wide"><i class="bi bi-building me-1"></i> Asignación Institucional y Militar</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="unidad_brigada" class="form-label fw-semibold">Unidad / Brigada</label>
                                    <select class="form-select" id="unidad_brigada" name="unidad_brigada">
                                        <option value="LABORATORIO CRIMINALISTICO NRO. 11" selected>LABORATORIO CRIMINALISTICO NRO. 11</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="destacamento_batallon" class="form-label fw-semibold">Destacamento / Batallón</label>
                                    <select class="form-select" id="destacamento_batallon" name="destacamento_batallon">
                                        <option value="SLCCT" selected>SLCCT</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="componente" class="form-label fw-semibold">Componente</label>
                                    <select class="form-select" id="componente" name="componente">
                                        <?php 
                                        $componentes = ['GNB', 'EB', 'ARB', 'AMB', 'MB'];
                                        foreach($componentes as $comp) {
                                            $sel = (($persona['componente'] ?? 'GNB') === $comp) ? 'selected' : '';
                                            echo "<option value='$comp' $sel>$comp</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="grado" class="form-label fw-semibold">Grado / Jerarquía</label>
                                    <select class="form-select" id="grado" name="grado" required>
                                        <option value="" disabled <?php echo empty($persona['grado']) ? 'selected' : ''; ?>>Seleccione el Grado</option>
                                        <optgroup label="Oficiales Generales">
                                            <option value="General en Jefe" <?php echo (($persona['grado'] ?? '') === 'General en Jefe') ? 'selected' : ''; ?>>General en Jefe</option>
                                            <option value="Mayor General" <?php echo (($persona['grado'] ?? '') === 'Mayor General') ? 'selected' : ''; ?>>Mayor General</option>
                                            <option value="General de Division" <?php echo (($persona['grado'] ?? '') === 'General de Division') ? 'selected' : ''; ?>>General de División</option>
                                            <option value="General de Brigada" <?php echo (($persona['grado'] ?? '') === 'General de Brigada') ? 'selected' : ''; ?>>General de Brigada</option>
                                        </optgroup>
                                        <optgroup label="Oficiales Superiores">
                                            <option value="Coronel" <?php echo (($persona['grado'] ?? '') === 'Coronel') ? 'selected' : ''; ?>>Coronel</option>
                                            <option value="Teniente Coronel" <?php echo (($persona['grado'] ?? '') === 'Teniente Coronel') ? 'selected' : ''; ?>>Teniente Coronel</option>
                                            <option value="Mayor" <?php echo (($persona['grado'] ?? '') === 'Mayor') ? 'selected' : ''; ?>>Mayor</option>
                                        </optgroup>
                                        <optgroup label="Oficiales Subalternos">
                                            <option value="Capitan" <?php echo (($persona['grado'] ?? '') === 'Capitan') ? 'selected' : ''; ?>>Capitán</option>
                                            <option value="Primer Teniente" <?php echo (($persona['grado'] ?? '') === 'Primer Teniente') ? 'selected' : ''; ?>>Primer Teniente</option>
                                            <option value="Teniente" <?php echo (($persona['grado'] ?? '') === 'Teniente') ? 'selected' : ''; ?>>Teniente</option>
                                        </optgroup>
                                        <optgroup label="Tropa Profesional (Sargentos)">
                                            <option value="Sargento Supervisor" <?php echo (($persona['grado'] ?? '') === 'Sargento Supervisor') ? 'selected' : ''; ?>>Sargento Supervisor</option>
                                            <option value="Sargento Ayudante" <?php echo (($persona['grado'] ?? '') === 'Sargento Ayudante') ? 'selected' : ''; ?>>Sargento Ayudante</option>
                                            <option value="Sargento Mayor de Primera" <?php echo (($persona['grado'] ?? '') === 'Sargento Mayor de Primera') ? 'selected' : ''; ?>>Sargento Mayor de Primera</option>
                                            <option value="Sargento Mayor de Segunda" <?php echo (($persona['grado'] ?? '') === 'Sargento Mayor de Segunda') ? 'selected' : ''; ?>>Sargento Mayor de Segunda</option>
                                            <option value="Sargento Mayor de Tercera" <?php echo (($persona['grado'] ?? '') === 'Sargento Mayor de Tercera') ? 'selected' : ''; ?>>Sargento Mayor de Tercera</option>
                                            <option value="Sargento Primero" <?php echo (($persona['grado'] ?? '') === 'Sargento Primero') ? 'selected' : ''; ?>>Sargento Primero</option>
                                            <option value="Sargento Segundo" <?php echo (($persona['grado'] ?? '') === 'Sargento Segundo') ? 'selected' : ''; ?>>Sargento Segundo</option>
                                        </optgroup>
                                        <optgroup label="Tropa Alistada">
                                            <option value="Distinguido" <?php echo (($persona['grado'] ?? '') === 'Distinguido') ? 'selected' : ''; ?>>Distinguido</option>
                                            <option value="Cabo Primero" <?php echo (($persona['grado'] ?? '') === 'Cabo Primero') ? 'selected' : ''; ?>>Cabo Primero</option>
                                            <option value="Cabo Segundo" <?php echo (($persona['grado'] ?? '') === 'Cabo Segundo') ? 'selected' : ''; ?>>Cabo Segundo</option>
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="especialidad" class="form-label fw-semibold">Especialidad</label>
                                    <input type="text" class="form-control" id="especialidad" name="especialidad" value="<?php echo htmlspecialchars($persona['especialidad'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="escuela_graduacion" class="form-label fw-semibold">Escuela de Graduación</label>
                                    <input type="text" class="form-control" id="escuela_graduacion" name="escuela_graduacion" value="<?php echo htmlspecialchars($persona['escuela_graduacion'] ?? ''); ?>">
                                </div>

                                <div class="col-md-4">
                                    <label for="promocion" class="form-label fw-semibold">Promoción</label>
                                    <input type="text" class="form-control" id="promocion" name="promocion" value="<?php echo htmlspecialchars($persona['promocion'] ?? ''); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="ano_graduacion" class="form-label fw-semibold">Año Graduación</label>
                                    <input type="number" class="form-control" id="ano_graduacion" name="ano_graduacion" min="1900" max="2100" onkeydown="evitarNegativos(event)" oninput="validarPositivo(this)" value="<?php echo htmlspecialchars($persona['ano_graduacion'] ?? ''); ?>">
                                </div>
                                <div class="col-md-5">
                                    <label for="categoria" class="form-label fw-semibold">Categoría</label>
                                    <input type="text" class="form-control" id="categoria" name="categoria" value="<?php echo htmlspecialchars($persona['categoria'] ?? ''); ?>">
                                </div>

                                <div class="col-md-4">
                                    <label for="fecha_ingreso" class="form-label fw-semibold">Fecha de Ingreso</label>
                                    <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" value="<?php echo htmlspecialchars($persona['fecha_ingreso'] ?? ''); ?>" onchange="calcularTiempoServicio()">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Tiempo de Servicio (Automático)</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="number" class="form-control fw-bold text-success" id="anos_servicio" name="anos_servicio" min="0" onkeydown="evitarNegativos(event)" oninput="validarPositivo(this)" value="<?php echo htmlspecialchars($persona['anos_servicio'] ?? ''); ?>" placeholder="Años" readonly>
                                        </div>
                                        <div class="col-6">
                                            <input type="number" class="form-control fw-bold text-success" id="meses_servicio" name="meses_servicio" min="0" max="11" onkeydown="evitarNegativos(event)" oninput="validarPositivo(this)" value="<?php echo htmlspecialchars($persona['meses_servicio'] ?? ''); ?>" placeholder="Meses" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="exposicion" class="form-label fw-semibold">Exposición / Observaciones</label>
                                    <textarea class="form-control" id="exposicion" name="exposicion" rows="2"><?php echo htmlspecialchars($persona['antecedentes_observaciones'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <hr class="my-4" style="border-color: var(--card-border);">

                            <h6 class="fw-bold mb-3 text-uppercase fs-7 text-primary tracking-wide"><i class="bi bi-person-gear me-1"></i> Tallas de Uniforme y Calzado</h6>
                            <div class="row g-3 mb-4">
                                <div class="col"><label class="form-label fw-semibold">Talla Zapato</label><input type="text" class="form-control" name="talla_zapato" value="<?php echo htmlspecialchars($persona['talla_zapato'] ?? ''); ?>"></div>
                                <div class="col"><label class="form-label fw-semibold">Talla Camisa</label><input type="text" class="form-control" name="talla_camisa" value="<?php echo htmlspecialchars($persona['talla_camisa'] ?? ''); ?>"></div>
                                <div class="col"><label class="form-label fw-semibold">Talla Pantalón</label><input type="text" class="form-control" name="talla_pantalon" value="<?php echo htmlspecialchars($persona['talla_pantalon'] ?? ''); ?>"></div>
                                <div class="col"><label class="form-label fw-semibold">Talla Gorra</label><input type="text" class="form-control" name="talla_gorra" value="<?php echo htmlspecialchars($persona['talla_gorra'] ?? ''); ?>"></div>
                                <div class="col"><label class="form-label fw-semibold">Talla Patriota</label><input type="text" class="form-control" name="talla_patriota" value="<?php echo htmlspecialchars($persona['talla_patriota'] ?? ''); ?>"></div>
                            </div>

                            <hr class="my-4" style="border-color: var(--card-border);">

                            <h6 class="fw-bold mb-3 text-uppercase fs-7 text-primary tracking-wide"><i class="bi bi-geo-alt me-1"></i> Contacto y Direcciones</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4"><label class="form-label fw-semibold">Teléfono Celular</label><input type="text" class="form-control" name="telefono_celular" value="<?php echo htmlspecialchars($persona['telefono_celular'] ?? ''); ?>" maxlength="12" oninput="formatearTelefono(this)"></div>
                                <div class="col-md-4"><label class="form-label fw-semibold">Teléfono Habitación</label><input type="text" class="form-control" name="telefono_habitacion" value="<?php echo htmlspecialchars($persona['telefono_habitacion'] ?? ''); ?>" maxlength="12" oninput="formatearTelefono(this)"></div>
                                <div class="col-md-4"><label class="form-label fw-semibold">Teléfono de Emergencia</label><input type="text" class="form-control" name="contacto_emergencia_telefono" value="<?php echo htmlspecialchars($persona['contacto_emergencia_telefono'] ?? ''); ?>" maxlength="12" oninput="formatearTelefono(this)"></div>
                                <div class="col-md-12"><label class="form-label fw-semibold">Correo Electrónico</label><input type="email" class="form-control" name="correo" value="<?php echo htmlspecialchars($persona['correo'] ?? ''); ?>"></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Dirección de Habitación</label><input type="text" class="form-control" name="direccion" value="<?php echo htmlspecialchars($persona['direccion'] ?? ''); ?>"></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Dirección de Emergencia</label><input type="text" class="form-control" name="direccion_emergencia" value="<?php echo htmlspecialchars($persona['direccion_emergencia'] ?? ''); ?>"></div>
                            </div>

                            <hr class="my-4" style="border-color: var(--card-border);">

                            <h6 class="fw-bold mb-3 text-uppercase fs-7 text-primary tracking-wide"><i class="bi bi-camera-fill me-1"></i> Registro Fotográfico y Documental</h6>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-3 text-center p-3 box-section-custom">
                                    <label class="form-label fw-semibold small">Carnet (Frente)</label>
                                    <label for="foto_carnet_frente" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-pill">
                                        <i class="bi bi-upload me-1"></i> Seleccionar
                                    </label>
                                    <input type="file" id="foto_carnet_frente" name="foto_carnet_frente" accept="image/*" style="display: none;" onchange="previewImagen(this, 'imgPreviewCarnetF', 'containerCarnetF')">
                                    <div id="containerCarnetF" class="preview-container mx-auto" style="<?php echo !empty($fotos_data['foto_carnet_frente']) ? 'display:block;' : 'display:none;'; ?>">
                                        <img id="imgPreviewCarnetF" class="preview-img" src="<?php echo htmlspecialchars($fotos_data['foto_carnet_frente'] ?? ''); ?>" alt="Carnet Frente">
                                        <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('foto_carnet_frente', 'containerCarnetF')"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center p-3 box-section-custom">
                                    <label class="form-label fw-semibold small">Carnet (Reverso)</label>
                                    <label for="foto_carnet_reverso" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-pill">
                                        <i class="bi bi-upload me-1"></i> Seleccionar
                                    </label>
                                    <input type="file" id="foto_carnet_reverso" name="foto_carnet_reverso" accept="image/*" style="display: none;" onchange="previewImagen(this, 'imgPreviewCarnetR', 'containerCarnetR')">
                                    <div id="containerCarnetR" class="preview-container mx-auto" style="<?php echo !empty($fotos_data['foto_carnet_reverso']) ? 'display:block;' : 'display:none;'; ?>">
                                        <img id="imgPreviewCarnetR" class="preview-img" src="<?php echo htmlspecialchars($fotos_data['foto_carnet_reverso'] ?? ''); ?>" alt="Carnet Reverso">
                                        <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('foto_carnet_reverso', 'containerCarnetR')"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center p-3 box-section-custom">
                                    <label class="form-label fw-semibold small">Partida Nac. (Frente)</label>
                                    <label for="foto_partida_frente" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-pill">
                                        <i class="bi bi-upload me-1"></i> Seleccionar
                                    </label>
                                    <input type="file" id="foto_partida_frente" name="foto_partida_frente" accept="image/*,.pdf" style="display: none;" onchange="previewImagen(this, 'imgPreviewPartidaF', 'containerPartidaF')">
                                    <div id="containerPartidaF" class="preview-container mx-auto" style="<?php echo !empty($fotos_data['foto_partida_frente']) ? 'display:block;' : 'display:none;'; ?>">
                                        <img id="imgPreviewPartidaF" class="preview-img" src="<?php echo htmlspecialchars($fotos_data['foto_partida_frente'] ?? ''); ?>" alt="Partida Frente">
                                        <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('foto_partida_frente', 'containerPartidaF')"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center p-3 box-section-custom">
                                    <label class="form-label fw-semibold small">Partida Nac. (Reverso)</label>
                                    <label for="foto_partida_reverso" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-pill">
                                        <i class="bi bi-upload me-1"></i> Seleccionar
                                    </label>
                                    <input type="file" id="foto_partida_reverso" name="foto_partida_reverso" accept="image/*,.pdf" style="display: none;" onchange="previewImagen(this, 'imgPreviewPartidaR', 'containerPartidaR')">
                                    <div id="containerPartidaR" class="preview-container mx-auto" style="<?php echo !empty($fotos_data['foto_partida_reverso']) ? 'display:block;' : 'display:none;'; ?>">
                                        <img id="imgPreviewPartidaR" class="preview-img" src="<?php echo htmlspecialchars($fotos_data['foto_partida_reverso'] ?? ''); ?>" alt="Partida Reverso">
                                        <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('foto_partida_reverso', 'containerPartidaR')"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 box-section-custom">
                                <label class="form-label fw-bold small d-block mb-3">Fotografías Oficiales del Personal</label>
                                <div class="row g-3">
                                    <div class="col-md-3 text-center">
                                        <label class="form-label small fw-semibold text-primary">Foto Gala</label>
                                        <label for="foto_gala" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-pill">
                                            <i class="bi bi-upload me-1"></i> Seleccionar
                                        </label>
                                        <input type="file" id="foto_gala" name="foto_gala" accept="image/*" style="display: none;" onchange="previewImagen(this, 'imgPreviewPGala', 'containerPGala')">
                                        <div id="containerPGala" class="preview-container mx-auto" style="<?php echo !empty($fotos_data['foto_gala']) ? 'display:block;' : 'display:none;'; ?>">
                                            <img id="imgPreviewPGala" class="preview-img" src="<?php echo htmlspecialchars($fotos_data['foto_gala'] ?? ''); ?>" alt="Foto Gala">
                                            <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('foto_gala', 'containerPGala')"><i class="bi bi-x-lg"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <label class="form-label small fw-semibold text-primary">Foto Patriota Tiuna</label>
                                        <label for="foto_patriota_tiuna" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-pill">
                                            <i class="bi bi-upload me-1"></i> Seleccionar
                                        </label>
                                        <input type="file" id="foto_patriota_tiuna" name="foto_patriota_tiuna" accept="image/*" style="display: none;" onchange="previewImagen(this, 'imgPreviewPTiuna', 'containerPTiuna')">
                                        <div id="containerPTiuna" class="preview-container mx-auto" style="<?php echo !empty($fotos_data['foto_patriota_tiuna']) ? 'display:block;' : 'display:none;'; ?>">
                                            <img id="imgPreviewPTiuna" class="preview-img" src="<?php echo htmlspecialchars($fotos_data['foto_patriota_tiuna'] ?? ''); ?>" alt="Foto Patriota Tiuna">
                                            <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('foto_patriota_tiuna', 'containerPTiuna')"><i class="bi bi-x-lg"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <label class="form-label small fw-semibold text-primary">Foto Interior de Cuartel</label>
                                        <label for="foto_interior_cuartel" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-pill">
                                            <i class="bi bi-upload me-1"></i> Seleccionar
                                        </label>
                                        <input type="file" id="foto_interior_cuartel" name="foto_interior_cuartel" accept="image/*" style="display: none;" onchange="previewImagen(this, 'imgPreviewPCuartel', 'containerPCuartel')">
                                        <div id="containerPCuartel" class="preview-container mx-auto" style="<?php echo !empty($fotos_data['foto_interior_cuartel']) ? 'display:block;' : 'display:none;'; ?>">
                                            <img id="imgPreviewPCuartel" class="preview-img" src="<?php echo htmlspecialchars($fotos_data['foto_interior_cuartel'] ?? ''); ?>" alt="Foto Interior Cuartel">
                                            <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('foto_interior_cuartel', 'containerPCuartel')"><i class="bi bi-x-lg"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <label class="form-label small fw-semibold text-primary">Foto Civil</label>
                                        <label for="foto_civil" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-pill">
                                            <i class="bi bi-upload me-1"></i> Seleccionar
                                        </label>
                                        <input type="file" id="foto_civil" name="foto_civil" accept="image/*" style="display: none;" onchange="previewImagen(this, 'imgPreviewPCivil', 'containerPCivil')">
                                        <div id="containerPCivil" class="preview-container mx-auto" style="<?php echo !empty($fotos_data['foto_civil']) ? 'display:block;' : 'display:none;'; ?>">
                                            <img id="imgPreviewPCivil" class="preview-img" src="<?php echo htmlspecialchars($fotos_data['foto_civil'] ?? ''); ?>" alt="Foto Civil">
                                            <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('foto_civil', 'containerPCivil')"><i class="bi bi-x-lg"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4 pt-3 border-top" style="border-color: var(--card-border) !important;">
                                <button type="button" class="btn btn-primary px-5 py-2.5 fw-bold rounded-pill shadow-sm" onclick="cambiarSeccion(2)" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border:none;">
                                    Siguiente (Títulos y Cursos) <i class="bi bi-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>

                        <!-- SECCIÓN 2 -->
                        <div id="seccion-2" class="seccion-formulario">
                            <h4 class="fw-bold text-primary mb-4 pb-2 border-bottom d-flex align-items-center" style="border-color: var(--card-border) !important;">
                                <i class="bi bi-award fs-3 me-2"></i> 2. Títulos, Cursos y Especializaciones
                            </h4>

                            <div class="row g-3 mb-4">
                                <div class="col-12 p-4 box-section-custom">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="form-label fw-bold fs-6 mb-0">
                                            Registro de Formación Académica con Soporte Individual
                                        </label>
                                        <div style="width: 150px;">
                                            <input type="number" class="form-control form-control-sm fw-bold text-primary text-center" id="num_cursos" name="num_cursos" min="0" max="10" value="<?php echo count($cursos_dec); ?>" onkeydown="evitarNegativos(event)" oninput="validarPositivo(this); generarCamposCursos();">
                                        </div>
                                    </div>
                                    <small class="d-block mb-3 text-muted">Indique la cantidad de registros para generar los campos correspondientes.</small>
                                    
                                    <div id="contenedor-cursos" class="col-12"></div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4 pt-3 border-top" style="border-color: var(--card-border) !important;">
                                <button type="button" class="btn btn-outline-secondary px-4 py-2.5 fw-bold rounded-pill" onclick="cambiarSeccion(1)">
                                    <i class="bi bi-arrow-left me-2"></i> Anterior
                                </button>
                                <button type="button" class="btn btn-primary px-5 py-2.5 fw-bold rounded-pill shadow-sm" onclick="cambiarSeccion(3)" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border:none;">
                                    Siguiente (Datos Familiares) <i class="bi bi-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>

                        <!-- SECCIÓN 3 -->
                        <div id="seccion-3" class="seccion-formulario">
                            <h4 class="fw-bold text-primary mb-4 pb-2 border-bottom d-flex align-items-center" style="border-color: var(--card-border) !important;">
                                <i class="bi bi-people-fill fs-3 me-2"></i> 3. Datos Familiares
                            </h4>

                            <div class="row g-3 mb-4 p-4 box-section-custom">
                                <div class="col-md-5">
                                    <label for="esta_casado" class="form-label fw-bold">¿Está Casado(a) o en Concubinato?</label>
                                    <select class="form-select fw-semibold" id="esta_casado" name="esta_casado" onchange="toggleSeccionConyuge()">
                                        <option value="No" <?php echo empty($fam_data['nombre_conyuge']) ? 'selected' : ''; ?>>No</option>
                                        <option value="Sí" <?php echo !empty($fam_data['nombre_conyuge']) ? 'selected' : ''; ?>>Sí</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4 p-4 box-section-custom" id="caja_conyuge" style="<?php echo !empty($fam_data['nombre_conyuge']) ? 'display:flex;' : 'display:none;'; ?>">
                                <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-heart-fill text-danger me-2"></i> Datos del Cónyuge</h5>
                                <div class="col-md-5">
                                    <label for="nombre_conyuge" class="form-label fw-semibold">Nombre y Apellido de la Esposa (o)</label>
                                    <input type="text" class="form-control" id="nombre_conyuge" name="nombre_conyuge" value="<?php echo htmlspecialchars($fam_data['nombre_conyuge'] ?? ''); ?>" oninput="soloLetras(this)">
                                </div>
                                <div class="col-md-3">
                                    <label for="cedula_conyuge" class="form-label fw-semibold">C.I. Cónyuge</label>
                                    <div class="input-group">
                                        <select class="form-select px-2" id="tipo_cedula_conyuge" name="tipo_cedula_conyuge" style="max-width: 75px;">
                                            <option value="V" <?php echo ($tipo_cedula_conyuge_bd === 'V') ? 'selected' : ''; ?>>V</option>
                                            <option value="E" <?php echo ($tipo_cedula_conyuge_bd === 'E') ? 'selected' : ''; ?>>E</option>
                                            <option value="P" <?php echo ($tipo_cedula_conyuge_bd === 'P') ? 'selected' : ''; ?>>P</option>
                                        </select>
                                        <input type="text" class="form-control" id="cedula_conyuge" name="cedula_conyuge" value="<?php echo htmlspecialchars($numero_cedula_conyuge_bd); ?>" oninput="validarCedula(this)">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="telefono_conyuge" class="form-label fw-semibold">Teléfono Cónyuge</label>
                                    <input type="text" class="form-control" id="telefono_conyuge" name="telefono_conyuge" value="<?php echo htmlspecialchars($fam_data['telefono_conyuge'] ?? ''); ?>" maxlength="12" oninput="formatearTelefono(this)">
                                </div>
                                <div class="col-md-4 mt-3">
                                    <label class="form-label fw-semibold small">Foto Cédula Cónyuge</label>
                                    <label for="foto_cedula_conyuge" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-pill">
                                        <i class="bi bi-upload me-1"></i> Adjuntar Cédula
                                    </label>
                                    <input type="file" id="foto_cedula_conyuge" name="foto_cedula_conyuge" accept="image/*,.pdf" style="display: none;" onchange="previewImagen(this, 'imgConyuge', 'contConyuge')">
                                    <div id="contConyuge" class="preview-container mx-auto" style="<?php echo !empty($fam_data['foto_cedula_conyuge']) ? 'display:block;' : 'display:none;'; ?>">
                                        <img id="imgConyuge" class="preview-img" src="<?php echo htmlspecialchars($fam_data['foto_cedula_conyuge'] ?? ''); ?>" alt="Cédula Cónyuge">
                                        <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('foto_cedula_conyuge', 'contConyuge')"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-4 p-4 box-section-custom">
                                <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-people me-2"></i> Hijos</h5>
                                <div class="col-md-4 mb-3">
                                    <label for="num_hijos" class="form-label fw-semibold">Número de Hijos</label>
                                    <input type="number" class="form-control fw-bold text-primary" id="num_hijos" name="num_hijos" min="0" max="10" value="<?php echo count($hijos_dec); ?>" onkeydown="evitarNegativos(event)" oninput="validarPositivo(this); generarCamposHijos();">
                                </div>
                                <div id="contenedor-hijos" class="col-12"></div>
                            </div>

                            <div class="row g-3 mb-4 p-4 box-section-custom">
                                <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-person-fill me-2"></i> Datos del Padre</h5>
                                <div class="col-md-5">
                                    <label for="nombre_padre" class="form-label fw-semibold">Nombre y Apellido del Padre</label>
                                    <input type="text" class="form-control" id="nombre_padre" name="nombre_padre" value="<?php echo htmlspecialchars($fam_data['nombre_padre'] ?? ''); ?>" oninput="soloLetras(this)">
                                </div>
                                <div class="col-md-3">
                                    <label for="cedula_padre" class="form-label fw-semibold">C.I. Padre</label>
                                    <div class="input-group">
                                        <select class="form-select px-2" id="tipo_cedula_padre" name="tipo_cedula_padre" style="max-width: 75px;">
                                            <option value="V" <?php echo ($tipo_cedula_padre_bd === 'V') ? 'selected' : ''; ?>>V</option>
                                            <option value="E" <?php echo ($tipo_cedula_padre_bd === 'E') ? 'selected' : ''; ?>>E</option>
                                            <option value="P" <?php echo ($tipo_cedula_padre_bd === 'P') ? 'selected' : ''; ?>>P</option>
                                        </select>
                                        <input type="text" class="form-control" id="cedula_padre" name="cedula_padre" value="<?php echo htmlspecialchars($numero_cedula_padre_bd); ?>" oninput="validarCedula(this)">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label for="padre_vive" class="form-label fw-semibold">¿Vive?</label>
                                    <select class="form-select" id="padre_vive" name="padre_vive">
                                        <option value="Sí" <?php echo (($fam_data['padre_vive'] ?? '') === 'Sí') ? 'selected' : ''; ?>>Sí</option>
                                        <option value="No" <?php echo (($fam_data['padre_vive'] ?? '') === 'No') ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </div>
                                <div class="col-md-2 text-center">
                                    <label class="form-label fw-semibold small d-block">Cédula Padre</label>
                                    <label for="foto_cedula_padre" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-pill">
                                        <i class="bi bi-upload"></i> Adjuntar
                                    </label>
                                    <input type="file" id="foto_cedula_padre" name="foto_cedula_padre" accept="image/*,.pdf" style="display: none;" onchange="previewImagen(this, 'imgPadre', 'contPadre')">
                                    <div id="contPadre" class="preview-container mx-auto" style="<?php echo !empty($fam_data['foto_cedula_padre']) ? 'display:block;' : 'display:none;'; ?>">
                                        <img id="imgPadre" class="preview-img" src="<?php echo htmlspecialchars($fam_data['foto_cedula_padre'] ?? ''); ?>" alt="Cédula Padre">
                                        <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('foto_cedula_padre', 'contPadre')"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="telefono_padre" class="form-label fw-semibold">Teléfono Padre</label>
                                    <input type="text" class="form-control" id="telefono_padre" name="telefono_padre" value="<?php echo htmlspecialchars($fam_data['telefono_padre'] ?? ''); ?>" maxlength="12" oninput="formatearTelefono(this)">
                                </div>
                                <div class="col-md-8">
                                    <label for="direccion_padre" class="form-label fw-semibold">Dirección Padre</label>
                                    <input type="text" class="form-control" id="direccion_padre" name="direccion_padre" value="<?php echo htmlspecialchars($fam_data['direccion_padre'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row g-3 mb-4 p-4 box-section-custom">
                                <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-person-fill me-2"></i> Datos de la Madre</h5>
                                <div class="col-md-5">
                                    <label for="nombre_madre" class="form-label fw-semibold">Nombre y Apellido de la Madre</label>
                                    <input type="text" class="form-control" id="nombre_madre" name="nombre_madre" value="<?php echo htmlspecialchars($fam_data['nombre_madre'] ?? ''); ?>" oninput="soloLetras(this)">
                                </div>
                                <div class="col-md-3">
                                    <label for="cedula_madre" class="form-label fw-semibold">C.I. Madre</label>
                                    <div class="input-group">
                                        <select class="form-select px-2" id="tipo_cedula_madre" name="tipo_cedula_madre" style="max-width: 75px;">
                                            <option value="V" <?php echo ($tipo_cedula_madre_bd === 'V') ? 'selected' : ''; ?>>V</option>
                                            <option value="E" <?php echo ($tipo_cedula_madre_bd === 'E') ? 'selected' : ''; ?>>E</option>
                                            <option value="P" <?php echo ($tipo_cedula_madre_bd === 'P') ? 'selected' : ''; ?>>P</option>
                                        </select>
                                        <input type="text" class="form-control" id="cedula_madre" name="cedula_madre" value="<?php echo htmlspecialchars($numero_cedula_madre_bd); ?>" oninput="validarCedula(this)">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label for="madre_vive" class="form-label fw-semibold">¿Vive?</label>
                                    <select class="form-select" id="madre_vive" name="madre_vive">
                                        <option value="Sí" <?php echo (($fam_data['madre_vive'] ?? '') === 'Sí') ? 'selected' : ''; ?>>Sí</option>
                                        <option value="No" <?php echo (($fam_data['madre_vive'] ?? '') === 'No') ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </div>
                                <div class="col-md-2 text-center">
                                    <label class="form-label fw-semibold small d-block">Cédula Madre</label>
                                    <label for="foto_cedula_madre" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-pill">
                                        <i class="bi bi-upload"></i> Adjuntar
                                    </label>
                                    <input type="file" id="foto_cedula_madre" name="foto_cedula_madre" accept="image/*,.pdf" style="display: none;" onchange="previewImagen(this, 'imgMadre', 'contMadre')">
                                    <div id="contMadre" class="preview-container mx-auto" style="<?php echo !empty($fam_data['foto_cedula_madre']) ? 'display:block;' : 'display:none;'; ?>">
                                        <img id="imgMadre" class="preview-img" src="<?php echo htmlspecialchars($fam_data['foto_cedula_madre'] ?? ''); ?>" alt="Cédula Madre">
                                        <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('foto_cedula_madre', 'contMadre')"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="telefono_madre" class="form-label fw-semibold">Teléfono Madre</label>
                                    <input type="text" class="form-control" id="telefono_madre" name="telefono_madre" value="<?php echo htmlspecialchars($fam_data['telefono_madre'] ?? ''); ?>" maxlength="12" oninput="formatearTelefono(this)">
                                </div>
                                <div class="col-md-8">
                                    <label for="direccion_madre" class="form-label fw-semibold">Dirección Madre</label>
                                    <input type="text" class="form-control" id="direccion_madre" name="direccion_madre" value="<?php echo htmlspecialchars($fam_data['direccion_madre'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4 pt-3 border-top" style="border-color: var(--card-border) !important;">
                                <button type="button" class="btn btn-outline-secondary px-4 py-2.5 fw-bold rounded-pill" onclick="cambiarSeccion(2)">
                                    <i class="bi bi-arrow-left me-2"></i> Anterior
                                </button>
                                <button type="button" class="btn btn-primary px-5 py-2.5 fw-bold rounded-pill shadow-sm" onclick="cambiarSeccion(4)" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border:none;">
                                    Siguiente (Antecedentes Médicos) <i class="bi bi-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>

                        <!-- SECCIÓN 4 -->
                        <div id="seccion-4" class="seccion-formulario">
                            <h4 class="fw-bold text-primary mb-4 pb-2 border-bottom d-flex align-items-center" style="border-color: var(--card-border) !important;">
                                <i class="bi bi-heart-pulse fs-3 me-2"></i> 4. Antecedentes Médicos
                            </h4>

                            <p class="small mb-3 text-muted">Marque las afecciones o patologías que presente el funcionario:</p>

                            <div class="row g-3 mb-4">
                                <?php 
                                $antecedentes = [
                                    'hipertenso' => 'Hipertenso / Tensión Alta', 'cardiologicos' => 'Antecedentes Cardiológicos',
                                    'renales' => 'Afecciones Renales', 'testiculares' => 'Afecciones Testiculares',
                                    'pelvicos' => 'Afecciones Pélvicas', 'infeccion_respiratoria' => 'Infecciones Respiratorias',
                                    'sinusitis' => 'Sinusitis Cronica', 'bronquitis' => 'Bronquitis',
                                    'neumonia' => 'Neumonía', 'asma' => 'Asma Bronquial'
                                ];
                                foreach ($antecedentes as $campo => $etiqueta): 
                                    $val_act = $medico_data[$campo] ?? 'No';
                                ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="antecedente-box d-flex justify-content-between align-items-center">
                                            <span class="fw-semibold small" style="color: var(--text-main);"><?php echo $etiqueta; ?></span>
                                            <select class="form-select form-select-sm ms-2" name="<?php echo $campo; ?>" style="width: 90px;">
                                                <option value="No" <?php echo ($val_act === 'No') ? 'selected' : ''; ?>>No</option>
                                                <option value="Sí" <?php echo ($val_act === 'Sí') ? 'selected' : ''; ?>>Sí</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label for="tratamiento_asignado" class="form-label fw-semibold">Tratamiento Médico Asignado</label>
                                    <textarea class="form-control" id="tratamiento_asignado" name="tratamiento_asignado" rows="4"><?php echo htmlspecialchars($medico_data['tratamiento_asignado'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4 pt-3 border-top" style="border-color: var(--card-border) !important;">
                                <button type="button" class="btn btn-outline-secondary px-4 py-2.5 fw-bold rounded-pill" onclick="cambiarSeccion(3)">
                                    <i class="bi bi-arrow-left me-2"></i> Anterior
                                </button>
                                <button type="button" id="btnGuardar" onclick="validarYEnviar()" class="btn btn-success px-5 py-2.5 fw-bold rounded-pill shadow-lg" style="background: linear-gradient(135deg, #16a34a, #15803d); border:none;">
                                    <i class="bi bi-check-circle-fill me-2"></i> Guardar Cambios en Expediente
                                </button>
                            </div>
                        </div>

                    </form>
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
                <div class="modal-footer border-top-0 d-flex justify-content-center gap-2 py-3" style="background: var(--card-bg);">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-bold rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <a href="logout.php" class="btn btn-primary px-4 fw-bold rounded-pill shadow-sm" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border:none;">Sí, Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const hijosGuardados = <?php echo json_encode($hijos_dec); ?>;
        const cursosGuardados = <?php echo json_encode($cursos_dec); ?>;

        function validarCedula(input) {
            input.value = input.value.replace(/\D/g, '');
        }

        function soloLetras(input) {
            input.value = input.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, '');
        }

        function evitarNegativos(event) {
            if (event.key === '-' || event.key === 'e' || event.key === 'E') {
                event.preventDefault();
            }
        }

        function validarPositivo(input) {
            if (input.value !== '' && parseFloat(input.value) < 0) {
                input.value = 0;
            }
        }

        function toggleSeccionConyuge() {
            const estaCasado = document.getElementById('esta_casado').value;
            const cajaConyuge = document.getElementById('caja_conyuge');
            if (estaCasado === 'Sí') {
                cajaConyuge.style.display = 'flex';
            } else {
                cajaConyuge.style.display = 'none';
                document.getElementById('nombre_conyuge').value = '';
                document.getElementById('cedula_conyuge').value = '';
                document.getElementById('telefono_conyuge').value = '';
            }
        }

        function evaluarEstadoCivil() {
            const estado = document.getElementById('estado_civil').value;
            const selectCasado = document.getElementById('esta_casado');
            if (estado === 'Casado/a' || estado === 'Concubinato') {
                selectCasado.value = 'Sí';
            } else {
                selectCasado.value = 'No';
            }
            toggleSeccionConyuge();
        }

        const archivosTemporalesHijos = {};
        const archivosTemporalesCursos = {};

        function registrarArchivoTemporal(tipo, indice, inputElement, tipoDoc = 'general') {
            if (inputElement.files && inputElement.files[0]) {
                const lector = new FileReader();
                lector.onload = function(e) {
                    const datosObj = {
                        file: inputElement.files[0],
                        src: e.target.result,
                        type: inputElement.files[0].type,
                        tipoDoc: tipoDoc
                    };
                    if (tipo === 'hijo') archivosTemporalesHijos[indice] = datosObj;
                    if (tipo === 'curso') archivosTemporalesCursos[indice] = datosObj;
                }
                lector.readAsDataURL(inputElement.files[0]);
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            calcularTiempoServicio();
            if (document.getElementById('num_cursos').value > 0) {
                generarCamposCursosIniciales();
            }
            if (document.getElementById('num_hijos').value > 0) {
                generarCamposHijosIniciales();
            }
        });

        function calcularEdadPersonal() {
            const fechaNac = document.getElementById('fecha_nacimiento').value;
            if (fechaNac) {
                const hoy = new Date();
                const nacimiento = new Date(fechaNac);
                let edad = hoy.getFullYear() - nacimiento.getFullYear();
                const m = hoy.getMonth() - nacimiento.getMonth();
                if (m < 0 || (m === 0 && hoy.getDate() < nacimiento.getDate())) {
                    edad--;
                }
                document.getElementById('edad').value = edad >= 0 ? edad : 0;
            }
        }

        function calcularTiempoServicio() {
            const fechaIngVal = document.getElementById('fecha_ingreso').value;
            if (fechaIngVal) {
                const hoy = new Date();
                const partes = fechaIngVal.split('-');
                const ingreso = new Date(partes[0], partes[1] - 1, partes[2]);
                
                let anos = hoy.getFullYear() - ingreso.getFullYear();
                let meses = hoy.getMonth() - ingreso.getMonth();
                
                if (hoy.getDate() < ingreso.getDate()) {
                    meses--;
                }
                
                if (meses < 0) {
                    anos--;
                    meses += 12;
                }

                document.getElementById('anos_servicio').value = anos >= 0 ? anos : 0;
                document.getElementById('meses_servicio').value = meses >= 0 ? meses : 0;
            }
        }

        function formatearTelefono(input) {
            let valor = input.value.replace(/\D/g, '');
            if (valor.length > 11) valor = valor.substring(0, 11);
            if (valor.length > 4) {
                input.value = valor.substring(0, 4) + '-' + valor.substring(4);
            } else {
                input.value = valor;
            }
        }

        function cambiarSeccion(paso) {
            document.querySelectorAll('.seccion-formulario').forEach(sec => sec.classList.remove('activa'));
            document.getElementById('seccion-' + paso).classList.add('activa');
            
            for (let i = 1; i <= 4; i++) {
                const tab = document.getElementById('step-tab-' + i);
                tab.classList.remove('active', 'completed');
                if (i < paso) {
                    tab.classList.add('completed');
                } else if (i === paso) {
                    tab.classList.add('active');
                }
            }
            window.scrollTo({ top: 100, behavior: 'smooth' });
        }

        function mostrarAlerta(tipo, mensaje) {
            const alertaBox = document.getElementById('alertaRespuesta');
            const alertaIcono = document.getElementById('alertaIcono');
            const alertaTexto = document.getElementById('alertaTexto');

            const claseModo = tipo === 'success' ? 'alert-custom-success' : 'alert-custom-danger';
            alertaBox.className = `toast align-items-center border-0 shadow-lg show ${claseModo}`;
            
            alertaIcono.className = tipo === 'success' ? 'bi bi-info-circle-fill me-2' : 'bi bi-exclamation-triangle-fill me-2';
            alertaTexto.textContent = mensaje;
            
            alertaBox.classList.remove('d-none');
        }

        function ocultarAlerta() {
            document.getElementById('alertaRespuesta').classList.add('d-none');
        }

        function limpiarMarcasError() {
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        }

        function validarYEnviar() {
            limpiarMarcasError();
            ocultarAlerta();

            const form = document.getElementById('formPersonal');
            const btnGuardar = document.getElementById('btnGuardar');
            const formData = new FormData(form);

            for (let idx in archivosTemporalesHijos) {
                let item = archivosTemporalesHijos[idx];
                if (item && item.file) {
                    let inputCed = document.getElementById(`hijo_${idx}_cedula`);
                    let inputPF = document.getElementById(`hijo_${idx}_partida_frente`);
                    let inputPR = document.getElementById(`hijo_${idx}_partida_reverso`);
                    
                    if (inputCed && inputCed.files.length === 0 && item.tipoDoc === 'cedula') formData.append(`hijo_${idx}_cedula`, item.file);
                    if (inputPF && inputPF.files.length === 0 && item.tipoDoc === 'partida') formData.append(`hijo_${idx}_partida_frente`, item.file);
                    if (inputPR && inputPR.files.length === 0 && item.tipoDoc === 'reverso') formData.append(`hijo_${idx}_partida_reverso`, item.file);
                }
            }

            for (let idx in archivosTemporalesCursos) {
                let item = archivosTemporalesCursos[idx];
                let inputCur = document.getElementById(`curso_${idx}_soporte`);
                if (item && item.file && inputCur && inputCur.files.length === 0) {
                    formData.append(`curso_${idx}_soporte`, item.file);
                }
            }

            btnGuardar.disabled = true;
            btnGuardar.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Guardando...`;

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i> Guardar Cambios en Expediente`;

                if (data.status === 'success') {
                    mostrarAlerta('success', data.mensaje);
                    setTimeout(() => { window.location.href = 'consulta_personal.php'; }, 2000);
                } else {
                    mostrarAlerta('danger', data.mensaje);
                    if (data.campos_invalidos && data.campos_invalidos.length > 0) {
                        data.campos_invalidos.forEach(campoId => {
                            const campoElem = document.getElementById(campoId);
                            if (campoElem) campoElem.classList.add('is-invalid');
                        });
                        cambiarSeccion(1);
                    }
                }
            })
            .catch(err => {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i> Guardar Cambios en Expediente`;
                mostrarAlerta('danger', 'Ocurrió un error inesperado al procesar la solicitud.');
            });
        }

        function generarCamposHijosIniciales() {
            const num = parseInt(document.getElementById('num_hijos').value) || 0;
            const cont = document.getElementById('contenedor-hijos');
            cont.innerHTML = '';
            for (let i = 1; i <= num; i++) {
                let h = hijosGuardados[i - 1] || { nombres: '', apellidos: '', sexo: 'M', fecha_nac: '', edad: '', cedula_doc: '', partida_frente: '', partida_reverso: '' };
                cont.innerHTML += `
                    <div class="card p-3 mb-3 border box-section-custom" id="hijo_card_${i}">
                        <h6 class="fw-bold text-primary small mb-3">Hijo (a) #${i}</h6>
                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Nombres</label>
                                <input type="text" class="form-control form-control-sm" name="hijo_${i}_nombres" value="${h.nombres || ''}" placeholder="Nombres" oninput="soloLetras(this)" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Apellidos</label>
                                <input type="text" class="form-control form-control-sm" name="hijo_${i}_apellidos" value="${h.apellidos || ''}" placeholder="Apellidos" oninput="soloLetras(this)" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Sexo</label>
                                <select class="form-select form-select-sm" name="hijo_${i}_sexo">
                                    <option value="M" ${(h.sexo || 'M') === 'M' ? 'selected' : ''}>M</option>
                                    <option value="F" ${(h.sexo || 'M') === 'F' ? 'selected' : ''}>F</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">F. Nac.</label>
                                <input type="date" class="form-control form-control-sm" name="hijo_${i}_fecha_nac" id="hijo_${i}_fecha_nac" value="${h.fecha_nac || ''}" onchange="calcularEdadHijo(${i})">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Edad</label>
                                <input type="number" class="form-control form-control-sm fw-bold text-info" name="hijo_${i}_edad" id="hijo_${i}_edad" min="0" onkeydown="evitarNegativos(event)" oninput="validarPositivo(this)" value="${h.edad || ''}" placeholder="Edad" readonly>
                            </div>
                        </div>
                        <div class="row g-2 mt-2" id="doc_hijo_${i}"></div>
                    </div>
                `;
                if(h.fecha_nac) {
                    setTimeout(() => calcularEdadHijo(i, h), 50);
                }
            }
        }

        function generarCamposHijos() {
            const num = parseInt(document.getElementById('num_hijos').value) || 0;
            const cont = document.getElementById('contenedor-hijos');
            let datosHijos = [];
            
            cont.querySelectorAll('.card').forEach((card, idx) => {
                let i = idx + 1;
                let nom = card.querySelector(`[name="hijo_${i}_nombres"]`);
                let ape = card.querySelector(`[name="hijo_${i}_apellidos"]`);
                let sex = card.querySelector(`[name="hijo_${i}_sexo"]`);
                let fec = card.querySelector(`[name="hijo_${i}_fecha_nac"]`);
                let ed  = card.querySelector(`[name="hijo_${i}_edad"]`);
                datosHijos.push({
                    nombres: nom ? nom.value : '', 
                    apellidos: ape ? ape.value : '', 
                    sexo: sex ? sex.value : 'M',
                    fecha_nac: fec ? fec.value : '', 
                    edad: ed ? ed.value : ''
                });
            });

            cont.innerHTML = '';
            for (let i = 1; i <= num; i++) {
                let h = datosHijos[i - 1] || { nombres: '', apellidos: '', sexo: 'M', fecha_nac: '', edad: '' };
                let hBase = hijosGuardados[i - 1] || {};
                
                cont.innerHTML += `
                    <div class="card p-3 mb-3 border box-section-custom" id="hijo_card_${i}">
                        <h6 class="fw-bold text-primary small mb-3">Hijo (a) #${i}</h6>
                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Nombres</label>
                                <input type="text" class="form-control form-control-sm" name="hijo_${i}_nombres" value="${h.nombres}" placeholder="Nombres" oninput="soloLetras(this)" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Apellidos</label>
                                <input type="text" class="form-control form-control-sm" name="hijo_${i}_apellidos" value="${h.apellidos}" placeholder="Apellidos" oninput="soloLetras(this)" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Sexo</label>
                                <select class="form-select form-select-sm" name="hijo_${i}_sexo">
                                    <option value="M" ${h.sexo === 'M' ? 'selected' : ''}>M</option>
                                    <option value="F" ${h.sexo === 'F' ? 'selected' : ''}>F</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">F. Nac.</label>
                                <input type="date" class="form-control form-control-sm" name="hijo_${i}_fecha_nac" id="hijo_${i}_fecha_nac" value="${h.fecha_nac}" onchange="calcularEdadHijo(${i})">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold">Edad</label>
                                <input type="number" class="form-control form-control-sm fw-bold text-info" name="hijo_${i}_edad" id="hijo_${i}_edad" min="0" onkeydown="evitarNegativos(event)" oninput="validarPositivo(this)" value="${h.edad}" placeholder="Edad" readonly>
                            </div>
                        </div>
                        <div class="row g-2 mt-2" id="doc_hijo_${i}"></div>
                    </div>
                `;
                
                if(h.fecha_nac) {
                    setTimeout(() => {
                        let edadVal = parseInt(document.getElementById(`hijo_${i}_edad`).value) || 0;
                        calcularEdadHijo(i, {
                            cedula_doc: hBase.cedula_doc || '',
                            partida_frente: hBase.partida_frente || '',
                            partida_reverso: hBase.partida_reverso || ''
                        });
                    }, 50);
                }
            }

            setTimeout(() => {
                for (let idx in archivosTemporalesHijos) {
                    if (parseInt(idx) <= num) {
                        let item = archivosTemporalesHijos[idx];
                        if (item) {
                            if (item.tipoDoc === 'cedula') {
                                let imgCed = document.getElementById(`imgHijoCed_${idx}`);
                                let contCed = document.getElementById(`contHijoCed_${idx}`);
                                if (imgCed && contCed) { imgCed.src = item.src; contCed.style.display = 'block'; }
                            } else if (item.tipoDoc === 'partida') {
                                let imgPF = document.getElementById(`imgHijoPF_${idx}`);
                                let contPF = document.getElementById(`contHijoPF_${idx}`);
                                if (imgPF && contPF) { imgPF.src = item.src; contPF.style.display = 'block'; }
                            } else if (item.tipoDoc === 'reverso') {
                                let imgPR = document.getElementById(`imgHijoPR_${idx}`);
                                let contPR = document.getElementById(`contHijoPR_${idx}`);
                                if (imgPR && contPR) { imgPR.src = item.src; contPR.style.display = 'block'; }
                            }
                        }
                    }
                }
            }, 100);
        }

        function calcularEdadHijo(i, datosExistentes = null) {
            const fechaNac = document.getElementById(`hijo_${i}_fecha_nac`).value;
            const edadInput = document.getElementById(`hijo_${i}_edad`);
            const docContainer = document.getElementById(`doc_hijo_${i}`);

            if (fechaNac) {
                const hoy = new Date();
                const partes = fechaNac.split('-');
                const nacimiento = new Date(partes[0], partes[1] - 1, partes[2]);
                let edad = hoy.getFullYear() - nacimiento.getFullYear();
                const m = hoy.getMonth() - nacimiento.getMonth();
                if (m < 0 || (m === 0 && hoy.getDate() < nacimiento.getDate())) {
                    edad--;
                }
                edadInput.value = edad >= 0 ? edad : 0;

                let docRuta = datosExistentes ? (edad >= 9 ? datosExistentes.cedula_doc : datosExistentes.partida_frente) : '';
                let partidaReverso = datosExistentes ? datosExistentes.partida_reverso : '';

                if (edad >= 9) {
                    docContainer.innerHTML = `
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Cédula de Identidad</label>
                            <label for="hijo_${i}_cedula" class="btn btn-outline-primary btn-sm w-100 fw-semibold rounded-pill">
                                <i class="bi bi-upload me-1"></i> ${docRuta ? 'Cambiar Cédula' : 'Adjuntar Cédula'}
                            </label>
                            <input type="file" id="hijo_${i}_cedula" name="hijo_${i}_cedula" accept="image/*,.pdf" style="display:none;" onchange="registrarArchivoTemporal('hijo', ${i}, this, 'cedula'); previewImagen(this, 'imgHijoCed_${i}', 'contHijoCed_${i}')">
                            <div id="contHijoCed_${i}" class="preview-container mx-auto" style="${docRuta ? 'display:block;' : 'display:none;'}">
                                <img id="imgHijoCed_${i}" class="preview-img" src="${docRuta || ''}" alt="Cédula Hijo">
                                <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('hijo_${i}_cedula', 'contHijoCed_${i}'); delete archivosTemporalesHijos[${i}];"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                    `;
                } else {
                    docContainer.innerHTML = `
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Acta de Nacimiento (Frente)</label>
                            <label for="hijo_${i}_partida_frente" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-pill">
                                <i class="bi bi-upload me-1"></i> ${docRuta ? 'Cambiar Frente' : 'Frente'}
                            </label>
                            <input type="file" id="hijo_${i}_partida_frente" name="hijo_${i}_partida_frente" accept="image/*,.pdf" style="display:none;" onchange="registrarArchivoTemporal('hijo', ${i}, this, 'partida'); previewImagen(this, 'imgHijoPF_${i}', 'contHijoPF_${i}')">
                            <div id="contHijoPF_${i}" class="preview-container mx-auto" style="${docRuta ? 'display:block;' : 'display:none;'}">
                                <img id="imgHijoPF_${i}" class="preview-img" src="${docRuta || ''}" alt="Partida Frente">
                                <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('hijo_${i}_partida_frente', 'contHijoPF_${i}');"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Acta de Nacimiento (Reverso)</label>
                            <label for="hijo_${i}_partida_reverso" class="btn btn-outline-secondary btn-sm w-100 fw-semibold rounded-pill">
                                <i class="bi bi-upload me-1"></i> ${partidaReverso ? 'Cambiar Reverso' : 'Reverso'}
                            </label>
                            <input type="file" id="hijo_${i}_partida_reverso" name="hijo_${i}_partida_reverso" accept="image/*,.pdf" style="display:none;" onchange="registrarArchivoTemporal('hijo', ${i}, this, 'reverso'); previewImagen(this, 'imgHijoPR_${i}', 'contHijoPR_${i}')">
                            <div id="contHijoPR_${i}" class="preview-container mx-auto" style="${partidaReverso ? 'display:block;' : 'display:none;'}">
                                <img id="imgHijoPR_${i}" class="preview-img" src="${partidaReverso || ''}" alt="Partida Reverso">
                                <button type="button" class="btn-eliminar-foto" onclick="borrarImagen('hijo_${i}_partida_reverso', 'contHijoPR_${i}');"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                    `;
                }
            } else {
                edadInput.value = '';
                docContainer.innerHTML = '';
            }
        }

        function generarCamposCursosIniciales() {
            const num = parseInt(document.getElementById('num_cursos').value) || 0;
            const cont = document.getElementById('contenedor-cursos');
            cont.innerHTML = '';
            for (let i = 1; i <= num; i++) {
                let c = cursosGuardados[i - 1] || { tipo: 'Curso', nombre: '', institucion: '', ano: '', soporte: '' };
                cont.innerHTML += `
                    <div class="card p-3 mb-3 border box-section-custom">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-2">
                                <select class="form-select form-select-sm fw-semibold text-primary" name="curso_${i}_tipo">
                                    <option value="Curso" ${c.tipo === 'Curso' ? 'selected' : ''}>Curso</option>
                                    <option value="Taller" ${c.tipo === 'Taller' ? 'selected' : ''}>Taller</option>
                                    <option value="Titulo" ${c.tipo === 'Titulo' ? 'selected' : ''}>Título</option>
                                    <option value="Diplomado" ${c.tipo === 'Diplomado' ? 'selected' : ''}>Diplomado</option>
                                    <option value="Especializacion" ${c.tipo === 'Especializacion' ? 'selected' : ''}>Especialización</option>
                                </select>
                            </div>
                            <div class="col-md-3"><input type="text" class="form-control form-control-sm" name="curso_${i}_nombre" value="${c.nombre || ''}" placeholder="Nombre" required></div>
                            <div class="col-md-2"><input type="text" class="form-control form-control-sm" name="curso_${i}_institucion" value="${c.institucion || ''}" placeholder="Institución"></div>
                            <div class="col-md-2"><input type="number" class="form-control form-control-sm" name="curso_${i}_ano" min="1900" max="2100" onkeydown="evitarNegativos(event)" oninput="validarPositivo(this)" value="${c.ano || ''}" placeholder="Año"></div>
                            <div class="col-md-3">
                                <input type="hidden" name="curso_${i}_soporte_existente" value="${c.soporte || ''}">
                                <label for="curso_${i}_soporte" class="btn btn-outline-secondary btn-sm w-100 fw-semibold text-truncate rounded-pill">
                                    <i class="bi bi-upload"></i> ${c.soporte ? 'Cambiar Soporte' : 'Seleccionar Soporte'}
                                </label>
                                <input type="file" id="curso_${i}_soporte" name="curso_${i}_soporte" accept="image/*,.pdf" style="display: none;" onchange="registrarArchivoTemporal('curso', ${i}, this); previewSoporteCurso(this, 'previewCursoContainer_${i}', 'previewCursoImg_${i}', 'previewCursoPdf_${i}')">
                                <div id="previewCursoContainer_${i}" class="preview-container mt-2 mx-auto text-center" style="${c.soporte ? 'display:block;' : 'display:none;'}">
                                    <img id="previewCursoImg_${i}" class="preview-img mb-1" src="${c.soporte || ''}" style="max-height: 80px; ${c.soporte && !c.soporte.endsWith('.pdf') ? 'display:block;' : 'display:none;'}" alt="Soporte">
                                    <div id="previewCursoPdf_${i}" class="badge bg-danger p-2 mb-1" style="${c.soporte && c.soporte.endsWith('.pdf') ? 'display:block;' : 'display:none;'};"><i class="bi bi-file-earmark-pdf fs-6"></i> PDF</div>
                                    <button type="button" class="btn-eliminar-foto" onclick="borrarSoporteCurso('curso_${i}_soporte', 'previewCursoContainer_${i}'); delete archivosTemporalesCursos[${i}];"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        function generarCamposCursos() {
            const num = parseInt(document.getElementById('num_cursos').value) || 0;
            const cont = document.getElementById('contenedor-cursos');
            let datosCursos = [];
            cont.querySelectorAll('.card').forEach((card, idx) => {
                let i = idx + 1;
                let tipo = card.querySelector(`[name="curso_${i}_tipo"]`);
                let nom = card.querySelector(`[name="curso_${i}_nombre"]`);
                let inst = card.querySelector(`[name="curso_${i}_institucion"]`);
                let ano = card.querySelector(`[name="curso_${i}_ano"]`);
                let supEst = card.querySelector(`[name="curso_${i}_soporte_existente"]`);
                datosCursos.push({
                    tipo: tipo ? tipo.value : 'Curso', nombre: nom ? nom.value : '',
                    institucion: inst ? inst.value : '', ano: ano ? ano.value : '',
                    soporte: supEst ? supEst.value : ''
                });
            });
            cont.innerHTML = '';
            for (let i = 1; i <= num; i++) {
                let c = datosCursos[i - 1] || { tipo: 'Curso', nombre: '', institucion: '', ano: '', soporte: '' };
                cont.innerHTML += `
                    <div class="card p-3 mb-3 border box-section-custom">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-2">
                                <select class="form-select form-select-sm fw-semibold text-primary" name="curso_${i}_tipo">
                                    <option value="Curso" ${c.tipo === 'Curso' ? 'selected' : ''}>Curso</option>
                                    <option value="Taller" ${c.tipo === 'Taller' ? 'selected' : ''}>Taller</option>
                                    <option value="Titulo" ${c.tipo === 'Titulo' ? 'selected' : ''}>Título</option>
                                    <option value="Diplomado" ${c.tipo === 'Diplomado' ? 'selected' : ''}>Diplomado</option>
                                    <option value="Especializacion" ${c.tipo === 'Especializacion' ? 'selected' : ''}>Especialización</option>
                                </select>
                            </div>
                            <div class="col-md-3"><input type="text" class="form-control form-control-sm" name="curso_${i}_nombre" value="${c.nombre}" placeholder="Nombre" required></div>
                            <div class="col-md-2"><input type="text" class="form-control form-control-sm" name="curso_${i}_institucion" value="${c.institucion}" placeholder="Institución"></div>
                            <div class="col-md-2"><input type="number" class="form-control form-control-sm" name="curso_${i}_ano" min="1900" max="2100" onkeydown="evitarNegativos(event)" oninput="validarPositivo(this)" value="${c.ano}" placeholder="Año"></div>
                            <div class="col-md-3">
                                <input type="hidden" name="curso_${i}_soporte_existente" value="${c.soporte || ''}">
                                <label for="curso_${i}_soporte" class="btn btn-outline-secondary btn-sm w-100 fw-semibold text-truncate rounded-pill">
                                    <i class="bi bi-upload"></i> Seleccionar Soporte
                                </label>
                                <input type="file" id="curso_${i}_soporte" name="curso_${i}_soporte" accept="image/*,.pdf" style="display: none;" onchange="registrarArchivoTemporal('curso', ${i}, this); previewSoporteCurso(this, 'previewCursoContainer_${i}', 'previewCursoImg_${i}', 'previewCursoPdf_${i}')">
                                <div id="previewCursoContainer_${i}" class="preview-container mt-2 mx-auto text-center" style="${c.soporte ? 'display:block;' : 'display:none;'}">
                                    <img id="previewCursoImg_${i}" class="preview-img mb-1" src="${c.soporte || ''}" style="max-height: 80px; ${c.soporte && !c.soporte.endsWith('.pdf') ? 'display:block;' : 'display:none;'}" alt="Soporte">
                                    <div id="previewCursoPdf_${i}" class="badge bg-danger p-2 mb-1" style="${c.soporte && c.soporte.endsWith('.pdf') ? 'display:block;' : 'display:none;'};"><i class="bi bi-file-earmark-pdf fs-6"></i> PDF</div>
                                    <button type="button" class="btn-eliminar-foto" onclick="borrarSoporteCurso('curso_${i}_soporte', 'previewCursoContainer_${i}')"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            for (let idx in archivosTemporalesCursos) {
                if (idx <= num) {
                    let item = archivosTemporalesCursos[idx];
                    let contC = document.getElementById(`previewCursoContainer_${idx}`);
                    let imgC = document.getElementById(`previewCursoImg_${idx}`);
                    let pdfC = document.getElementById(`previewCursoPdf_${idx}`);
                    if (contC && item) {
                        contC.style.display = 'block';
                        if (item.type.includes('pdf')) {
                            if(pdfC) pdfC.style.display = 'block';
                        } else {
                            if(imgC) { imgC.src = item.src; imgC.style.display = 'block'; }
                        }
                    }
                }
            }
        }

        function previewImagen(input, imgId, containerId) {
            const container = document.getElementById(containerId);
            const img = document.getElementById(imgId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    container.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewSoporteCurso(input, containerId, imgId, pdfId) {
            if (input.files && input.files[0]) {
                const container = document.getElementById(containerId);
                const img = document.getElementById(imgId);
                const pdf = document.getElementById(pdfId);
                const file = input.files[0];
                container.style.display = 'block';
                if (file.type.includes('image')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        img.src = e.target.result;
                        img.style.display = 'block';
                        pdf.style.display = 'none';
                    }
                    reader.readAsDataURL(file);
                } else if (file.type.includes('pdf')) {
                    img.style.display = 'none';
                    pdf.style.display = 'block';
                }
            }
        }

        function borrarImagen(inputId, containerId) {
            document.getElementById(inputId).value = '';
            document.getElementById(containerId).style.display = 'none';
        }

        function borrarSoporteCurso(inputId, containerId) {
            document.getElementById(inputId).value = '';
            document.getElementById(containerId).style.display = 'none';
        }
    </script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <?php include 'inactivity_check.php'; ?>
</body>
</html>