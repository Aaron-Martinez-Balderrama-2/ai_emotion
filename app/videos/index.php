<?php // Entrada de Videos TikTok
require_once '../../core/colossus.php';
fn_cabecera_sistema("Gestión de Videos");

function fn_negocio_video()
{
    $conexion_bd = fn_conexion_bd();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acc'])) {
        $post_cod = (int) ($_POST['inv_id'] ?? 0);
        $get_app = isset($_GET['app']) ? '?app=' . $_GET['app'] : '';

        switch ($_POST['acc']) {
            case 0: // Registrar Video
                try {
                    $url = $_POST['v_url'];
                    // Validación básica de URL de TikTok
                    if (strpos($url, 'tiktok.com') === false) {
                        die('<script>alert("URL de TikTok no válida.");window.location.href = "/antigravity/ai_emotion/app/videos/' . $get_app . '";</script>');
                    }

                    $sql = "INSERT INTO tb_videos (url_tiktok, estado) VALUES (?, 'pendiente')";
                    $conexion_bd->prepare($sql)->execute([$url]);

                    // Disparar motor en segundo plano de forma asincrónica
                    $host = $_SERVER['HTTP_HOST'];
                    $ruta = "/antigravity/ai_emotion/engine/trigger.php";
                    $fp = @fsockopen($host, 80, $errno, $errstr, 1);
                    if ($fp) {
                        $out = "GET $ruta HTTP/1.1\r\n";
                        $out .= "Host: $host\r\n";
                        $out .= "Connection: Close\r\n\r\n";
                        fwrite($fp, $out);
                        fclose($fp);
                    }

                    die('<script>alert("Video registrado. El procesamiento iniciará pronto.");window.location.href = "/antigravity/ai_emotion/app/videos/' . $get_app . '";</script>');
                } catch (PDOException $e) {
                    die('<script>alert("Error: ' . $e->getMessage() . '");window.location.href = "/antigravity/ai_emotion/app/videos/' . $get_app . '";</script>');
                }
                break;

            case 2: // Eliminar Video
                try {
                    $sql = "DELETE FROM tb_videos WHERE cod = ?";
                    $conexion_bd->prepare($sql)->execute([$post_cod]);
                    die('<script>alert("Registro eliminado.");window.location.href = "/antigravity/ai_emotion/app/videos/' . $get_app . '";</script>');
                } catch (PDOException $e) {
                    die('<script>alert("Error al eliminar.");window.location.href = "/antigravity/ai_emotion/app/videos/' . $get_app . '";</script>');
                }
                break;
        }
    }
}

function fn_formulario_video($cod = 0)
{
    if ($cod > 0) {
        $conexion_bd = fn_conexion_bd();
        $stmt = $conexion_bd->prepare("SELECT * FROM tb_videos WHERE cod = ?");
        $stmt->execute([$cod]);
        $resultado = $stmt->fetch();
    }
    ?>
    <form id="frmVideo" method="post" action="" autocomplete="off">
        <input type="hidden" name="inv_id" id="inv_id" value="<?= $resultado['cod'] ?? '' ?>">
        <input type="hidden" name="tip" id="tip" value="2">
        <div class="g_form_colossus">
            <div class="g_cabeza"><i class="fa-brands fa-tiktok"></i>
                <div>
                    <h2>Nuevo Análisis</h2>
                    <span>Procesamiento de Video TikTok</span>
                </div>
                <a href="#" onclick="window.location.href=window.location.pathname" title="Cerrar"><i class="fas fa-times"></i></a>
            </div>
            <div class="g_cuerpo">
                <section class="subtitulo"><i class="fas fa-link"></i> Enlace del Video</section>
                <div class="g_fila g_reloj_1 g_antig_1 g_movil_1 g_table_1 g_escri_1 g_grand_1">
                    <div>
                        <label class="g_lab">URL de TikTok</label>
                        <div class="g_ico"><i class="fas fa-video"></i>
                            <input type="url" class="g_in" name="v_url" value="<?= $resultado['url_tiktok'] ?? '' ?>" placeholder="https://www.tiktok.com/@usuario/video/..." required <?= $cod ? 'readonly' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>
            <div class="g_pie">
                <?= $cod > 0 ? '
                <button class="g_boton g_btn_peligro" name="acc" value=2 onclick="return confirm(\'¿Eliminar este registro?\')"><i class="fas fa-trash"></i> Borrar</button>
                ' : '
                <button class="g_boton g_btn_primario" name=acc value=0><i class="fas fa-bolt"></i> Analizar</button>'; ?>
            </div>
        </div>
    </form>
    <?php
}

function fn_grilla_video()
{
    $conexion_bd = fn_conexion_bd();

    $buscar = htmlspecialchars($_REQUEST['b'] ?? '');
    $pag_actual = (int) ($_REQUEST['p'] ?? 1);
    $pag_max_filas = 10;
    $pag_inicio = ($pag_actual - 1) * $pag_max_filas;

    $params = ["%$buscar%", "%$buscar%"];
    $sql_where = "WHERE url_tiktok LIKE ? OR titulo LIKE ?";

    $sql_total = "SELECT COUNT(*) FROM tb_videos $sql_where";
    $stmt_total = $conexion_bd->prepare($sql_total);
    $stmt_total->execute($params);
    $filas_tot = $stmt_total->fetch(PDO::FETCH_NUM);
    $pags_tot = ceil($filas_tot[0] / $pag_max_filas);

    $sql_res = "SELECT * FROM tb_videos $sql_where ORDER BY cod DESC LIMIT $pag_max_filas OFFSET $pag_inicio";
    $stmt_res = $conexion_bd->prepare($sql_res);
    $stmt_res->execute($params);
    $resultados = $stmt_res->fetchAll();

    ?>
    <form method="POST" action="" id="frm_b">
        <input type="hidden" name="p" id="p" value="1">
        <div class="g_cabeza_busqueda">
            <div class="g_buscador_wrapper">
                <i class="fas fa-search"></i>
                <input type="text" name="b" value="<?= $buscar ?>" class="g_buscador" placeholder="Buscar por título o URL..." onsearch="if(this.value=='')this.form.submit();">
            </div>
            
            <a href="javascript:void(0)" onclick="id_n.value=''; tip.value='1'; frm_n.submit();" class="g_btn_nuevo">
                <i class="fas fa-plus-circle"></i> Nuevo Análisis
            </a>
        </div>
    </form>

    <form method="POST" action="" id="frm_n">
        <input type="hidden" name="id_n" id="id_n" value="">
        <input type="hidden" name="tip" id="tip" value="">
    </form>

    <div class="g_grilla_colossus">
        <div class="g_cabeza">
            <h3><i class="fas fa-list"></i> Historial de Análisis</h3>
        </div>

        <div class="g_cuerpo">
            <table>
                <thead>
                    <tr>
                        <th>Video / URL</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $f): ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <?php if($f['thumbnail']): ?>
                                        <img src="<?= $f['thumbnail'] ?>" style="width:40px; height:40px; border-radius:5px; object-fit:cover;">
                                    <?php else: ?>
                                        <div style="width:40px; height:40px; background:#eee; border-radius:5px; display:flex; align-items:center; justify-content:center;"><i class="fa-brands fa-tiktok"></i></div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= $f['titulo'] ?? 'Procesando...' ?></strong><br>
                                        <small style="color:gray;"><?= substr($f['url_tiktok'], 0, 40) ?>...</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if($f['estado'] == 'procesando'): ?>
                                    <div class="progress_container">
                                        <div class="progress_bar">
                                            <div class="progress_fill" style="width: <?= $f['progreso'] ?>%;"></div>
                                        </div>
                                        <div class="progress_info">
                                            <strong><?= $f['progreso'] ?>%</strong>
                                            <small><?= $f['paso_actual'] ?></small>
                                        </div>
                                    </div>
                                <?php elseif($f['estado'] == 'pendiente'): ?>
                                    <span class="badge badge-pendiente"><i class="fas fa-clock"></i> En cola</span>
                                <?php else: ?>
                                    <span class="badge badge-<?= $f['estado'] ?>"><?= ucfirst($f['estado']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($f['fecha_registro'])) ?></td>
                            <td>
                                <button onclick="id_n.value='<?= $f['cod'] ?>'; frm_n.submit();"><i class="fas fa-eye"></i></button>
                                <?php if($f['estado'] == 'completado'): ?>
                                    <button onclick="window.location.href='../reportes/?id=<?= $f['cod'] ?>'"><i class="fas fa-chart-line"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="g_pie">
                <?php
                echo '<span>' . ($pag_inicio + 1) . '-' . (min($pag_inicio + $pag_max_filas, $filas_tot[0])) . ' de ' . $filas_tot[0] . '</span><div>';
                if ($pag_actual > 1) echo "<a href='javascript:void(0)' onclick='fn_paginar(" . ($pag_actual - 1) . ")'><i class='fas fa-chevron-left'></i></a> ";
                if ($pag_actual < $pags_tot) echo "<a href='javascript:void(0)' onclick='fn_paginar(" . ($pag_actual + 1) . ")'><i class='fas fa-chevron-right'></i></a> ";
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

function fn_activar_video()
{
    switch ($_POST['tip'] ?? 0) {
        case 0: fn_grilla_video(); break;
        case 1: fn_formulario_video($_POST['id_n']); break;
        case 2: fn_negocio_video(); break;
    }
}

fn_activar_video();
fn_pie_sistema();
?>
