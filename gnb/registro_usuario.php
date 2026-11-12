<?php
session_start();
require_once 'conexion.php';

// Verificar sesión de usuario activa y permisos exclusivos para Administradores
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$rol_actual = strtolower(trim($_SESSION['usuario_rol'] ?? 'usuario'));
if ($rol_actual !== 'admin') {
    header("Location: dashboard.php?error=acceso_denegado");
    exit();
}

$mensaje = '';
$tipo_mensaje = '';
$lista_usuarios = [];

// 1. ELIMINAR USUARIO (Protegiendo al ID 1)
if (isset($_GET['eliminar'])) {
    $id_eliminar = (int)$_GET['eliminar'];
    
    if ($id_eliminar === 1) {
        $mensaje = 'Acción denegada: No se puede eliminar al Administrador Principal del sistema.';
        $tipo_mensaje = 'danger';
    } elseif ($id_eliminar == $_SESSION['usuario_id']) {
        $mensaje = 'No puedes eliminar tu propia cuenta de usuario en uso.';
        $tipo_mensaje = 'warning';
    } else {
        try {
            $stmt_del = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
            if ($stmt_del->execute(['id' => $id_eliminar])) {
                $mensaje = 'Usuario eliminado correctamente.';
                $tipo_mensaje = 'success';
            }
        } catch (PDOException $e) {
            $mensaje = 'Error al eliminar el usuario: ' . $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
}

// 2. SUSPENDER / ACTIVAR USUARIO
if (isset($_GET['toggle_estado'])) {
    $id_toggle = (int)$_GET['toggle_estado'];

    if ($id_toggle === 1) {
        $mensaje = 'Acción denegada: No se puede suspender al Administrador Principal.';
        $tipo_mensaje = 'danger';
    } else {
        try {
            $stmt_est = $pdo->prepare("SELECT estado FROM usuarios WHERE id = ?");
            $stmt_est->execute([$id_toggle]);
            $estado_actual = $stmt_est->fetchColumn();

            $nuevo_estado = ($estado_actual === 'suspendido') ? 'activo' : 'suspendido';

            $stmt_up_est = $pdo->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
            if ($stmt_up_est->execute([$nuevo_estado, $id_toggle])) {
                $mensaje = ($nuevo_estado === 'suspendido') ? 'Cuenta suspendida correctamente.' : 'Cuenta activada correctamente.';
                $tipo_mensaje = 'success';
            }
        } catch (PDOException $e) {
            $mensaje = 'Error al cambiar el estado del usuario: ' . $e->getMessage();
            $tipo_mensaje = 'danger';
        }
    }
}

// Función auxiliar para validar requisitos de contraseña en el servidor
function validar_fortaleza_password($pwd) {
    if (strlen($pwd) < 6) return false;
    if (!preg_match('/[A-Z]/', $pwd)) return false;
    if (!preg_match('/[0-9]/', $pwd)) return false;
    if (!preg_match('/[^A-Za-z0-9]/', $pwd)) return false;
    return true;
}

// 3. EDITAR / ACTUALIZAR USUARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_usuario'])) {
    $id_edit = (int)$_POST['id_usuario'];
    $primer_nombre = trim($_POST['primer_nombre'] ?? '');
    $segundo_nombre = trim($_POST['segundo_nombre'] ?? '');
    $primer_apellido = trim($_POST['primer_apellido'] ?? '');
    $segundo_apellido = trim($_POST['segundo_apellido'] ?? '');
    
    $tipo_cedula = trim($_POST['tipo_cedula'] ?? 'V');
    $num_cedula = trim($_POST['cedula'] ?? '');
    $cedula = $tipo_cedula . '-' . $num_cedula;

    $email = trim($_POST['email'] ?? '');
    $rol = $_POST['rol'] ?? 'Usuario';
    $clave = $_POST['clave'] ?? '';
    $confirmar_clave = $_POST['confirmar_clave'] ?? '';

    if (!empty($primer_nombre) && !empty($primer_apellido) && !empty($num_cedula) && !empty($email)) {
        
        $actualizar_clave = false;
        $clave_hash = '';

        if (!empty($clave) || !empty($confirmar_clave)) {
            if ($clave !== $confirmar_clave) {
                $mensaje = 'Las nuevas contraseñas no coinciden.';
                $tipo_mensaje = 'danger';
            } elseif (!validar_fortaleza_password($clave)) {
                $mensaje = 'La nueva contraseña no cumple con los requisitos de seguridad obligatorios.';
                $tipo_mensaje = 'danger';
            } else {
                $actualizar_clave = true;
                $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
            }
        }

        if (empty($mensaje)) {
            try {
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE (email = :email OR cedula = :cedula) AND id != :id");
                $stmt_check->execute(['email' => $email, 'cedula' => $cedula, 'id' => $id_edit]);

                if ($stmt_check->fetchColumn() > 0) {
                    $mensaje = 'La cédula o el correo ya pertenecen a otro usuario.';
                    $tipo_mensaje = 'warning';
                } else {
                    if ($actualizar_clave) {
                        $sql_up = "UPDATE usuarios SET primer_nombre = :p_nom, segundo_nombre = :s_nom, primer_apellido = :p_ape, segundo_apellido = :s_ape, nombre = :nombre_gen, apellido = :apellido_gen, cedula = :cedula, email = :email, password = :password, rol = :rol WHERE id = :id";
                        $params = [
                            'p_nom' => $primer_nombre, 's_nom' => $segundo_nombre,
                            'p_ape' => $primer_apellido, 's_ape' => $segundo_apellido,
                            'nombre_gen' => $primer_nombre, 'apellido_gen' => $primer_apellido,
                            'cedula' => $cedula, 'email' => $email, 'password' => $clave_hash,
                            'rol' => $rol, 'id' => $id_edit
                        ];
                    } else {
                        $sql_up = "UPDATE usuarios SET primer_nombre = :p_nom, segundo_nombre = :s_nom, primer_apellido = :p_ape, segundo_apellido = :s_ape, nombre = :nombre_gen, apellido = :apellido_gen, cedula = :cedula, email = :email, rol = :rol WHERE id = :id";
                        $params = [
                            'p_nom' => $primer_nombre, 's_nom' => $segundo_nombre,
                            'p_ape' => $primer_apellido, 's_ape' => $segundo_apellido,
                            'nombre_gen' => $primer_nombre, 'apellido_gen' => $primer_apellido,
                            'cedula' => $cedula, 'email' => $email,
                            'rol' => $rol, 'id' => $id_edit
                        ];
                    }

                    $stmt_up = $pdo->prepare($sql_up);
                    if ($stmt_up->execute($params)) {
                        $mensaje = 'Usuario actualizado exitosamente.';
                        $tipo_mensaje = 'success';
                    }
                }
            } catch (PDOException $e) {
                $mensaje = 'Error al actualizar el usuario: ' . $e->getMessage();
                $tipo_mensaje = 'danger';
            }
        }
    } else {
        $mensaje = 'Por favor complete todos los campos obligatorios.';
        $tipo_mensaje = 'danger';
    }
}

// 4. REGISTRAR UN NUEVO USUARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $primer_nombre = trim($_POST['primer_nombre'] ?? '');
    $segundo_nombre = trim($_POST['segundo_nombre'] ?? '');
    $primer_apellido = trim($_POST['primer_apellido'] ?? '');
    $segundo_apellido = trim($_POST['segundo_apellido'] ?? '');
    
    $tipo_cedula = trim($_POST['tipo_cedula'] ?? 'V');
    $num_cedula = trim($_POST['cedula'] ?? '');
    $cedula = $tipo_cedula . '-' . $num_cedula;

    $email = trim($_POST['email'] ?? '');
    $clave = $_POST['clave'] ?? '';
    $confirmar_clave = $_POST['confirmar_clave'] ?? '';
    $rol = $_POST['rol'] ?? 'Usuario';

    if (!empty($primer_nombre) && !empty($primer_apellido) && !empty($num_cedula) && !empty($email) && !empty($clave) && !empty($confirmar_clave)) {
        if ($clave !== $confirmar_clave) {
            $mensaje = 'Las contraseñas no coinciden.';
            $tipo_mensaje = 'danger';
        } elseif (!validar_fortaleza_password($clave)) {
            $mensaje = 'La contraseña no cumple con los requisitos de seguridad obligatorios.';
            $tipo_mensaje = 'danger';
        } else {
            try {
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email OR cedula = :cedula");
                $stmt_check->execute(['email' => $email, 'cedula' => $cedula]);
                
                if ($stmt_check->fetchColumn() > 0) {
                    $mensaje = 'La cédula o el correo electrónico ya se encuentran registrados.';
                    $tipo_mensaje = 'warning';
                } else {
                    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);

                    $sql = "INSERT INTO usuarios (primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, nombre, apellido, cedula, email, password, rol, estado) 
                            VALUES (:p_nom, :s_nom, :p_ape, :s_ape, :nombre_gen, :apellido_gen, :cedula, :email, :password, :rol, 'activo')";
                    $stmt = $pdo->prepare($sql);
                    $guardado = $stmt->execute([
                        'p_nom' => $primer_nombre, 
                        's_nom' => $segundo_nombre,
                        'p_ape' => $primer_apellido, 
                        's_ape' => $segundo_apellido,
                        'nombre_gen' => $primer_nombre, 
                        'apellido_gen' => $primer_apellido,
                        'cedula' => $cedula, 
                        'email' => $email,
                        'password' => $clave_hash, 
                        'rol' => $rol
                    ]);

                    if ($guardado) {
                        $mensaje = 'Usuario registrado exitosamente.';
                        $tipo_mensaje = 'success';
                    }
                }
            } catch (PDOException $e) {
                $mensaje = 'Error al registrar el usuario: ' . $e->getMessage();
                $tipo_mensaje = 'danger';
            }
        }
    } else {
        $mensaje = 'Por favor complete todos los campos obligatorios.';
        $tipo_mensaje = 'danger';
    }
}

// OBTENER LA LISTA DE USUARIOS
try {
    $stmt_lista = $pdo->query("SELECT * FROM usuarios ORDER BY (id = 1) DESC, id DESC");
    $resultado = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);
    if (is_array($resultado)) {
        $lista_usuarios = $resultado;
    }
} catch (PDOException $e) {
    $lista_usuarios = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio Criminalístico N° 11 (Zulia) - Gestión de Usuarios</title>
    <script>
        const savedTheme = localStorage.getItem('gnb_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Contraste de selección claro y visible en modo oscuro */
        [data-theme="dark"] ::selection {
            background-color: #2563eb;
            color: #ffffff;
        }
        [data-theme="dark"] ::-moz-selection {
            background-color: #2563eb;
            color: #ffffff;
        }

        [data-theme="light"] ::selection {
            background-color: #2563eb;
            color: #ffffff;
        }
        [data-theme="light"] ::-moz-selection {
            background-color: #2563eb;
            color: #ffffff;
        }

        [data-theme="dark"] {
            --bg-body: #0b1120; 
            --bg-navbar: #0f172a; 
            --text-main: #f8fafc; 
            --text-muted: #cbd5e1;
            --card-bg: #1e293b; 
            --card-border: rgba(255, 255, 255, 0.12); 
            --card-shadow: 0 10px 30px rgba(0,0,0,0.08);
            --table-hover: rgba(59, 130, 246, 0.08); 
            --input-bg: #0f172a; 
            --input-bg-focus: #151e32; 
            --input-border: rgba(255, 255, 255, 0.22); 
            --btn-nav-bg: rgba(255, 255, 255, 0.06); 
            --btn-nav-border: rgba(255, 255, 255, 0.15); 
            --btn-nav-text: #f8fafc;
        }
        [data-theme="light"] {
            --bg-body: #cbd5e1; 
            --bg-navbar: #ffffff; 
            --text-main: #0f172a; 
            --text-muted: #334155;
            --card-bg: #ffffff; 
            --card-border: rgba(15, 23, 42, 0.22); 
            --card-shadow: 0 12px 30px rgba(15, 23, 42, 0.15);
            --table-hover: #f1f5f9; 
            --input-bg: #ffffff;
            --input-bg-focus: #ffffff;
            --input-border: #94a3b8;
            --btn-nav-bg: #f1f5f9; 
            --btn-nav-border: rgba(15, 23, 42, 0.25); 
            --btn-nav-text: #0f172a;
        }

        body { 
            background-color: var(--bg-body); 
            min-height: 100vh; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            color: var(--text-main); 
            transition: background 0.2s ease, color 0.2s ease;
        }

        .navbar-gnb { 
            background: var(--bg-navbar); 
            border-bottom: 1px solid var(--card-border); 
            box-shadow: 0 4px 25px rgba(0,0,0,0.15); 
        }

        .navbar-brand img { 
            width: 120px; 
            height: 120px; 
            object-fit: contain; 
            position: relative; 
            top: 2px; 
            margin-top: -30px; 
            margin-bottom: -30px; 
            filter: drop-shadow(0 3px 6px rgba(0,0,0,0.3));
        }

        .card-custom { 
            border-radius: 1.25rem; 
            background: var(--card-bg); 
            border: 1px solid var(--card-border); 
            color: var(--text-main); 
            box-shadow: var(--card-shadow); 
        }

        .table { 
            color: var(--text-main); 
            margin-bottom: 0; 
            font-size: 0.92rem; 
        }

        .table > :not(caption) > * > * { 
            background-color: transparent; 
            color: var(--text-main); 
            border-bottom-color: var(--card-border); 
            padding: 0.85rem 0.6rem; 
            vertical-align: middle; 
        }

        .table-hover > tbody > tr:hover > * { 
            background-color: var(--table-hover) !important; 
        }

        .table th { 
            background: linear-gradient(135deg, #1e3a8a, #1d4ed8) !important; 
            color: #ffffff !important; 
            font-weight: 600; 
            text-transform: uppercase; 
            font-size: 0.75rem; 
            letter-spacing: 0.6px; 
            border: none; 
            padding: 0.95rem 0.6rem; 
            white-space: nowrap; 
        }

        .form-control, .form-select { 
            background-color: var(--input-bg) !important; 
            color: var(--text-main) !important; 
            border: 1.5px solid var(--input-border) !important; 
            font-size: 0.88rem; 
            border-radius: 0.65rem; 
            padding: 0.55rem 0.75rem;
            transition: all 0.25s ease;
        }

        .form-control::placeholder {
            color: var(--text-muted);
            opacity: 0.75;
        }

        .form-select option { 
            background-color: var(--card-bg); 
            color: var(--text-main); 
        }

        .form-control:focus, .form-select:focus { 
            background-color: var(--input-bg-focus) !important; 
            color: var(--text-main) !important; 
            border-color: #3b82f6 !important; 
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25) !important; 
        }

        .btn-action-sm { 
            padding: 0.38rem 0.65rem; 
            font-size: 0.78rem; 
            font-weight: 600; 
            border-radius: 50rem; 
            border: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.25rem; 
            transition: all 0.2s ease;
            text-decoration: none !important; 
        }

        .btn-action-sm:hover {
            transform: translateY(-1px);
            filter: brightness(1.1);
        }

        .btn-editar-custom { background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff !important; box-shadow: 0 3px 10px rgba(217, 119, 6, 0.25); }
        .btn-suspender-custom { background: linear-gradient(135deg, #ca8a04, #a16207); color: #ffffff !important; box-shadow: 0 3px 10px rgba(202, 138, 4, 0.25); }
        .btn-activar-custom { background: linear-gradient(135deg, #16a34a, #15803d); color: #ffffff !important; box-shadow: 0 3px 10px rgba(22, 163, 74, 0.25); }
        .btn-eliminar-custom { background: #fee2e2; color: #dc2626 !important; border: 1px solid #fca5a5; }
        .btn-eliminar-custom:hover { background: #dc2626; color: #fff !important; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-gnb shadow-lg py-2 sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold fs-6 d-flex align-items-center" href="dashboard.php" style="color: var(--text-main);">
                <img src="imagen/logo_gnb.png?v=<?php echo time(); ?>" alt="Escudo GNB" class="me-2"> Laboratorio Criminalístico N<sup>o</sup> 11 (Zulia)
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-semibold" style="background: var(--btn-nav-bg); border-color: var(--btn-nav-border); color: var(--btn-nav-text);">
                    <i class="bi bi-arrow-left me-1"></i> Volver al Panel
                </a>
                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCerrarSesion">
                    <i class="bi bi-box-arrow-right me-1"></i> Salir
                </button>
            </div>
        </div>
    </nav>

    <!-- NOTIFICACIONES FLOTANTES -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
        <?php if (!empty($mensaje)): ?>
            <div id="liveToast" class="toast align-items-center text-white bg-<?php echo ($tipo_mensaje === 'success') ? 'success' : (($tipo_mensaje === 'warning') ? 'warning text-dark' : 'danger'); ?> border-0 shadow-lg rounded-4 show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body fw-semibold d-flex align-items-center">
                        <i class="bi bi-info-circle-fill fs-5 me-2"></i> <?php echo $mensaje; ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="container-fluid px-4 py-4">
        <div class="row g-4">
            
            <!-- FORMULARIO IZQUIERDA -->
            <div class="col-xl-4 col-lg-5">
                <div class="card card-custom p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 text-success p-2 rounded-3 me-3">
                            <i class="bi bi-person-plus-fill fs-4"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 fs-5" style="color: var(--text-main);">Crear Nuevo Usuario</h4>
                            <p class="small mb-0" style="color: var(--text-muted);">Registre un administrador o usuario.</p>
                        </div>
                    </div>

                    <form action="registro_usuario.php" method="POST" id="formCrearUsuario">
                        <input type="hidden" name="crear_usuario" value="1">
                        
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size: 0.75rem; color: var(--text-main);">1er Nombre</label>
                                <input type="text" class="form-control" name="primer_nombre" placeholder="Ej: Pedro" oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size: 0.75rem; color: var(--text-main);">2do Nombre</label>
                                <input type="text" class="form-control" name="segundo_nombre" placeholder="Opcional" oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');">
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size: 0.75rem; color: var(--text-main);">1er Apellido</label>
                                <input type="text" class="form-control" name="primer_apellido" placeholder="Ej: Pérez" oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold" style="font-size: 0.75rem; color: var(--text-main);">2do Apellido</label>
                                <input type="text" class="form-control" name="segundo_apellido" placeholder="Opcional" oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold" style="font-size: 0.75rem; color: var(--text-main);">Cédula de Identidad</label>
                            <div class="input-group">
                                <select class="form-select fw-bold text-center" name="tipo_cedula" style="max-width: 80px;">
                                    <option value="V" selected>V</option>
                                    <option value="E">E</option>
                                </select>
                                <input type="text" class="form-control" name="cedula" placeholder="12345678" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="9" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold" style="font-size: 0.75rem; color: var(--text-main);">Correo Electrónico</label>
                            <input type="email" class="form-control" name="email" placeholder="correo@ejemplo.com" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold" style="font-size: 0.75rem; color: var(--text-main);">Contraseña</label>
                            <input type="password" class="form-control" id="clave" name="clave" placeholder="••••••••" required>
                            <div id="passwordRequirements" class="mt-1 small" style="line-height: 1.3; font-size: 0.7rem; display: none;">
                                <div id="req-length" class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i> Mínimo 6 caracteres</div>
                                <div id="req-upper" class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i> Al menos una mayúscula</div>
                                <div id="req-number" class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i> Al menos un número</div>
                                <div id="req-symbol" class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i> Al menos un símbolo</div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold" style="font-size: 0.75rem; color: var(--text-main);">Verificar Contraseña</label>
                            <input type="password" class="form-control" id="confirmar_clave" name="confirmar_clave" placeholder="••••••••" required>
                            <div id="feedbackPassword" class="form-text small mt-0.5 fw-semibold" style="font-size: 0.7rem;"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size: 0.75rem; color: var(--text-main);">Rol de Acceso</label>
                            <select class="form-select" name="rol">
                                <option value="Admin">Administrador</option>
                                <option value="Usuario" selected>Usuario</option>
                            </select>
                        </div>

                        <button type="submit" id="btnGuardar" class="btn btn-success w-100 fw-bold py-2.5 rounded-pill shadow-sm" style="background: linear-gradient(135deg, #16a34a, #15803d); border:none; font-size: 0.9rem;">
                            <i class="bi bi-check-circle-fill me-1"></i> Guardar Usuario
                        </button>
                    </form>
                </div>
            </div>

            <!-- TABLA DERECHA -->
            <div class="col-xl-8 col-lg-7">
                <div class="card card-custom p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-3">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 fs-5" style="color: var(--text-main);">Usuarios del Sistema</h4>
                            <p class="small mb-0" style="color: var(--text-muted);">Listado general de cuentas registradas.</p>
                        </div>
                    </div>

                    <div class="table-responsive rounded-3 overflow-hidden border" style="border-color: var(--card-border) !important;">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Cédula</th>
                                    <th>1er Nombre</th>
                                    <th>2do Nombre</th>
                                    <th>1er Apellido</th>
                                    <th>2do Apellido</th>
                                    <th>Correo Electrónico</th>
                                    <th>Rol / Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($lista_usuarios) > 0): ?>
                                    <?php foreach ($lista_usuarios as $u): ?>
                                        <?php 
                                            $id_usuario = (int)$u['id'];
                                            $estado_usuario = $u['estado'] ?? 'activo';

                                            $partes_cedula = explode('-', $u['cedula'] ?? '');
                                            $t_cedula_val = count($partes_cedula) > 1 ? $partes_cedula[0] : 'V';
                                            $n_cedula_val = count($partes_cedula) > 1 ? $partes_cedula[1] : ($u['cedula'] ?? '');

                                            $p_nom = trim($u['primer_nombre'] ?? '');
                                            $s_nom = trim($u['segundo_nombre'] ?? '');
                                            $p_ape = trim($u['primer_apellido'] ?? '');
                                            $s_ape = trim($u['segundo_apellido'] ?? '');
                                            $email_val = trim($u['email'] ?? ($u['correo'] ?? ''));

                                            if (empty($p_nom) && !empty($u['nombre'])) {
                                                $nombres_arr = array_values(array_filter(explode(' ', trim($u['nombre']))));
                                                $p_nom = $nombres_arr[0] ?? '';
                                                $s_nom = implode(' ', array_slice($nombres_arr, 1));
                                            }
                                            if (empty($p_ape) && !empty($u['apellido'])) {
                                                $apellidos_arr = array_values(array_filter(explode(' ', trim($u['apellido']))));
                                                $p_ape = $apellidos_arr[0] ?? '';
                                                $s_ape = implode(' ', array_slice($apellidos_arr, 1));
                                            }
                                        ?>
                                        <tr>
                                            <td class="fw-bold text-info" style="font-size: 0.93rem;"><?php echo htmlspecialchars($u['cedula'] ?? 'N/A'); ?></td>
                                            <td class="fw-semibold" style="color: var(--text-main); font-size: 0.93rem;"><?php echo htmlspecialchars($p_nom ?: '-'); ?></td>
                                            <td style="color: var(--text-muted); font-size: 0.93rem;"><?php echo htmlspecialchars($s_nom ?: '-'); ?></td>
                                            <td class="fw-semibold" style="color: var(--text-main); font-size: 0.93rem;"><?php echo htmlspecialchars($p_ape ?: '-'); ?></td>
                                            <td style="color: var(--text-muted); font-size: 0.93rem;"><?php echo htmlspecialchars($s_ape ?: '-'); ?></td>
                                            <td style="font-size: 0.9rem; color: var(--text-muted);"><?php echo htmlspecialchars($email_val ?: 'N/A'); ?></td>
                                            <td>
                                                <?php $rol_usuario = strtolower($u['rol'] ?? 'usuario'); ?>
                                                <span class="badge <?php echo ($rol_usuario === 'admin') ? 'bg-danger' : 'bg-primary'; ?> rounded-pill px-2 py-1 mb-1 d-block text-center" style="font-size: 0.7rem;">
                                                    <?php echo strtoupper(htmlspecialchars($u['rol'] ?? 'USUARIO')); ?>
                                                </span>
                                                <?php if ($estado_usuario === 'suspendido'): ?>
                                                    <span class="badge bg-warning text-dark rounded-pill px-2 py-1 d-block text-center" style="font-size: 0.7rem;">SUSPENDIDO</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success rounded-pill px-2 py-1 d-block text-center" style="font-size: 0.7rem;">ACTIVO</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center align-items-center gap-1 flex-wrap">
                                                    <button type="button" class="btn-action-sm btn-editar-custom" data-bs-toggle="modal" data-bs-target="#modalEditar<?php echo $id_usuario; ?>" title="Editar">
                                                        <i class="bi bi-pencil-square"></i> Editar
                                                    </button>
                                                    
                                                    <?php if ($id_usuario !== 1): ?>
                                                        <?php if ($estado_usuario === 'suspendido'): ?>
                                                            <button type="button" class="btn-action-sm btn-activar-custom" data-bs-toggle="modal" data-bs-target="#modalSuspender<?php echo $id_usuario; ?>" title="Activar">
                                                                <i class="bi bi-check-circle-fill"></i> Activar
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn-action-sm btn-suspender-custom" data-bs-toggle="modal" data-bs-target="#modalSuspender<?php echo $id_usuario; ?>" title="Suspender">
                                                                <i class="bi bi-pause-circle-fill"></i> Suspender
                                                            </button>
                                                        <?php endif; ?>

                                                        <button type="button" class="btn-action-sm btn-eliminar-custom" data-bs-toggle="modal" data-bs-target="#modalEliminar<?php echo $id_usuario; ?>" title="Eliminar">
                                                            <i class="bi bi-trash3-fill"></i> Eliminar
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary text-light px-2 py-1" style="font-size: 0.72rem;">Protegido</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- MODAL SUSPENDER / ACTIVAR USUARIO -->
                                        <div class="modal fade" id="modalSuspender<?php echo $id_usuario; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: var(--card-bg); color: var(--text-main);">
                                                    <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #d97706, #b45309);">
                                                        <h5 class="modal-title fw-bold fs-6"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Confirmar Cambio de Estado</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-center py-4">
                                                        <i class="bi bi-pause-circle-fill text-warning display-4 d-block mb-3"></i>
                                                        <h5 class="fw-bold mb-2"><?php echo ($estado_usuario === 'suspendido') ? '¿Deseas activar este usuario?' : '¿Deseas suspender este usuario?'; ?></h5>
                                                        <p class="small mb-0" style="color: var(--text-main) !important;">Usuario: <strong><?php echo htmlspecialchars($p_nom . ' ' . $p_ape); ?></strong></p>
                                                    </div>
                                                    <div class="modal-footer border-0 d-flex justify-content-center gap-2 py-3" style="background: var(--card-bg);">
                                                        <button type="button" class="btn btn-outline-secondary px-4 fw-bold rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                                                        <a href="registro_usuario.php?toggle_estado=<?php echo $id_usuario; ?>" class="btn btn-warning px-4 fw-bold rounded-pill shadow-sm text-dark" style="background: linear-gradient(135deg, #f59e0b, #d97706); border:none;">Sí, Confirmar</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- MODAL ELIMINAR USUARIO -->
                                        <div class="modal fade" id="modalEliminar<?php echo $id_usuario; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: var(--card-bg); color: var(--text-main);">
                                                    <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
                                                        <h5 class="modal-title fw-bold fs-6"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Confirmar Eliminación</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-center py-4">
                                                        <i class="bi bi-trash3-fill text-danger display-4 d-block mb-3"></i>
                                                        <h5 class="fw-bold mb-2">¿Deseas eliminar este usuario?</h5>
                                                        <p class="small text-muted mb-0">Esta acción eliminará el registro del sistema de forma permanente.</p>
                                                    </div>
                                                    <div class="modal-footer border-0 d-flex justify-content-center gap-2 py-3" style="background: var(--card-bg);">
                                                        <button type="button" class="btn btn-outline-secondary px-4 fw-bold rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                                                        <a href="registro_usuario.php?eliminar=<?php echo $id_usuario; ?>" class="btn btn-danger px-4 fw-bold rounded-pill shadow-sm" style="background: linear-gradient(135deg, #dc2626, #b91c1c); border:none;">Sí, Eliminar</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- MODAL EDITAR USUARIO -->
                                        <div class="modal fade" id="modalEditar<?php echo $id_usuario; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: var(--card-bg); color: var(--text-main);">
                                                    <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #1e3a8a, #1d4ed8);">
                                                        <h5 class="modal-title fw-bold fs-6"><i class="bi bi-pencil-square text-warning me-2"></i>Editar Usuario</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="registro_usuario.php" method="POST" id="formEditar<?php echo $id_usuario; ?>">
                                                        <div class="modal-body p-4 text-start">
                                                            <input type="hidden" name="editar_usuario" value="1">
                                                            <input type="hidden" name="id_usuario" value="<?php echo $id_usuario; ?>">

                                                            <div class="row g-2 mb-3">
                                                                <div class="col-6">
                                                                    <label class="form-label fw-semibold small">1er Nombre</label>
                                                                    <input type="text" class="form-control" name="primer_nombre" value="<?php echo htmlspecialchars($p_nom); ?>" oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');" required>
                                                                </div>
                                                                <div class="col-6">
                                                                    <label class="form-label fw-semibold small">2do Nombre</label>
                                                                    <input type="text" class="form-control" name="segundo_nombre" value="<?php echo htmlspecialchars($s_nom); ?>" oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');" placeholder="Opcional">
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="row g-2 mb-3">
                                                                <div class="col-6">
                                                                    <label class="form-label fw-semibold small">1er Apellido</label>
                                                                    <input type="text" class="form-control" name="primer_apellido" value="<?php echo htmlspecialchars($p_ape); ?>" oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');" required>
                                                                </div>
                                                                <div class="col-6">
                                                                    <label class="form-label fw-semibold small">2do Apellido</label>
                                                                    <input type="text" class="form-control" name="segundo_apellido" value="<?php echo htmlspecialchars($s_ape); ?>" oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');" placeholder="Opcional">
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Cédula</label>
                                                                <div class="input-group">
                                                                    <select class="form-select fw-bold text-center" name="tipo_cedula" style="max-width: 85px;">
                                                                        <option value="V" <?php echo ($t_cedula_val === 'V') ? 'selected' : ''; ?>>V</option>
                                                                        <option value="E" <?php echo ($t_cedula_val === 'E') ? 'selected' : ''; ?>>E</option>
                                                                    </select>
                                                                    <input type="text" class="form-control" name="cedula" value="<?php echo htmlspecialchars($n_cedula_val); ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="9" required>
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Correo Electrónico</label>
                                                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email_val); ?>" required>
                                                            </div>

                                                            <!-- CAMPO DE NUEVA CONTRASEÑA -->
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Nueva Contraseña (Opcional)</label>
                                                                <input type="password" class="form-control clave-edit" data-id="<?php echo $id_usuario; ?>" name="clave" placeholder="Dejar en blanco para mantener la actual">
                                                                <div id="passwordRequirementsEdit<?php echo $id_usuario; ?>" class="mt-1 small" style="line-height: 1.3; font-size: 0.7rem; display: none;">
                                                                    <div id="req-length-edit<?php echo $id_usuario; ?>" class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i> Mínimo 6 caracteres</div>
                                                                    <div id="req-upper-edit<?php echo $id_usuario; ?>" class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i> Al menos una mayúscula</div>
                                                                    <div id="req-number-edit<?php echo $id_usuario; ?>" class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i> Al menos un número</div>
                                                                    <div id="req-symbol-edit<?php echo $id_usuario; ?>" class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i> Al menos un símbolo</div>
                                                                </div>
                                                            </div>

                                                            <!-- CAMPO DE REPETIR NUEVA CONTRASEÑA -->
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Repetir Nueva Contraseña</label>
                                                                <input type="password" class="form-control confirmar-edit" data-id="<?php echo $id_usuario; ?>" name="confirmar_clave" placeholder="Repetir contraseña nueva">
                                                                <div id="feedbackPasswordEdit<?php echo $id_usuario; ?>" class="form-text small mt-0.5 fw-semibold" style="font-size: 0.7rem;"></div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Rol de Acceso</label>
                                                                <select class="form-select" name="rol" <?php echo ($id_usuario === 1) ? 'disabled' : ''; ?>>
                                                                    <option value="Admin" <?php echo (strtolower($u['rol'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                                                                    <option value="Usuario" <?php echo (strtolower($u['rol'] ?? '') !== 'admin') ? 'selected' : ''; ?>>Usuario</option>
                                                                </select>
                                                                <?php if ($id_usuario === 1): ?>
                                                                    <input type="hidden" name="rol" value="Admin">
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer px-4 py-3" style="background: var(--card-bg);">
                                                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                                                            <button type="submit" id="btnGuardarEdit<?php echo $id_usuario; ?>" class="btn btn-primary fw-bold rounded-pill px-4" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border:none;">Guardar Cambios</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">No hay usuarios registrados.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Cerrar Sesión -->
    <div class="modal fade" id="modalCerrarSesion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: var(--card-bg); color: var(--text-main);">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e3a8a, #1d4ed8);">
                    <h5 class="modal-title fw-bold"><i class="bi bi-shield-exclamation me-2"></i>Confirmar Salida</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-box-arrow-right text-info display-4 d-block mb-3"></i>
                    <h5 class="fw-bold mb-2">¿Seguro que deseas cerrar sesión?</h5>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-center gap-2 py-3">
                    <button type="button" class="btn btn-outline-secondary px-4 fw-bold rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <a href="logout.php" class="btn btn-primary px-4 fw-bold rounded-pill shadow-sm" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border:none;">Sí, Salir</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const claveInput = document.getElementById('clave');
        const passwordRequirements = document.getElementById('passwordRequirements');
        const reqLength = document.getElementById('req-length');
        const reqUpper = document.getElementById('req-upper');
        const reqNumber = document.getElementById('req-number');
        const reqSymbol = document.getElementById('req-symbol');
        const confirmarClaveInput = document.getElementById('confirmar_clave');
        const feedbackPassword = document.getElementById('feedbackPassword');
        const btnGuardar = document.getElementById('btnGuardar');

        function validarPassword() {
            const val = claveInput.value;
            if (val.length === 0) {
                passwordRequirements.style.display = 'none';
                return false;
            } else {
                passwordRequirements.style.display = 'block';
            }

            const lenOk = val.length >= 6;
            const upperOk = /[A-Z]/.test(val);
            const numOk = /[0-9]/.test(val);
            const symOk = /[^A-Za-z0-9]/.test(val);

            reqLength.className = lenOk ? 'text-success fw-semibold' : 'text-danger fw-semibold';
            reqLength.innerHTML = `<i class="bi bi-${lenOk ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'} me-1"></i> Mínimo 6 caracteres`;

            reqUpper.className = upperOk ? 'text-success fw-semibold' : 'text-danger fw-semibold';
            reqUpper.innerHTML = `<i class="bi bi-${upperOk ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'} me-1"></i> Al menos una mayúscula`;

            reqNumber.className = numOk ? 'text-success fw-semibold' : 'text-danger fw-semibold';
            reqNumber.innerHTML = `<i class="bi bi-${numOk ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'} me-1"></i> Al menos un número`;

            reqSymbol.className = symOk ? 'text-success fw-semibold' : 'text-danger fw-semibold';
            reqSymbol.innerHTML = `<i class="bi bi-${symOk ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'} me-1"></i> Al menos un símbolo`;

            return lenOk && upperOk && numOk && symOk;
        }

        function validarPasswordsEnTiempoReal() {
            const fortalezaOk = validarPassword();
            const val1 = claveInput.value;
            const val2 = confirmarClaveInput.value;

            if (val2 === '') {
                feedbackPassword.textContent = '';
                confirmarClaveInput.style.borderColor = '';
                btnGuardar.disabled = true;
                return;
            }

            const coincide = (val1 === val2);

            if (coincide && fortalezaOk) {
                feedbackPassword.textContent = 'Contraseña válida y coincide.';
                feedbackPassword.className = 'form-text small mt-0.5 fw-semibold text-success';
                confirmarClaveInput.style.borderColor = '#16a34a';
                btnGuardar.disabled = false;
            } else if (!coincide && !fortalezaOk) {
                feedbackPassword.textContent = 'Las contraseñas no coinciden y no cumplen con los requisitos de seguridad.';
                feedbackPassword.className = 'form-text small mt-0.5 fw-semibold text-danger';
                confirmarClaveInput.style.borderColor = '#dc2626';
                btnGuardar.disabled = true;
            } else if (!coincide) {
                feedbackPassword.textContent = 'Las contraseñas no coinciden.';
                feedbackPassword.className = 'form-text small mt-0.5 fw-semibold text-danger';
                confirmarClaveInput.style.borderColor = '#dc2626';
                btnGuardar.disabled = true;
            } else {
                feedbackPassword.textContent = 'La contraseña no cumple con los requisitos de seguridad.';
                feedbackPassword.className = 'form-text small mt-0.5 fw-semibold text-danger';
                confirmarClaveInput.style.borderColor = '#dc2626';
                btnGuardar.disabled = true;
            }
        }

        if (claveInput && confirmarClaveInput) {
            btnGuardar.disabled = true;
            claveInput.addEventListener('input', validarPasswordsEnTiempoReal);
            confirmarClaveInput.addEventListener('input', validarPasswordsEnTiempoReal);
        }

        document.querySelectorAll('.clave-edit').forEach(inputClave => {
            const uid = inputClave.getAttribute('data-id');
            const inputConfirmar = document.querySelector(`.confirmar-edit[data-id="${uid}"]`);
            const reqBox = document.getElementById(`passwordRequirementsEdit${uid}`);
            const feedbackEdit = document.getElementById(`feedbackPasswordEdit${uid}`);
            const btnEdit = document.getElementById(`btnGuardarEdit${uid}`);

            const reqL = document.getElementById(`req-length-edit${uid}`);
            const reqU = document.getElementById(`req-upper-edit${uid}`);
            const reqN = document.getElementById(`req-number-edit${uid}`);
            const reqS = document.getElementById(`req-symbol-edit${uid}`);

            function verificarEdicion() {
                const val = inputClave.value;
                if (val.length === 0) {
                    reqBox.style.display = 'none';
                    feedbackEdit.textContent = '';
                    inputConfirmar.value = '';
                    btnEdit.disabled = false;
                    return;
                } else {
                    reqBox.style.display = 'block';
                }

                const lOk = val.length >= 6;
                const uOk = /[A-Z]/.test(val);
                const nOk = /[0-9]/.test(val);
                const sOk = /[^A-Za-z0-9]/.test(val);

                reqL.className = lOk ? 'text-success fw-semibold' : 'text-danger fw-semibold';
                reqL.innerHTML = `<i class="bi bi-${lOk ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'} me-1"></i> Mínimo 6 caracteres`;

                reqU.className = uOk ? 'text-success fw-semibold' : 'text-danger fw-semibold';
                reqU.innerHTML = `<i class="bi bi-${uOk ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'} me-1"></i> Al menos una mayúscula`;

                reqN.className = nOk ? 'text-success fw-semibold' : 'text-danger fw-semibold';
                reqN.innerHTML = `<i class="bi bi-${nOk ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'} me-1"></i> Al menos un número`;

                reqS.className = sOk ? 'text-success fw-semibold' : 'text-danger fw-semibold';
                reqS.innerHTML = `<i class="bi bi-${sOk ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'} me-1"></i> Al menos un símbolo`;

                const fortalezaOk = lOk && uOk && nOk && sOk;
                const matchOk = (val === inputConfirmar.value);

                if (fortalezaOk && matchOk) {
                    feedbackEdit.textContent = 'Contraseña nueva válida y coincide.';
                    feedbackEdit.className = 'form-text small mt-0.5 fw-semibold text-success';
                    btnEdit.disabled = false;
                } else if (!matchOk && !fortalezaOk) {
                    feedbackEdit.textContent = 'Las contraseñas no coinciden y no cumplen con los requisitos de seguridad.';
                    feedbackEdit.className = 'form-text small mt-0.5 fw-semibold text-danger';
                    btnEdit.disabled = true;
                } else if (!matchOk) {
                    feedbackEdit.textContent = 'Las contraseñas no coinciden.';
                    feedbackEdit.className = 'form-text small mt-0.5 fw-semibold text-danger';
                    btnEdit.disabled = true;
                } else {
                    feedbackEdit.textContent = 'La contraseña no cumple con los requisitos de seguridad.';
                    feedbackEdit.className = 'form-text small mt-0.5 fw-semibold text-danger';
                    btnEdit.disabled = true;
                }
            }

            inputClave.addEventListener('input', verificarEdicion);
            inputConfirmar.addEventListener('input', verificarEdicion);
        });

        const toastEl = document.getElementById('liveToast');
        if (toastEl) {
            setTimeout(() => { new bootstrap.Toast(toastEl).hide(); }, 4000);
        }
    </script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <?php include 'inactivity_check.php'; ?>
</body>
</html>