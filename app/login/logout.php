<?php
// app/login/logout.php
require_once '../../core/auth.php';

// Cerramos sesión
fn_logout();

// Redirigimos al login
header("Location: index.php");
exit;
?>
