<?php // Reporte de Análisis y Marketing
require_once '../../core/auth.php';
require_once '../../core/colossus.php';
fn_requerir_permiso('ver_reportes');
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
    
    // Datos de demografía
    $dem = $plan['demografia'] ?? ['hombres' => 50, 'mujeres' => 50, 'hombres_conteo' => 0, 'mujeres_conteo' => 0, 'no_detectados' => 0, 'total_comentarios' => 0, 'edad_promedio' => 'N/A'];
    $calificacion = $plan['calificacion'] ?? 0;
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
                    <?php if (!empty($video['candidato'])): ?>
                        <span class="badge" style="background:#1976d2; color:#fff; margin-left:5px;"><i class="fas fa-user-tie"></i> <?= htmlspecialchars($video['candidato']) ?></span>
                        <span class="badge" style="background:#ff9800; color:#fff;"><i class="fas fa-flag"></i> <?= htmlspecialchars($video['partido'] ?? '') ?></span>
                    <?php endif; ?>
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
                    <section class="subtitulo"><i class="fas fa-cloud"></i> Red Neuronal de Comentarios</section>
                    <div id="word_cloud" style="width:100%; height:400px; background:#f9f9f9; border-radius:10px;"></div>
                    
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
                    
                    <!-- Demografía con tooltips -->
                    <div class="m_card m_info">
                        <h3><i class="fas fa-users"></i> DEMOGRAFÍA ESTIMADA</h3>
                        <p style="font-size:0.75em; color:#666; margin-bottom:10px;"><i class="fas fa-info-circle"></i> <?= $dem['metodo'] ?? 'Inferencia por nombre de autor' ?> — Apunta con el cursor para ver detalle</p>
                        <div style="display:flex; justify-content: space-between; margin-top: 10px; text-align: center;">
                            <div class="demo_item" style="flex: 1; cursor:help;" title="<?= $dem['hombres_conteo'] ?? 0 ?> comentarios identificados como masculinos de <?= $dem['total_comentarios'] ?? 0 ?> totales">
                                <i class="fas fa-male" style="font-size: 2em; color: #1976d2;"></i><br>
                                <strong>Hombres</strong><br>
                                <span style="font-size:1.5em; font-weight:700; color:#1976d2;"><?= $dem['hombres'] ?? 50 ?>%</span><br>
                                <small style="color:#888;">(<?= $dem['hombres_conteo'] ?? 0 ?> detectados)</small>
                            </div>
                            <div class="demo_item" style="flex: 1; border-left: 1px solid #ccc; border-right: 1px solid #ccc; cursor:help;" title="<?= $dem['mujeres_conteo'] ?? 0 ?> comentarios identificados como femeninos de <?= $dem['total_comentarios'] ?? 0 ?> totales">
                                <i class="fas fa-female" style="font-size: 2em; color: #e91e63;"></i><br>
                                <strong>Mujeres</strong><br>
                                <span style="font-size:1.5em; font-weight:700; color:#e91e63;"><?= $dem['mujeres'] ?? 50 ?>%</span><br>
                                <small style="color:#888;">(<?= $dem['mujeres_conteo'] ?? 0 ?> detectadas)</small>
                            </div>
                            <div class="demo_item" style="flex: 1; cursor:help;" title="Edad estimada por Ollama basándose en el tono y vocabulario de los comentarios">
                                <i class="fas fa-child" style="font-size: 2em; color: #00bcd4;"></i><br>
                                <strong>Edad Prom.</strong><br>
                                <span style="font-size:1.5em; font-weight:700; color:#00bcd4;"><?= $dem['edad_promedio'] ?? 'N/A' ?></span><br>
                                <small style="color:#888;">(<?= $dem['no_detectados'] ?? 0 ?> sin género)</small>
                            </div>
                        </div>
                    </div>

                    <div class="rentabilidad_box">
                        <h3>RENTABILIDAD ESTIMADA</h3>
                        <div style="font-size: 3em; font-weight: 800; margin: 10px 0;"><?= $calificacion ?><span style="font-size:0.4em; opacity:0.7;">/10</span></div>
                        <div class="score_bar">
                            <div class="score_fill" style="width: <?= $calificacion * 10 ?>%;"></div>
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
            
            <section class="subtitulo"><i class="fas fa-file-alt"></i> Guion Optimizado por IA</section>
            <div class="m_card" style="background:#fff3e0; border-color:#ff9800; font-size:1.1em; line-height:1.6;">
                <p id="txt_guion"><?= isset($plan['nuevo_guion']) ? nl2br($plan['nuevo_guion']) : 'El guion no se ha generado.' ?></p>
                <?php if (isset($plan['nuevo_guion'])): ?>
                <button onclick="navigator.clipboard.writeText(document.getElementById('txt_guion').innerText); alert('Guion copiado');" class="g_boton g_btn_primario" style="margin-top:15px;">
                    <i class="fas fa-copy"></i> Copiar Guion
                </button>
                <?php endif; ?>
            </div>

            <section class="subtitulo"><i class="fas fa-align-left"></i> Transcripción Original del Video</section>
            <div class="transcription_box">
                <?= nl2br($video['transcripcion']) ?>
            </div>
        </div>
    </div>

    <!-- Librerías para Nube de Palabras (Red Neuronal) -->
    <script src="https://d3js.org/d3.v6.min.js"></script>

    <div id="network_tooltip" style="position:absolute; opacity:0; background:rgba(0,0,0,0.8); color:white; padding:8px; border-radius:5px; pointer-events:none; font-size:14px; z-index:1000; box-shadow: 0 4px 6px rgba(0,0,0,0.3);"></div>

    <script>
        const cloud_data = <?= json_encode($cloud_data) ?>;
        
        // Determinar si es el formato antiguo o nuevo
        const is_new_format = cloud_data && cloud_data.nodos;
        const words = is_new_format ? cloud_data.nodos : (cloud_data.length ? cloud_data : []);
        const raw_links = is_new_format ? cloud_data.enlaces : [];
        
        const width = 1200;
        const height = 800;

        const svg = d3.select("#word_cloud")
            .style("display", "flex")
            .style("justify-content", "center")
            .style("align-items", "center")
            .style("position", "relative")
            .append("svg")
            .attr("width", "100%")
            .attr("height", "100%")
            .attr("viewBox", `0 0 ${width} ${height}`)
            .attr("preserveAspectRatio", "xMidYMid meet");

        const g = svg.append("g");

        // Habilitar Zoom y Paneo
        svg.call(d3.zoom()
            .scaleExtent([0.5, 4])
            .on("zoom", (event) => {
                g.attr("transform", event.transform);
            }));

        const tooltip = d3.select("#network_tooltip");

        // Preparar nodos
        // El radio dependerá del tamaño del texto para que contenga la palabra.
        const nodes = words.map(d => {
            let freq_size = Math.max(12, Math.min(24, 10 + d.size * 2));
            let radius = Math.max(25, (d.text.length * freq_size * 0.35) + 10);
            return {
                id: d.text, 
                size: d.size, 
                sentiment: d.sentiment || "neutro",
                radius: radius,
                font_size: freq_size
            };
        });
        
        // Crear enlaces
        let links = [];
        if (is_new_format && raw_links.length > 0) {
            links = raw_links.map(l => ({source: l.source, target: l.target, weight: l.weight}));
        } else {
            // Fallback: enlaces aleatorios (antiguo)
            for (let i = 0; i < nodes.length; i++) {
                if (i > 0) {
                    links.push({source: nodes[i].id, target: nodes[Math.floor(Math.random() * i)].id, weight: 1});
                    if (i > 2 && Math.random() > 0.5) {
                        links.push({source: nodes[i].id, target: nodes[Math.floor(Math.random() * i)].id, weight: 1});
                    }
                }
            }
        }

        // Colores según sentimiento
        const colorScale = {
            "positivo": "#4caf50",
            "negativo": "#f44336",
            "neutro": "#9e9e9e"
        };

        const simulation = d3.forceSimulation(nodes)
            .force("link", d3.forceLink(links).id(d => d.id).distance(180).strength(0.3))
            .force("charge", d3.forceManyBody().strength(-1000).distanceMax(500))
            .force("center", d3.forceCenter(width / 2, height / 2))
            .force("collide", d3.forceCollide().radius(d => d.radius + 20).iterations(4));

        const link = g.append("g")
            .selectAll("line")
            .data(links)
            .join("line")
            .attr("stroke", "#ccc")
            .attr("stroke-width", d => Math.min(6, d.weight * 1.5))
            .attr("stroke-opacity", 0.6);

        const node = g.append("g")
            .selectAll("g")
            .data(nodes)
            .join("g")
            .call(drag(simulation));

        node.append("circle")
            .attr("r", d => d.radius)
            .attr("fill", d => colorScale[d.sentiment] || colorScale["neutro"])
            .attr("opacity", 0.8)
            .style("cursor", "grab")
            .on("mouseover", function(event, d) {
                d3.select(this).attr("stroke", "#333").attr("stroke-width", 3).attr("opacity", 1);
                tooltip.transition().duration(200).style("opacity", .9);
                tooltip.html(`<strong>${d.id}</strong><br/>Repeticiones: ${d.size}<br/>Sentimiento: ${d.sentiment}`)
                    .style("left", (event.pageX + 10) + "px")
                    .style("top", (event.pageY - 28) + "px");
            })
            .on("mouseout", function(event, d) {
                d3.select(this).attr("stroke", null).attr("opacity", 0.8);
                tooltip.transition().duration(500).style("opacity", 0);
            });

        node.append("text")
            .text(d => d.id)
            .attr("text-anchor", "middle")
            .attr("dy", ".35em")
            .style("font-family", "Outfit, sans-serif")
            .style("font-weight", "600")
            .style("font-size", d => d.font_size + "px")
            .style("fill", "#fff")
            .style("pointer-events", "none");

        simulation.on("tick", () => {
            // Forzar las neuronas a quedarse dentro de la caja de visualización
            node.attr("transform", d => {
                d.x = Math.max(d.radius, Math.min(width - d.radius, d.x));
                d.y = Math.max(d.radius, Math.min(height - d.radius, d.y));
                return `translate(${d.x},${d.y})`;
            });

            link
                .attr("x1", d => d.source.x)
                .attr("y1", d => d.source.y)
                .attr("x2", d => d.target.x)
                .attr("y2", d => d.target.y);
        });

        // Drag function
        function drag(simulation) {
            function dragstarted(event) {
                if (!event.active) simulation.alphaTarget(0.3).restart();
                event.subject.fx = event.subject.x;
                event.subject.fy = event.subject.y;
                d3.select(this).select("circle").style("cursor", "grabbing");
            }
            function dragged(event) {
                event.subject.fx = event.x;
                event.subject.fy = event.y;
            }
            function dragended(event) {
                if (!event.active) simulation.alphaTarget(0);
                event.subject.fx = null;
                event.subject.fy = null;
                d3.select(this).select("circle").style("cursor", "grab");
            }
            return d3.drag()
                .on("start", dragstarted)
                .on("drag", dragged)
                .on("end", dragended);
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
        .demo_item { transition: transform 0.2s; padding: 10px; border-radius: 8px; }
        .demo_item:hover { transform: scale(1.05); background: rgba(0,0,0,0.03); }
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
                    <th>Candidato</th>
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
                    <td>
                        <?php if(!empty($v['candidato'])): ?>
                            <strong><?= htmlspecialchars($v['candidato']) ?></strong><br>
                            <small><i class="fas fa-flag"></i> <?= htmlspecialchars($v['partido'] ?? '') ?></small>
                        <?php else: ?>
                            <span style="color:#aaa;">—</span>
                        <?php endif; ?>
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
