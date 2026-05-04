<?php // Reporte de Análisis y Marketing
require_once '../../core/colossus.php';
fn_cabecera_sistema("Reporte de Inteligencia");

function fn_vista_reporte($video_id)
{
    $db = fn_conexion_bd();
    
    // 1. Obtener Datos
    $stmt_v = $db->prepare("SELECT * FROM tb_videos WHERE cod = ?");
    $stmt_v->execute([$video_id]);
    $video = $stmt_v->fetch();

    $plan = json_decode($video['analisis_ia'] ?? '{}', true);

    // 2. Usar Nube de Palabras de la IA
    $cloud_data = $plan['nube_pensamientos'] ?? [];
    ?>

    <div class="g_form_colossus report_container">
        <div class="g_cabeza"><i class="fas fa-chart-pie"></i>
            <div>
                <h2>Reporte de Inteligencia</h2>
                <span>Análisis de Impacto y Marketing</span>
            </div>
            <a href="../videos/" title="Volver"><i class="fas fa-arrow-left"></i></a>
        </div>

        <div class="g_cuerpo">
            <!-- Header del Video -->
            <div class="video_header">
                <img src="<?= $video['thumbnail'] ?>" alt="Thumbnail">
                <div>
                    <h1><?= $video['titulo'] ?></h1>
                    <p><a href="<?= $video['url_tiktok'] ?>" target="_blank"><?= $video['url_tiktok'] ?></a></p>
                    <span class="badge badge-success">Procesado con IA</span>
                </div>
            </div>

            <hr>

            <?php if (isset($plan['error'])): ?>
                <div class="m_card m_danger" style="border-left: 10px solid #f44336; background: #fff5f5;">
                    <h3 style="color: #d32f2f;"><i class="fas fa-exclamation-triangle"></i> ERROR DE LA IA</h3>
                    <p style="color: #b71c1c; font-weight: bold;"><?= $plan['error'] ?></p>
                    <p style="font-size: 0.8em; margin-top: 10px;"><strong>Nota del Doctor:</strong> Parece que Ollama no está respondiendo o el modelo no está cargado. Asegúrate de tener la app abierta.</p>
                </div>
            <?php endif; ?>

            <div class="g_fila g_reloj_1 g_antig_2 g_movil_1 g_table_2 g_escri_2 g_grand_2">

                <!-- Columna Izquierda: Nube de Palabras y Detonantes -->
                <div>
                    <section class="subtitulo"><i class="fas fa-cloud"></i> Nube de Palabras (Comentarios)</section>
                    <div id="word_cloud" style="width:100%; height:300px; background:#f9f9f9; border-radius:10px;"></div>
                    
                    <section class="subtitulo" style="margin-top:20px;"><i class="fas fa-bomb"></i> Palabras Detonantes</section>
                    <div class="detonantes_list">
                        <?php foreach($plan['detonantes'] ?? [] as $det): ?>
                            <span class="det_item"><?= $det ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Columna Derecha: Recomendaciones de Marketing -->
                <div class="marketing_panel">
                    <section class="subtitulo"><i class="fas fa-bullhorn"></i> Plan de Marketing</section>
                    
                    <div class="m_card m_success">
                        <h3><i class="fas fa-check-circle"></i> PALABRAS A REPETIR</h3>
                        <p><?= isset($plan['repetir']) ? nl2br($plan['repetir']) : 'No hay datos suficientes.' ?></p>
                    </div>

                    <div class="m_card m_danger">
                        <h3><i class="fas fa-times-circle"></i> PALABRAS A EVITAR</h3>
                        <p><?= isset($plan['evitar']) ? nl2br($plan['evitar']) : 'No hay datos suficientes.' ?></p>
                    </div>



                    <div class="rentabilidad_box">
                        <h3>RENTABILIDAD ESTIMADA</h3>
                        <div class="score_bar">
                            <div class="score_fill" style="width: <?= ($plan['calificacion'] ?? 0) * 10 ?>%;"></div>
                        </div>
                        <p>
                            <?php 
                            if (isset($plan['rentabilidad'])) {
                                if (is_array($plan['rentabilidad'])) {
                                    foreach ($plan['rentabilidad'] as $k => $v) {
                                        echo "<strong>" . ucfirst(str_replace('_', ' ', $k)) . ":</strong> " . $v . "<br><br>";
                                    }
                                } else {
                                    echo nl2br($plan['rentabilidad']);
                                }
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <section class="subtitulo"><i class="fas fa-align-left"></i> Transcripción del Video</section>
            <div class="transcription_box">
                <?= nl2br($video['transcripcion']) ?>
            </div>
        </div>
    </div>

    <!-- Librerías para Nube de Palabras -->
    <script src="https://d3js.org/d3.v6.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/holtzy/D3-graph-gallery@master/LIB/d3.layout.cloud.js"></script>

    <script>
        const words = <?= json_encode($cloud_data) ?>;
        
        const margin = {top: 10, right: 10, bottom: 10, left: 10},
              width = document.getElementById('word_cloud').offsetWidth - margin.left - margin.right,
              height = 300 - margin.top - margin.bottom;

        const svg = d3.select("#word_cloud").append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
          .append("g")
            .attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");

        const layout = d3.layout.cloud()
            .size([width, height])
            .words(words.map(d => ({text: d.text, size: 15 + d.size * 2})))
            .padding(5)
            .rotate(() => (~~(Math.random() * 2) * 90))
            .fontSize(d => d.size)
            .on("end", draw);

        layout.start();

        function draw(words) {
            svg.selectAll("text")
                .data(words)
              .enter().append("text")
                .style("font-size", d => d.size + "px")
                .style("fill", (d, i) => d3.schemeTableau10[i % 10])
                .attr("text-anchor", "middle")
                .style("font-family", "Impact")
                .attr("transform", d => "translate(" + [d.x, d.y] + ")rotate(" + d.rotate + ")")
                .text(d => d.text);
        }
    </script>

    <style>
        .report_container { max-width: 1200px; margin: 0 auto; }
        .video_header { display: flex; gap: 20px; align-items: center; padding: 20px 0; }
        .video_header img { width: 120px; height: 120px; border-radius: 15px; object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .detonantes_list { display: flex; flex-wrap: wrap; gap: 8px; padding: 10px 0; }
        .det_item { background: #e3f2fd; color: #1976d2; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 0.9em; }
        .m_card { padding: 15px; border-radius: 10px; margin-bottom: 15px; border-left: 5px solid #ccc; }
        .m_success { background: #e8f5e9; border-color: #4caf50; }
        .m_danger { background: #ffebee; border-color: #f44336; }
        .m_info { background: #e0f7fa; border-color: #00bcd4; }
        .m_card h3 { font-size: 0.9em; margin-bottom: 8px; color: #333; }
        .rentabilidad_box { background: #333; color: #fff; padding: 20px; border-radius: 15px; text-align: center; }
        .score_bar { background: #555; height: 10px; border-radius: 5px; margin: 15px 0; overflow: hidden; }
        .score_fill { background: #4caf50; height: 100%; transition: width 1s ease-in-out; }
        .transcription_box { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #ddd; font-style: italic; color: #555; max-height: 300px; overflow-y: auto; }
    </style>
    <?php
}

// Lógica de control
$video_id = (int)($_REQUEST['id'] ?? 0);
if ($video_id > 0) {
    fn_vista_reporte($video_id);
} else {
    // Dashboard General de Reportes
    ?>
    <div class="g_cabeza_busqueda">
        <h2><i class="fas fa-chart-line"></i> Dashboard de Inteligencia</h2>
        <p>Selecciona un video procesado para ver sus métricas.</p>
    </div>
    
    <div class="g_grilla_colossus">
        <table>
            <thead>
                <tr>
                    <th>Video / Título</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $db = fn_conexion_bd();
                $stmt = $db->query("SELECT * FROM tb_videos WHERE estado = 'completado' ORDER BY fecha_registro DESC");
                while($v = $stmt->fetch()):
                ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <img src="<?= $v['thumbnail'] ?>" style="width:50px; height:50px; border-radius:8px; object-fit:cover;">
                            <span><?= $v['titulo'] ?></span>
                        </div>
                    </td>
                    <td><span class="badge badge-completado">Analizado</span></td>
                    <td>
                        <a href="?id=<?= $v['cod'] ?>" class="g_btn_primario" style="padding: 5px 15px; font-size: 0.8rem;">
                            <i class="fas fa-eye"></i> Ver Reporte
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php
}
fn_pie_sistema();
?>
