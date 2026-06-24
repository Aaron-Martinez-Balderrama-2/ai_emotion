<?php // Gestión de Usuarios
require_once '../../core/auth.php';
fn_requerir_permiso('gestionar_usuarios');
fn_cabecera_sistema("Gestión de Usuarios");

function fn_negocio_usuarios() {
    $db = fn_conexion_bd();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acc'])) {
        $id = (int)($_POST['inv_id'] ?? 0);
        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $id_perfil = (int)($_POST['id_perfil'] ?? 0);
        $password = $_POST['password'] ?? '';

        if ($_POST['acc'] == 0) { // Nuevo o Editar
            try {
                if ($id == 0) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("INSERT INTO tb_usuarios (nombre, email, password_hash, id_perfil) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nombre, $email, $hash, $id_perfil]);
                } else {
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $db->prepare("UPDATE tb_usuarios SET nombre = ?, email = ?, password_hash = ?, id_perfil = ? WHERE id = ?");
                        $stmt->execute([$nombre, $email, $hash, $id_perfil, $id]);
                    } else {
                        $stmt = $db->prepare("UPDATE tb_usuarios SET nombre = ?, email = ?, id_perfil = ? WHERE id = ?");
                        $stmt->execute([$nombre, $email, $id_perfil, $id]);
                    }
                }
                die('<script>alert("Usuario guardado exitosamente.");window.location.href="?";</script>');
            } catch (Exception $e) {
                die('<script>alert("Error: ' . $e->getMessage() . '");window.location.href="?";</script>');
            }
        } elseif ($_POST['acc'] == 2) { // Eliminar
            try {
                $db->prepare("DELETE FROM tb_usuarios WHERE id = ?")->execute([$id]);
                die('<script>alert("Usuario eliminado exitosamente.");window.location.href="?";</script>');
            } catch (Exception $e) {
                die('<script>alert("Error al eliminar.");window.location.href="?";</script>');
            }
        }
    }
}

function fn_formulario_usuarios($id = 0) {
    $db = fn_conexion_bd();
    $resultado = ['nombre' => '', 'email' => '', 'id_perfil' => ''];
    if ($id > 0) {
        $stmt = $db->prepare("SELECT * FROM tb_usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $resultado = $stmt->fetch();
    }
    
    $perfiles = $db->query("SELECT * FROM tb_perfiles ORDER BY nombre")->fetchAll();
    ?>
    <form method="post" action="" autocomplete="off">
        <input type="hidden" name="inv_id" value="<?= $id ?>">
        <input type="hidden" name="tip" value="2">
        <div class="g_form_colossus">
            <div class="g_cabeza"><i class="fas fa-user"></i>
                <div>
                    <h2><?= $id > 0 ? 'Editar Usuario' : 'Nuevo Usuario' ?></h2>
                </div>
                <a href="?" title="Cerrar"><i class="fas fa-times"></i></a>
            </div>
            <div class="g_cuerpo">
                <div class="g_fila">
                    <div>
                        <label class="g_lab">Nombre Completo</label>
                        <input type="text" class="g_in" name="nombre" value="<?= htmlspecialchars($resultado['nombre']) ?>" required>
                    </div>
                    <div>
                        <label class="g_lab">Correo Electrónico</label>
                        <input type="email" class="g_in" name="email" value="<?= htmlspecialchars($resultado['email']) ?>" required>
                    </div>
                </div>
                <div class="g_fila">
                    <div>
                        <label class="g_lab">Rol (Perfil)</label>
                        <select class="g_in" name="id_perfil" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($perfiles as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $resultado['id_perfil'] == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="g_lab">Contraseña <?= $id > 0 ? '(Dejar en blanco para no cambiar)' : '' ?></label>
                        <input type="password" class="g_in" name="password" <?= $id == 0 ? 'required' : '' ?>>
                    </div>
                </div>
            </div>
            <div class="g_pie">
                <?php if ($id > 0 && $id != $_SESSION['usuario_id']): ?>
                    <button class="g_boton g_btn_peligro" name="acc" value="2" onclick="return confirm('¿Eliminar?')"><i class="fas fa-trash"></i> Borrar</button>
                <?php endif; ?>
                <button class="g_boton g_btn_primario" name="acc" value="0"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </div>
    </form>
    <?php
}

function fn_grilla_usuarios() {
    $db = fn_conexion_bd();
    $resultados = $db->query("SELECT u.*, p.nombre as perfil FROM tb_usuarios u LEFT JOIN tb_perfiles p ON u.id_perfil = p.id ORDER BY u.id DESC")->fetchAll();
    ?>
    <form method="POST" action="" id="frm_n">
        <input type="hidden" name="id_n" id="id_n" value="">
        <input type="hidden" name="tip" id="tip" value="">
    </form>

    <div class="g_cabeza_busqueda">
        <h2><i class="fas fa-users"></i> Directorio de Usuarios</h2>
        <a href="javascript:void(0)" onclick="id_n.value=''; tip.value='1'; frm_n.submit();" class="g_btn_nuevo">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
        </a>
    </div>

    <div class="g_grilla_colossus">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol Asignado</th>
                    <th>Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $r): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['nombre']) ?></strong></td>
                    <td><?= htmlspecialchars($r['email']) ?></td>
                    <td><span class="badge badge-completado"><?= htmlspecialchars($r['perfil']) ?></span></td>
                    <td><?= date('d/m/Y', strtotime($r['fecha_registro'])) ?></td>
                    <td>
                        <button onclick="id_n.value='<?= $r['id'] ?>'; tip.value='1'; frm_n.submit();"><i class="fas fa-edit"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

switch ($_POST['tip'] ?? 0) {
    case 0: fn_grilla_usuarios(); break;
    case 1: fn_formulario_usuarios((int)$_POST['id_n']); break;
    case 2: fn_negocio_usuarios(); break;
}

fn_pie_sistema();
?>
