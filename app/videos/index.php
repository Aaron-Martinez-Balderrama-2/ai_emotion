<?php // Entrada de Videos TikTok
require_once '../../core/auth.php';
fn_requerir_permiso('ver_dashboard');
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
                    $candidato = trim($_POST['v_candidato'] ?? '');
                    $partido = trim($_POST['v_partido'] ?? '');

                    // Validación básica de URL de TikTok
                    if (strpos($url, 'tiktok.com') === false) {
                        die('<script>alert("URL de TikTok no válida.");window.location.href = "/antigravity/ai_emotion/app/videos/' . $get_app . '";</script>');
                    }
                    if (empty($candidato) || empty($partido)) {
                        die('<script>alert("Debes indicar el Candidato y el Partido.");window.location.href = "/antigravity/ai_emotion/app/videos/' . $get_app . '";</script>');
                    }

                    $sql = "INSERT INTO tb_videos (url_tiktok, candidato, partido, estado, id_usuario) VALUES (?, ?, ?, 'pendiente', ?)";
                    $conexion_bd->prepare($sql)->execute([$url, $candidato, $partido, $_SESSION['usuario_id']]);

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
    $conexion_bd = fn_conexion_bd();
    $resultado = null;

    if ($cod > 0) {
        $stmt = $conexion_bd->prepare("SELECT * FROM tb_videos WHERE cod = ?");
        $stmt->execute([$cod]);
        $resultado = $stmt->fetch();
    }

    // Obtener candidatos y partidos existentes para autocompletar
    $candidatos_existentes = $conexion_bd->query("SELECT DISTINCT candidato FROM tb_videos WHERE candidato IS NOT NULL AND candidato != '' ORDER BY candidato")->fetchAll(PDO::FETCH_COLUMN);
    $partidos_existentes = $conexion_bd->query("SELECT DISTINCT partido FROM tb_videos WHERE partido IS NOT NULL AND partido != '' ORDER BY partido")->fetchAll(PDO::FETCH_COLUMN);
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
                <div class="g_fila">
                    <div>
                        <label class="g_lab">URL de TikTok</label>
                        <div class="g_ico"><i class="fas fa-video"></i>
                            <input type="url" class="g_in" name="v_url" value="<?= $resultado['url_tiktok'] ?? '' ?>" placeholder="https://www.tiktok.com/@usuario/video/..." required <?= $cod ? 'readonly' : '' ?>>
                        </div>
                    </div>
                </div>

                <section class="subtitulo"><i class="fas fa-user-tie"></i> Información del Candidato</section>
                <div class="g_fila">
                    <div>
                        <label class="g_lab">Nombre del Candidato</label>
                        <div class="g_ico autocomplete_wrapper"><i class="fas fa-user"></i>
                            <input type="text" class="g_in custom_autocomplete" name="v_candidato" id="v_candidato" data-list="lista_candidatos" value="<?= htmlspecialchars($resultado['candidato'] ?? '') ?>" placeholder="Ej: Juan Carlos Medrano" required <?= $cod ? 'readonly' : '' ?> autocomplete="off">
                            <datalist id="lista_candidatos">
                                <?php foreach ($candidatos_existentes as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>
                    <div>
                        <label class="g_lab">Partido Político</label>
                        <div class="g_ico autocomplete_wrapper"><i class="fas fa-flag"></i>
                            <input type="text" class="g_in custom_autocomplete" name="v_partido" id="v_partido" data-list="lista_partidos" value="<?= htmlspecialchars($resultado['partido'] ?? '') ?>" placeholder="Ej: Creemos" required <?= $cod ? 'readonly' : '' ?> autocomplete="off">
                            <datalist id="lista_partidos">
                                <?php foreach ($partidos_existentes as $p): ?>
                                    <option value="<?= htmlspecialchars($p) ?>">
                                <?php endforeach; ?>
                            </datalist>
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

    $params = ["%$buscar%", "%$buscar%", "%$buscar%"];
    // OWD: Si es Admin (perfil 1), ve todo. Si no, solo los videos que subió el usuario actual.
    $filtro_usuario = ($_SESSION['usuario_perfil'] == 1) ? "1=1" : "id_usuario = " . (int)$_SESSION['usuario_id'];

    $sql_where = "WHERE (url_tiktok LIKE ? OR titulo LIKE ? OR candidato LIKE ?) AND ($filtro_usuario)";

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
                <input type="text" name="b" value="<?= $buscar ?>" class="g_buscador" placeholder="Buscar por título, URL o candidato..." onsearch="if(this.value=='')this.form.submit();">
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
                        <th>Candidato</th>
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
                                        <img src="<?= $f['thumbnail'] ?>" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode(!empty($f['candidato']) ? $f['candidato'] : 'TikTok') ?>&background=random';" style="width:40px; height:40px; border-radius:5px; object-fit:cover;">
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
                                <?php if(!empty($f['candidato'])): ?>
                                    <strong><?= htmlspecialchars($f['candidato']) ?></strong><br>
                                    <small style="color:#888;"><i class="fas fa-flag"></i> <?= htmlspecialchars($f['partido'] ?? '') ?></small>
                                <?php else: ?>
                                    <span style="color:#aaa;">—</span>
                                <?php endif; ?>
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
                                <?php if($f['estado'] == 'completado'): ?>
                                    <button onclick="window.location.href='../reportes/?id=<?= $f['cod'] ?>'" title="Ver Reporte"><i class="fas fa-chart-line"></i></button>
                                <?php endif; ?>
                                <button onclick="id_n.value='<?= $f['cod'] ?>'; tip.value='1'; frm_n.submit();" title="Editar / Eliminar"><i class="fas fa-edit"></i></button>
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
