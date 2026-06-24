<?php
// core/auth.php - Motor de Seguridad y Autenticación
require_once __DIR__ . '/colossus.php';

// Iniciar la sesión de forma segura si no ha sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configuraciones de seguridad para la cookie de sesión
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

/**
 * Inicia sesión verificando credenciales
 */
function fn_login($email, $password) {
    $db = fn_conexion_bd();
    $stmt = $db->prepare("SELECT id, nombre, email, password_hash, id_perfil FROM tb_usuarios WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($password, $usuario['password_hash'])) {
        // Prevenir fijación de sesión
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_perfil'] = $usuario['id_perfil'];

        return true;
    }
    return false;
}

/**
 * Cierra la sesión de forma segura
 */
function fn_logout() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Verifica si el usuario actual tiene un permiso específico (Acceso Funcional)
 */
function fn_tiene_permiso($llave_permiso) {
    if (!isset($_SESSION['usuario_perfil'])) {
        return false;
    }

    $id_perfil = $_SESSION['usuario_perfil'];
    $db = fn_conexion_bd();

    $stmt = $db->prepare("
        SELECT 1 
        FROM tb_perfil_permisos pp
        JOIN tb_permisos p ON pp.id_permiso = p.id
        WHERE pp.id_perfil = :id_perfil AND p.llave_permiso = :llave_permiso
        LIMIT 1
    ");
    $stmt->execute([
        'id_perfil' => $id_perfil,
        'llave_permiso' => $llave_permiso
    ]);

    return $stmt->rowCount() > 0;
}

/**
 * Redirige al login si el usuario no tiene el permiso requerido
 */
function fn_requerir_permiso($llave_permiso) {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: /antigravity/ai_emotion/app/login/");
        exit;
    }

    if (!fn_tiene_permiso($llave_permiso)) {
        header("Location: /antigravity/ai_emotion/app/login/?error=acceso_denegado");
        exit;
    }
}
?>
