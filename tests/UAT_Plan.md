# Plan de Pruebas de Aceptación (UAT) — Antigravity AI
### Proyecto: ai_emotion | Versión: 1.0 | Ing. de Calidad

---

## 1. Introducción y Objetivos

Este documento define el **Plan de Pruebas de Aceptación (UAT)** para el sistema **Antigravity AI**, una plataforma de análisis de sentimientos en videos de TikTok destinada a consultoras de marketing político.

El objetivo de las pruebas de aceptación es **verificar que el sistema cumple con los criterios de aceptación definidos por los stakeholders** antes de pasar a producción, bajo condiciones lo más cercanas posible al uso real.

### Criterios de éxito globales
- El sistema analiza un video de TikTok de extremo a extremo (ingesta → reporte) sin intervención manual.
- Los sentimientos detectados reflejan el tono real de los comentarios con una precisión perceptible ≥ 70%.
- El sistema soporta al menos 2 usuarios concurrentes sin degradación notable de rendimiento.
- Ninguna vulnerabilidad de seguridad crítica (SQL Injection, XSS) está presente en producción.

---

## 2. Participantes y Roles

| Rol                       | Responsabilidad                                              |
|---------------------------|--------------------------------------------------------------|
| **QA Lead (Analista)**    | Coordina las pruebas, documenta resultados y aprueba el UAT  |
| **Product Owner**         | Valida que el sistema cumpla los requisitos de negocio       |
| **Usuario Beta (Marketer)** | Evalúa la usabilidad y la utilidad del reporte generado    |
| **Administrador técnico** | Verifica que el ambiente de producción está correctamente configurado |

---

## 3. Alcance

### Incluido en UAT
- Módulo de ingesta de videos (URL TikTok)
- Motor de extracción (yt-dlp + Whisper)
- Motor de análisis IA (Python + Ollama)
- Módulo de reportes (Semáforo Repetir/Evitar, nube de pensamientos, demografía)
- Módulo de autenticación (Login, Logout, Roles)
- Panel de administración de usuarios y roles

### Excluido de UAT
- Módulo de predicciones electorales (CU14 — pendiente de desarrollo)
- Módulo de nuevo guion exportable (CU16 — pendiente de desarrollo)

---

## 4. Pruebas Alfa (Ambiente Controlado)

> Las Pruebas Alfa se realizan **en el ambiente de desarrollo** (XAMPP local) con el equipo interno. El objetivo es encontrar defectos antes de exponerlos a usuarios externos.

### 4.1 Checklist Operativo Pre-Alfa

- [ ] La base de datos `db_antigravity` está inicializada con el esquema completo.
- [ ] Existe al menos 1 usuario Administrador y 1 usuario Analista en `tb_usuarios`.
- [ ] Ollama está corriendo (`ollama run llama3`).
- [ ] Python y sus dependencias están instalados (`requests`, `json`).
- [ ] `yt-dlp.exe` y `ffmpeg.exe` están en la carpeta `bin/`.
- [ ] El token de Apify en `core/env.php` tiene créditos disponibles.
- [ ] La suite PHPUnit (Unit + Integration) pasa al 100%: `c:\xampp\php\php.exe vendor/bin/phpunit --testsuite Unit,Integration`.

### 4.2 Casos de Prueba Alfa

#### CU-UAT-01: Autenticación

| Campo         | Detalle                                                      |
|---------------|--------------------------------------------------------------|
| **Objetivo**  | Verificar que el login y logout funcionan correctamente      |
| **Precondición** | Usuario `admin@test.com` existe con contraseña `Admin123!` |
| **Pasos**     | 1. Abrir `/app/login/`. 2. Ingresar credenciales. 3. Verificar redirección al dashboard. 4. Hacer clic en "Salir". |
| **Resultado esperado** | Login redirige al dashboard. Logout limpia la sesión y redirige al login. |
| **Resultado obtenido** | `[ ] PASS  [ ] FAIL  [ ] BLOCK` |
| **Notas**     | |

---

#### CU-UAT-02: Ingesta de Video

| Campo         | Detalle                                                      |
|---------------|--------------------------------------------------------------|
| **Objetivo**  | Verificar que el sistema acepta una URL de TikTok y la encola |
| **Precondición** | Usuario autenticado en el sistema                          |
| **Pasos**     | 1. Ir a "Videos". 2. Pegar URL: `https://www.tiktok.com/@rodrigopazbolivia/video/7513660434427700486`. 3. Completar candidato y partido. 4. Clic "Analizar". |
| **Resultado esperado** | El video aparece en la lista con estado "Pendiente" o "Procesando". |
| **Resultado obtenido** | `[ ] PASS  [ ] FAIL  [ ] BLOCK` |
| **Notas**     | |

---

#### CU-UAT-03: Procesamiento y Análisis IA

| Campo         | Detalle                                                      |
|---------------|--------------------------------------------------------------|
| **Objetivo**  | Verificar que el motor extrae comentarios, transcribe y genera el análisis |
| **Precondición** | CU-UAT-02 completado. Ollama y Apify configurados.        |
| **Pasos**     | 1. Esperar a que el estado del video cambie a "Completado". 2. Abrir el reporte. |
| **Resultado esperado** | El reporte muestra: semáforo (Repetir/Evitar), nube de pensamientos, demografía de audiencia, calificación de 0-10. |
| **Resultado obtenido** | `[ ] PASS  [ ] FAIL  [ ] BLOCK` |
| **Pregunta de validación** | ¿El sentimiento detectado refleja el tono real de los comentarios del video? `[ ] Sí  [ ] Parcialmente  [ ] No` |
| **Notas**     | |

---

#### CU-UAT-04: Validación de Roles y Permisos

| Campo         | Detalle                                                      |
|---------------|--------------------------------------------------------------|
| **Objetivo**  | Verificar que los roles limitan correctamente el acceso      |
| **Pasos**     | 1. Iniciar sesión como Analista (sin rol Admin). 2. Intentar acceder a `/app/admin/usuarios.php`. |
| **Resultado esperado** | El sistema redirige al login con `?error=acceso_denegado`. |
| **Resultado obtenido** | `[ ] PASS  [ ] FAIL  [ ] BLOCK` |
| **Notas**     | |

---

#### CU-UAT-05: Gestión del Diccionario

| Campo         | Detalle                                                      |
|---------------|--------------------------------------------------------------|
| **Objetivo**  | Verificar que el Administrador puede agregar palabras al diccionario |
| **Pasos**     | 1. Ir a "Diccionario". 2. Agregar la palabra "corrupción" con categoría "mala" y peso -10. 3. Ejecutar un nuevo análisis. |
| **Resultado esperado** | La palabra "corrupción" aparece en rojo en la nube de pensamientos del nuevo reporte. |
| **Resultado obtenido** | `[ ] PASS  [ ] FAIL  [ ] BLOCK` |
| **Notas**     | |

---

## 5. Pruebas Beta (Distribución Controlada)

> Las Pruebas Beta se realizan con **usuarios reales seleccionados** (consultores políticos o marketers) que acceden al sistema en un ambiente similar al productivo. El objetivo es obtener feedback sobre la utilidad y usabilidad real.

### 5.1 Perfil de Usuarios Beta

- **Cantidad**: 3 a 5 usuarios (marketers o consultores políticos)
- **Duración**: 2 semanas
- **Acceso**: URL pública en servidor de staging (no producción)
- **Videos a analizar**: Videos reales de campañas electorales bolivianas

### 5.2 Preguntas de Validación para Usuarios Beta

Estas preguntas se envían al usuario después de cada sesión de uso:

1. **Utilidad del reporte:** ¿La sección "Repetir" y "Evitar" le ofrece información accionable para planificar su próximo video?
   - `[ ]` Muy útil  `[ ]` Algo útil  `[ ]` Poco útil  `[ ]` No útil

2. **Precisión de la IA:** ¿El sentimiento detectado refleja el tono real de los comentarios que usted percibió al leer el video?
   - `[ ]` Sí, muy preciso  `[ ]` Parcialmente  `[ ]` No refleja la realidad

3. **Demografía:** ¿La distribución de género estimada le parece razonable para el video analizado?
   - `[ ]` Sí  `[ ]` No — Estimación real: `_____`

4. **Usabilidad:** ¿Pudo usar el sistema sin ayuda externa?
   - `[ ]` Sí, completamente  `[ ]` Con algunas dudas  `[ ]` Necesité ayuda

5. **Tiempo de análisis:** ¿El tiempo de espera para obtener el reporte fue aceptable?
   - `[ ]` Sí (< 5 min)  `[ ]` Aceptable (5-15 min)  `[ ]` Demasiado largo (> 15 min)

6. **Defectos encontrados:** Describa cualquier error, comportamiento inesperado o sugerencia:
   > _________________________________

### 5.3 Criterios de Aceptación Beta

El sistema se aprueba para producción si:
- ≥ 80% de los usuarios beta califican la utilidad del reporte como "Muy útil" o "Algo útil".
- ≥ 70% de los usuarios beta confirman que el sentimiento detectado es "preciso" o "parcialmente preciso".
- 0 defectos críticos (crash del sistema, pérdida de datos) durante las pruebas beta.
- ≤ 3 defectos de severidad media que pueden resolverse en parches menores.

---

## 6. Métricas de Calidad

| Métrica                         | Objetivo      | Medición                          |
|---------------------------------|---------------|-----------------------------------|
| Cobertura pruebas unitarias      | ≥ 85%         | PHPUnit + python tests            |
| Pruebas de integración pasadas   | 100%          | Suite Integration PHPUnit         |
| Defectos críticos en beta        | 0             | Reporte de usuarios beta          |
| Tiempo análisis por video        | < 10 minutos  | Timestamp BD (inicio → completado)|
| Precisión de sentimientos        | ≥ 70%         | Validación manual usuarios beta   |

---

## 7. Registro de Defectos UAT

| ID     | Descripción         | Severidad | Estado   | Asignado a | Fecha    |
|--------|---------------------|-----------|----------|------------|----------|
| DEF-01 | (ejemplo)           | Alta      | Abierto  |            |          |

---

## 8. Aprobación Final

| Rol                | Nombre          | Firma / Aprobación    | Fecha |
|--------------------|-----------------|-----------------------|-------|
| QA Lead            |                 | `[ ] Aprobado`        |       |
| Product Owner      |                 | `[ ] Aprobado`        |       |
| Director Técnico   |                 | `[ ] Aprobado`        |       |
