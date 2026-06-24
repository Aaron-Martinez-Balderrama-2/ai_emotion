<?php // Gestión de Roles y Permisos
require_once '../../core/auth.php';
fn_requerir_permiso('gestionar_usuarios'); // Requiere permisos administrativos
fn_cabecera_sistema("Gestión de Roles");

function fn_negocio_roles() {
    $db = fn_conexion_bd();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acc'])) {
        $id = (int)($_POST['inv_id'] ?? 0);
        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $permisos = $_POST['permisos'] ?? [];

        if ($_POST['acc'] == 0) { // Nuevo o Editar
            try {
                $db->beginTransaction();
                if ($id == 0) {
                    $stmt = $db->prepare("INSERT INTO tb_perfiles (nombre, descripcion) VALUES (?, ?)");
                    $stmt->execute([$nombre, $descripcion]);
                    $id = $db->lastInsertId();
                } else {
                    $stmt = $db->prepare("UPDATE tb_perfiles SET nombre = ?, descripcion = ? WHERE id = ?");
                    $stmt->execute([$nombre, $descripcion, $id]);
                }
                
                // Actualizar permisos
                $db->prepare("DELETE FROM tb_perfil_permisos WHERE id_perfil = ?")->execute([$id]);
                $stmt_p = $db->prepare("INSERT INTO tb_perfil_permisos (id_perfil, id_permiso) VALUES (?, ?)");
                foreach ($permisos as $p_id) {
                    $stmt_p->execute([$id, $p_id]);
                }
                
                $db->commit();
                die('<script>alert("Rol guardado exitosamente.");window.location.href="?";</script>');
            } catch (Exception $e) {
                $db->rollBack();
                die('<script>alert("Error: ' . $e->getMessage() . '");window.location.href="?";</script>');
            }
        } elseif ($_POST['acc'] == 2) { // Eliminar
            try {
                $db->prepare("DELETE FROM tb_perfiles WHERE id = ?")->execute([$id]);
                die('<script>alert("Rol eliminado exitosamente.");window.location.href="?";</script>');
            } catch (Exception $e) {
                die('<script>alert("Error al eliminar. Puede que haya usuarios asignados a este rol.");window.location.href="?";</script>');
            }
        }
    }
}

function fn_formulario_roles($id = 0) {
    $db = fn_conexion_bd();
    $resultado = ['nombre' => '', 'descripcion' => ''];
    $mis_permisos = [];
    
    if ($id > 0) {
        $stmt = $db->prepare("SELECT * FROM tb_perfiles WHERE id = ?");
        $stmt->execute([$id]);
        $resultado = $stmt->fetch();
        
        $stmt_p = $db->prepare("SELECT id_permiso FROM tb_perfil_permisos WHERE id_perfil = ?");
        $stmt_p->execute([$id]);
        $mis_permisos = $stmt_p->fetchAll(PDO::FETCH_COLUMN);
    }
    
    $todos_permisos = $db->query("SELECT * FROM tb_permisos ORDER BY descripcion")->fetchAll();
    ?>
    <form method="post" action="" autocomplete="off">
        <input type="hidden" name="inv_id" value="<?= $id ?>">
        <input type="hidden" name="tip" value="2">
        <div class="g_form_colossus">
            <div class="g_cabeza"><i class="fas fa-user-shield"></i>
                <div>
                    <h2><?= $id > 0 ? 'Editar Rol' : 'Nuevo Rol' ?></h2>
                </div>
                <a href="?" title="Cerrar"><i class="fas fa-times"></i></a>
            </div>
            <div class="g_cuerpo">
                <div class="g_fila">
                    <div>
                        <label class="g_lab">Nombre del Rol</label>
                        <input type="text" class="g_in" name="nombre" value="<?= htmlspecialchars($resultado['nombre']) ?>" required>
                    </div>
                </div>
                <div class="g_fila">
                    <div>
                        <label class="g_lab">Descripción</label>
                        <input type="text" class="g_in" name="descripcion" value="<?= htmlspecialchars($resultado['descripcion']) ?>">
                    </div>
                </div>
                
                <section class="subtitulo"><i class="fas fa-key"></i> Permisos Asignados</section>
                <div style="display:flex; flex-wrap:wrap; gap:15px; padding:10px;">
                    <?php foreach ($todos_permisos as $p): ?>
                        <label style="display:flex; align-items:center; gap:5px; background:#f9f9f9; padding:8px 12px; border-radius:5px; border:1px solid #ddd; cursor:pointer;">
                            <input type="checkbox" name="permisos[]" value="<?= $p['id'] ?>" <?= in_array($p['id'], $mis_permisos) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($p['descripcion']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="g_pie">
                <?php if ($id > 0 && $id != 1): // No eliminar Admin Supremo ?>
                    <button class="g_boton g_btn_peligro" name="acc" value="2" onclick="return confirm('¿Eliminar?')"><i class="fas fa-trash"></i> Borrar</button>
                <?php endif; ?>
                <button class="g_boton g_btn_primario" name="acc" value="0"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </div>
    </form>
    <?php
}

function fn_grilla_roles() {
    $db = fn_conexion_bd();
    $resultados = $db->query("SELECT * FROM tb_perfiles ORDER BY id ASC")->fetchAll();
    ?>
    <form method="POST" action="" id="frm_n">
        <input type="hidden" name="id_n" id="id_n" value="">
        <input type="hidden" name="tip" id="tip" value="">
    </form>

    <div class="g_cabeza_busqueda">
        <h2><i class="fas fa-shield-alt"></i> Roles y Permisos</h2>
        <a href="javascript:void(0)" onclick="id_n.value=''; tip.value='1'; frm_n.submit();" class="g_btn_nuevo">
            <i class="fas fa-plus"></i> Nuevo Rol
        </a>
    </div>

    <div class="g_grilla_colossus">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Rol</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><strong><?= htmlspecialchars($r['nombre']) ?></strong></td>
                    <td><?= htmlspecialchars($r['descripcion']) ?></td>
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
    case 0: fn_grilla_roles(); break;
    case 1: fn_formulario_roles((int)$_POST['id_n']); break;
    case 2: fn_negocio_roles(); break;
}

fn_pie_sistema();
?>
