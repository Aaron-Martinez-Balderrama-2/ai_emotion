<?php
require_once 'core/colossus.php';

$db = fn_conexion_bd();

$nuevoHash = password_hash('123456', PASSWORD_DEFAULT);

$stmt = $db->prepare("
    UPDATE tb_usuarios
    SET password_hash = :hash
    WHERE email = 'admin@gmail.com'
");

$stmt->execute(['hash' => $nuevoHash]);

echo "Contraseña actualizada correctamente";