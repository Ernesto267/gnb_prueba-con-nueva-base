<?php
// verificar_sesion.php con control de suspensión en tiempo real
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir la conexión a la base de datos
require_once 'conexion.php';

// 1. Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header("Location: index.php?error=acceso_no_autorizado");
    exit();
}

// 2. VERIFICAR EN TIEMPO REAL SI LA CUENTA ESTÁ SUSPENDIDA
try {
    $stmt_est = $pdo->prepare("SELECT estado FROM usuarios WHERE id = ?");
    $stmt_est->execute([$_SESSION['usuario_id']]);
    $estado_actual = $stmt_est->fetchColumn();

    // Si la cuenta fue suspendida, destruimos la sesión de inmediato y lo sacamos
    if ($estado_actual === 'suspendido' || $estado_actual === false) {
        session_unset();
        session_destroy();
        header("Location: index.php?error=cuenta_suspendida");
        exit();
    }
} catch (PDOException $e) {
    // Si ocurre un error con la base de datos, dejamos pasar para no bloquear por fallos de red
}

/**
 * Función para restringir por roles específicos
 * @param array $rolesPermitidos Ej: ['admin']
 */
function verificarRol($rolesPermitidos = []) {
    if (!empty($rolesPermitidos)) {
        // Obtenemos el rol actual unificado en minúsculas
        $rolUsuario = strtolower(trim($_SESSION['usuario_rol'] ?? 'usuario'));
        
        // Convertir los permitidos a minúsculas también para evitar errores
        $rolesPermitidos = array_map('strtolower', $rolesPermitidos);
        
        if (!in_array($rolUsuario, $rolesPermitidos)) {
            // Si no tiene el rol permitido, redirigir al dashboard con un aviso
            header("Location: dashboard.php?error=rol_no_autorizado");
            exit();
        }
    }
}
?>