<?php
// app/login/index.php
require_once '../../core/auth.php';

// Si ya está logueado, redirigir al dashboard (videos)
if (isset($_SESSION['usuario_id'])) {
    header("Location: ../videos/");
    exit;
}

$error_msg = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (fn_login($email, $password)) {
        header("Location: ../videos/");
        exit;
    } else {
        $error_msg = 'Credenciales incorrectas. Intenta nuevamente.';
    }
} else if (isset($_GET['error']) && $_GET['error'] === 'acceso_denegado') {
    $error_msg = 'Debes iniciar sesión para acceder a esa página.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Antigravity AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/colossus.css">
    <style>
        /* Pequeño ajuste para centrar el form en toda la pantalla usando estilos existentes */
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, var(--fondo) 0%, #e0e7ff 100%);
        }
        .g_form_colossus {
            width: 100%;
            margin: 0 1rem;
        }
        .alert_error {
            background: #fee2e2;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 600;
        }
        .form_group {
            margin-bottom: 1.5rem;
        }
        .form_group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--texto);
        }
        .login_logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login_logo i {
            font-size: 3rem;
            color: var(--primario);
            margin-bottom: 0.5rem;
        }
        .login_logo h1 {
            font-size: 1.8rem;
            color: var(--texto);
        }
        .login_logo h1 span {
            color: var(--primario);
        }
        .btn_full {
            width: 100%;
            justify-content: center;
        }
    </style>
</head>
<body>

    <div class="g_form_colossus">
        <div class="g_cuerpo">
            <div class="login_logo">
                <i class="fas fa-microchip"></i>
                <h1>Antigravity <span>AI</span></h1>
                <p style="color: #64748b;">Ingresa a tu cuenta</p>
            </div>

            <?php if ($error_msg): ?>
                <div class="alert_error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form_group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" class="g_in" placeholder="admin@gmail.com" required autofocus>
                </div>
                
                <div class="form_group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="g_in" placeholder="••••••••" required>
                </div>

                <button type="submit" class="g_boton g_btn_primario btn_full">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>
        </div>
    </div>

</body>
</html>
