<?php // Predicción de Apoyo Electoral
require_once '../../core/auth.php';
require_once '../../core/colossus.php';
fn_requerir_permiso('ver_electoral');
fn_cabecera_sistema("Ranking Electoral");

$db = fn_conexion_bd();

// Obtener todos los videos completados
$stmt = $db->query("SELECT url_tiktok, candidato, partido, thumbnail, analisis_ia FROM tb_videos WHERE estado = 'completado'");
$videos = $stmt->fetchAll();

$candidatos = [];

foreach ($videos as $v) {
    // Definir identificador único para agrupar
    $nombre = !empty($v['candidato']) ? $v['candidato'] : null;
    if (!$nombre) {
        preg_match('/@([a-zA-Z0-9_]+)/', $v['url_tiktok'], $matches);
        $nombre = $matches[1] ?? 'Desconocido';
    }
    
    $partido = $v['partido'] ?? 'Independiente';
    $thumbnail = $v['thumbnail'] ?? '';
    
    $plan = json_decode($v['analisis_ia'] ?? '{}', true);
    
    // Contar Nodos Positivos usando el multiplicador de Peso
    $nodos_positivos = 0;
    if (isset($plan['nube_pensamientos']['nodos'])) {
        foreach ($plan['nube_pensamientos']['nodos'] as $nodo) {
            if (isset($nodo['sentiment']) && $nodo['sentiment'] === 'positivo') {
                $peso = isset($nodo['peso']) && (int)$nodo['peso'] > 0 ? (int)$nodo['peso'] : 10;
                $nodos_positivos += ((int)$nodo['size'] * $peso);
            }
        }
    }
    
    if (!isset($candidatos[$nombre])) {
        $candidatos[$nombre] = [
            'nombre' => $nombre,
            'partido' => $partido,
            'thumbnail' => $thumbnail,
            'score_positivo' => 0,
            'conteo_videos' => 0
        ];
    }
    
    $candidatos[$nombre]['score_positivo'] += $nodos_positivos;
    $candidatos[$nombre]['conteo_videos'] += 1;
    
    // Si este video tiene thumbnail, lo guardamos para asegurar que al menos haya uno visible
    if (!empty($thumbnail)) {
        $candidatos[$nombre]['thumbnail'] = $thumbnail;
    }
}

// Convertir a array plano para ordenar
$ranking = array_values($candidatos);

// Ordenar de mayor a menor score positivo
usort($ranking, function($a, $b) {
    return $b['score_positivo'] <=> $a['score_positivo'];
});

// Extraer Top 10
$top_10 = array_slice($ranking, 0, 10);
$max_score = !empty($top_10) && $top_10[0]['score_positivo'] > 0 ? $top_10[0]['score_positivo'] : 1; // Para calcular la altura porcentual de las barras
?>

<style>
/* CSS para el gráfico de barras verticales */
.bar-chart-container {
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 30px;
    height: 400px;
    padding-bottom: 90px;
    position: relative;
    margin-top: 60px;
    border-bottom: 2px solid #ddd;
}
.bar-column {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    width: 65px;
    position: relative;
    height: 100%;
}
.bar-fill {
    width: 100%;
    border-radius: 5px 5px 0 0;
    background: #00bcd4; /* Color cyan genérico por defecto */
    transition: height 1.5s cubic-bezier(0.2, 0.8, 0.2, 1);
    position: relative;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
}
/* Colores de Podio */
.bar-column:nth-child(1) .bar-fill { background: #4caf50; } /* Oro / Verde ganador */
.bar-column:nth-child(2) .bar-fill { background: #ff9800; } /* Plata / Naranja */
.bar-column:nth-child(3) .bar-fill { background: #03a9f4; } /* Bronce / Azul */

.bar-image {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.25);
    position: absolute;
    top: -27px; /* Exactamente la mitad de la imagen fuera de la barra */
    left: 50%;
    transform: translateX(-50%);
    z-index: 2;
    background: #fff;
}
.bar-score {
    position: absolute;
    top: -65px;
    left: 50%;
    transform: translateX(-50%);
    font-weight: 900;
    font-size: 18px;
    color: #333;
}
.bar-label {
    position: absolute;
    bottom: -80px;
    left: 50%;
    transform: translateX(-50%);
    text-align: center;
    width: 120px;
}
.bar-label-name {
    font-size: 13px;
    font-weight: bold;
    color: #333;
    line-height: 1.3;
    word-wrap: break-word;
}
.bar-label-party {
    font-size: 11px;
    color: #777;
    margin-top: 4px;
}

/* Modal CSS para la Lista Completa */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 1000;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.modal-content {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 15px 35px rgba(0,0,0,0.3);
}
.modal-close {
    float: right;
    cursor: pointer;
    font-size: 22px;
    color: #555;
    transition: color 0.2s;
}
.modal-close:hover {
    color: #f44336;
}
.rank-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
}
.rank-table th, .rank-table td {
    padding: 15px 12px;
    border-bottom: 1px solid #e0e0e0;
    text-align: left;
}
.rank-table th {
    background: #f8f9fa;
    font-weight: 800;
    color: #444;
}
.rank-position {
    font-weight: 900;
    color: #2196f3;
    font-size: 1.3em;
}
.rank-table tbody tr:hover {
    background: #f1f8ff;
}
</style>

<div class="g_cabeza_busqueda" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h2><i class="fas fa-poll"></i> Ranking Electoral (Sentimiento)</h2>
        <p>Ranking basado exclusivamente en la suma matemática de comentarios e interacciones positivas detectadas por la IA.</p>
    </div>
    <button onclick="document.getElementById('rankingModal').style.display='flex'" class="g_boton g_btn_primario" style="margin-right:20px;">
        <i class="fas fa-list-ol"></i> Ver Ranking Completo
    </button>
</div>

<div class="g_form_colossus" style="max-width: 1100px; margin: 20px auto; padding: 20px; text-align:center;">
    
    <?php if (empty($top_10) || $max_score === 1 && $top_10[0]['score_positivo'] == 0): ?>
        <div class="m_card m_info" style="text-align:left;">No hay suficientes interacciones positivas detectadas para formar un ranking. Analiza más videos.</div>
    <?php else: ?>
        <h3 style="color:#555; font-weight:300; margin-bottom: 20px;">Top 10 - Favorabilidad Nacional</h3>
        <div class="bar-chart-container">
            <?php foreach($top_10 as $c): 
                $height_percent = ($c['score_positivo'] / $max_score) * 100;
                // Altura mínima para que la barra no desaparezca por completo y quepa la foto
                if ($height_percent < 12) $height_percent = 12; 
                
                // Imagen por defecto dinámica basada en el nombre
                $img_src = !empty($c['thumbnail']) ? $c['thumbnail'] : 'https://ui-avatars.com/api/?name='.urlencode($c['nombre']).'&background=random';
            ?>
                <div class="bar-column">
                    <div class="bar-score"><?= $c['score_positivo'] ?> <i class="fas fa-thumbs-up" style="font-size:14px; color:#4caf50;"></i></div>
                    <div class="bar-fill" style="height: <?= $height_percent ?>%;">
                        <img src="<?= $img_src ?>" class="bar-image" alt="Candidato" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($c['nombre']) ?>&background=random';">
                    </div>
                    <div class="bar-label">
                        <div class="bar-label-name"><?= htmlspecialchars($c['nombre']) ?></div>
                        <div class="bar-label-party"><?= htmlspecialchars($c['partido']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="m_card m_info" style="margin-top:70px; text-align:left;">
        <h3><i class="fas fa-info-circle"></i> ¿Cómo se calcula la puntuación?</h3>
        <p>El sistema recopila la Red Neuronal Semántica de todos los videos asociados a un candidato. La puntuación final es la <strong>suma exacta del volumen de conversación positiva</strong> (halagos, palabras clave de apoyo y sentimiento a favor). Aquel candidato que genere un mayor ruido positivo orgánico en sus comentarios, lidera la encuesta.</p>
    </div>
</div>

<!-- Modal Ranking Completo -->
<div id="rankingModal" class="modal-overlay" onclick="if(event.target===this) this.style.display='none'">
    <div class="modal-content">
        <span class="modal-close" onclick="document.getElementById('rankingModal').style.display='none'"><i class="fas fa-times"></i></span>
        <h2><i class="fas fa-trophy" style="color:#ff9800;"></i> Ranking Nacional Completo</h2>
        <p>Posiciones según interacciones positivas orgánicas.</p>
        
        <table class="rank-table">
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Candidato</th>
                    <th>Partido</th>
                    <th>Volumen Positivo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($ranking as $index => $c): ?>
                    <tr>
                        <td class="rank-position">#<?= $index + 1 ?></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <img src="<?= !empty($c['thumbnail']) ? $c['thumbnail'] : 'https://ui-avatars.com/api/?name='.urlencode($c['nombre']).'&background=random' ?>" style="width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid #ccc;" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($c['nombre']) ?>&background=random';">
                                <strong><?= htmlspecialchars($c['nombre']) ?></strong>
                            </div>
                        </td>
                        <td><span style="background:#e8f5e9; color:#2e7d32; font-weight:600; padding:4px 10px; border-radius:12px; font-size:12px;"><?= htmlspecialchars($c['partido']) ?></span></td>
                        <td><strong style="color:#4caf50; font-size:1.1em;"><?= $c['score_positivo'] ?> <i class="fas fa-thumbs-up" style="font-size:12px;"></i></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Pequeño script para animar las barras al cargar la página
document.addEventListener("DOMContentLoaded", function() {
    const bars = document.querySelectorAll('.bar-fill');
    bars.forEach(bar => {
        const targetHeight = bar.style.height;
        bar.style.height = '0%';
        setTimeout(() => {
            bar.style.height = targetHeight;
        }, 100);
    });
});
</script>

<?php fn_pie_sistema(); ?>
