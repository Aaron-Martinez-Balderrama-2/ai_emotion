<?php // Diccionario de Entrenamiento IA
require_once '../../core/colossus.php';
fn_cabecera_sistema("Diccionario de Entrenamiento");

function fn_negocio_diccionario()
{
    $conexion_bd = fn_conexion_bd();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acc'])) {
        $post_cod = (int) ($_POST['inv_id'] ?? 0);
        $get_app = isset($_GET['app']) ? '?app=' . $_GET['app'] : '';

        switch ($_POST['acc']) {
            case 0: // Insertar
                try {
                    $datos_insert = [$_POST['v_palabra'], $_POST['v_categoria'], (int)$_POST['v_peso']];
                    $sql = "INSERT INTO tb_diccionario (palabra, categoria, peso) VALUES (?,?,?)";
                    $conexion_bd->prepare($sql)->execute($datos_insert);

                    die('<script>alert("Palabra registrada.");window.location.href = "/antigravity/ai_emotion/app/diccionario/' . $get_app . '";</script>');
                } catch (PDOException $e) {
                    die('<script>alert("Error: ' . $e->getMessage() . '");window.location.href = "/antigravity/ai_emotion/app/diccionario/' . $get_app . '";</script>');
                }
                break;

            case 1: // Editar
                try {
                    $datos_update = [$_POST['v_palabra'], $_POST['v_categoria'], (int)$_POST['v_peso'], $post_cod];
                    $sql = "UPDATE tb_diccionario SET palabra=?, categoria=?, peso=? WHERE cod=?";
                    $conexion_bd->prepare($sql)->execute($datos_update);

                    die('<script>alert("Palabra actualizada.");window.location.href = "/antigravity/ai_emotion/app/diccionario/' . $get_app . '";</script>');
                } catch (PDOException $e) {
                    die('<script>alert("Error al modificar.");window.location.href = "/antigravity/ai_emotion/app/diccionario/' . $get_app . '";</script>');
                }
                break;

            case 2: // Eliminar
                try {
                    $sql = "DELETE FROM tb_diccionario WHERE cod = ?";
                    $conexion_bd->prepare($sql)->execute([$post_cod]);

                    die('<script>alert("Palabra eliminada.");window.location.href = "/antigravity/ai_emotion/app/diccionario/' . $get_app . '";</script>');
                } catch (PDOException $e) {
                    die('<script>alert("Error al eliminar.");window.location.href = "/antigravity/ai_emotion/app/diccionario/' . $get_app . '";</script>');
                }
                break;
        }
    }
}

function fn_formulario_diccionario($cod = 0)
{
    if ($cod > 0) {
        $conexion_bd = fn_conexion_bd();
        $stmt = $conexion_bd->prepare("SELECT * FROM tb_diccionario WHERE cod = ?");
        $stmt->execute([$cod]);
        $resultado = $stmt->fetch();
    }
    ?>
    <form id="frmDiccionario" method="post" action="" autocomplete="off">
        <input type="hidden" name="inv_id" id="inv_id" value="<?= $resultado['cod'] ?? '' ?>">
        <input type="hidden" name="tip" id="tip" value="2">
        <div class="g_form_colossus">
            <div class="g_cabeza"><i class="fa-solid fa-brain"></i>
                <div>
                    <h2><?= $cod ? 'Editar' : 'Nueva' ?> Palabra</h2>
                    <span>Entrenamiento de IA Local</span>
                </div>
                <a href="#" onclick="window.location.href=window.location.pathname" title="Cerrar"><i class="fas fa-times"></i></a>
            </div>
            <div class="g_cuerpo">
                <section class="subtitulo"><i class="fas fa-tag"></i> Datos de la palabra</section>
                <div class="g_fila g_reloj_1 g_antig_3 g_movil_3 g_table_3 g_escri_3 g_grand_3">
                    <div>
                        <label class="g_lab">Palabra / Frase</label>
                        <div class="g_ico"><i class="fas fa-font"></i>
                            <input type="text" class="g_in" name="v_palabra" value="<?= $resultado['palabra'] ?? '' ?>" placeholder="Ej: Esclavitud" required>
                        </div>
                    </div>
                    <div>
                        <label class="g_lab">Categoría</label>
                        <div class="g_ico"><i class="fas fa-list"></i>
                            <select name="v_categoria" class="g_in">
                                <option value="buena" <?= ($resultado['categoria'] ?? '') == 'buena' ? 'selected' : '' ?>>Buena</option>
                                <option value="mala" <?= ($resultado['categoria'] ?? '') == 'mala' ? 'selected' : '' ?>>Mala</option>
                                <option value="neutra" <?= ($resultado['categoria'] ?? '') == 'neutra' ? 'selected' : '' ?>>Neutra</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="g_lab">Peso (-100 a 100)</label>
                        <div class="g_ico"><i class="fas fa-weight-hanging"></i>
                            <input type="number" class="g_in" name="v_peso" value="<?= $resultado['peso'] ?? 0 ?>" placeholder="0">
                        </div>
                    </div>
                </div>
            </div>
            <div class="g_pie">
                <?= $cod > 0 ? '
                <button class="g_boton g_btn_primario" name="acc" value=1><i class="fas fa-save"></i> Guardar</button>
                <button class="g_boton g_btn_peligro" name="acc" value=2 onclick="return confirm(\'¿Eliminar esta palabra?\')"><i class="fas fa-trash"></i> Borrar</button>
                ' : '
                <button class="g_boton g_btn_primario" name=acc value=0><i class="fas fa-save"></i> Guardar</button>'; ?>
            </div>
        </div>
    </form>
    <?php
}

function fn_grilla_diccionario()
{
    $conexion_bd = fn_conexion_bd();

    $buscar = htmlspecialchars($_REQUEST['b'] ?? '');
    $pag_actual = (int) ($_REQUEST['p'] ?? 1);
    $pag_max_filas = 10;
    $pag_inicio = ($pag_actual - 1) * $pag_max_filas;

    $params = ["%$buscar%", "%$buscar%"];
    $sql_where = "WHERE palabra LIKE ? OR categoria LIKE ?";

    $sql_total = "SELECT COUNT(*) FROM tb_diccionario $sql_where";
    $stmt_total = $conexion_bd->prepare($sql_total);
    $stmt_total->execute($params);
    $filas_tot = $stmt_total->fetch(PDO::FETCH_NUM);
    $pags_tot = ceil($filas_tot[0] / $pag_max_filas);

    $sql_res = "SELECT * FROM tb_diccionario $sql_where ORDER BY cod DESC LIMIT $pag_max_filas OFFSET $pag_inicio";
    $stmt_res = $conexion_bd->prepare($sql_res);
    $stmt_res->execute($params);
    $resultados = $stmt_res->fetchAll();

    ?>
    <div class="g_grilla_colossus">
        <div class="g_cabeza">
            <form class="g_buscar" action="" method="POST" id="frm_b">
                <input type="hidden" name="p" id="p" value="1">
                <input type="search" name="b" placeholder="Buscar palabra..." value="<?= $buscar ?>" onsearch="if(this.value=='')this.form.submit();">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
            <div class="g_accion">
                <button><i class="fas fa-filter"></i> <span>Filtrar</span></button>
                <form method="POST" action="" id=frm_n>
                    <input type="hidden" name="id_n" id="id_n" value="">
                    <input type="hidden" name="tip" id="tip" value="1">
                    <button><i class="fas fa-plus"></i> <span>Nueva</span></button>
                </form>
            </div>
        </div>

        <div class="g_cuerpo">
            <table>
                <thead>
                    <tr>
                        <th>Palabra</th>
                        <th>Categoría</th>
                        <th>Peso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $f): ?>
                        <tr>
                            <td><?= $f['palabra'] ?></td>
                            <td><span class="badge badge-<?= $f['categoria'] ?>"><?= ucfirst($f['categoria']) ?></span></td>
                            <td><?= $f['peso'] ?></td>
                            <td>
                                <button onclick="id_n.value='<?= $f['cod'] ?>'; frm_n.submit();"><i class="fas fa-edit"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="g_pie">
                <?php
                $p_ini = max(1, min($pag_actual - 1, max(1, $pags_tot - 2)));
                $p_fin = min($pags_tot, $p_ini + 2);
                echo '<span>' . ($pag_inicio + 1) . '-' . (min($pag_inicio + $pag_max_filas, $filas_tot[0])) . ' de ' . $filas_tot[0] . '</span><div>';
                if ($pag_actual > 1) echo "<a href='javascript:void(0)' onclick='fn_paginar(1)'>««</a> <a href='javascript:void(0)' onclick='fn_paginar(" . ($pag_actual - 1) . ")'><i class='fas fa-chevron-left'></i></a> ";
                for ($i = $p_ini; $i <= $p_fin; $i++) {
                    echo "<a href='javascript:void(0)' onclick='fn_paginar($i)' " . ($i == $pag_actual ? 'style="font-weight:bold"' : '') . ">$i</a> ";
                }
                if ($pag_actual < $pags_tot) echo "<a href='javascript:void(0)' onclick='fn_paginar(" . ($pag_actual + 1) . ")'><i class='fas fa-chevron-right'></i></a> <a href='javascript:void(0)' onclick='fn_paginar($pags_tot)'>»»</a>";
                echo '</div>';
                ?>
            </div>
        </div>
    </div>
    <script>
        function fn_paginar(pagina) {
            document.getElementById('p').value = pagina;
            document.getElementById('frm_b').submit();
        }
    </script>
    <?php
}

function fn_activar_diccionario()
{
    switch ($_POST['tip'] ?? 0) {
        case 0: fn_grilla_diccionario(); break;
        case 1: fn_formulario_diccionario($_POST['id_n']); break;
        case 2: fn_negocio_diccionario(); break;
    }
}

fn_activar_diccionario();
fn_pie_sistema();
?>
