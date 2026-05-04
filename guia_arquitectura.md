# Guía de Arquitectura: Antigravity AI 🚀

Esta guía está diseñada para que puedas explicarle a tu equipo, de forma clara y profesional, cómo está estructurado el proyecto, dónde vive cada parte de la lógica y cómo fluye la información desde que el usuario pega un link hasta que se genera el reporte de Inteligencia Artificial.

---

## 1. El Concepto General (Para explicar en la reunión)

El proyecto es una **Aplicación Web Híbrida**. Esto significa que usa diferentes lenguajes de programación para lo que cada uno hace mejor:
*   **PHP y JavaScript:** Se encargan de la cara visible (Frontend) y de administrar la base de datos de manera rápida.
*   **Python:** Es el "Cerebro". Se encarga exclusivamente de las matemáticas puras, limpiar el texto y hablar con la Inteligencia Artificial Local (Ollama).

El sistema tiene un diseño **Asincrónico (Fire and Forget)**: El usuario pide un análisis y la web le responde inmediatamente, mientras que los servidores (Workers) hacen el trabajo pesado a puerta cerrada.

---

## 2. Separación de Frontend y Backend

### 🎨 Frontend (Lo que ve el usuario)
El Frontend está contenido principalmente en la carpeta **`app/`** y **`assets/`**. 
Aquí NO se hacen cálculos pesados. Su única función es pintar la interfaz (HTML/CSS), mostrar gráficas (D3.js) y permitirle al usuario guardar el link en la base de datos con un estado de *"En cola"*.

### ⚙️ Backend (El cuarto de máquinas)
El Backend está dividido en 3 bloques de poder:
1.  **Core (`core/`):** La columna vertebral que conecta a la Base de Datos y decide cómo se comunican PHP y Python.
2.  **Engine (`engine/`):** Los trabajadores de cuello azul. Descargan los videos, extraen audios y consultan APIs externas (Whisper/Apify).
3.  **Python IA (`python_ia/`):** El trabajador de cuello blanco. Analiza sentimientos, arma nubes de palabras y genera estrategias de marketing usando Phi-3.

---

## 3. Árbol de Directorios (Archivo por Archivo)

Aquí tienes el mapa exacto de tu proyecto. Úsalo como referencia si alguien te pregunta "¿Y dónde se arregla X cosa?":

```text
ai_emotion/
│
├── app/                        # 🎨 FRONTEND: Módulos Visuales
│   ├── videos/index.php        # Vista: Tabla de historial y formulario para pegar el link de TikTok.
│   ├── reportes/index.php      # Vista: El panel final con el semáforo (Repetir/Evitar), Nube de Palabras y Rentabilidad.
│   └── diccionario/            # (Módulo futuro)
│
├── assets/                     # 🎨 RECURSOS FRONTEND
│   ├── css/colossus.css        # Archivo de estilos (Colores, botones, diseño visual).
│   └── js/global.js            # Lógica de interfaz (Animaciones, validaciones de formularios).
│
├── core/                       # ⚙️ BACKEND CENTRAL: El Corazón del Sistema
│   ├── colossus.php            # Archivo maestro de PHP. Contiene la conexión a la base de datos y dibuja el menú superior.
│   └── ai_engine.php           # El "Traductor". Es el script PHP que se encarga de enviarle los datos a Python y recibir la respuesta de la IA.
│
├── engine/                     # ⚙️ BACKEND WORKERS: Procesamiento Pesado
│   ├── trigger.php             # "El Gatillo". El script silencioso que despierta al motor en segundo plano sin colgar la web.
│   ├── extractor.php           # El Obrero: Descarga el video con yt-dlp, extrae el audio y usa Whisper para transcribirlo.
│   └── apify_handler.php       # El Recolector: Se conecta a la nube de Apify para descargar hasta 500 comentarios del video.
│
├── python_ia/                  # 🧠 BACKEND IA: Inteligencia Artificial
│   └── emotion_analyzer.py     # El Cerebro: Filtra palabras basura (Stopwords), hace cruces matemáticos (Detonantes) y le da el "Prompt" (Instrucción) a Ollama.
│
├── bin/                        # 📦 DEPENDENCIAS DEL SISTEMA
│   ├── yt-dlp.exe              # Programa externo para hackear la descarga de TikToks.
│   └── ffmpeg.exe              # Programa externo para manipular archivos multimedia (convertir video a MP3).
│
├── temp/                       # 🗑️ ALMACENAMIENTO TEMPORAL
│   └── (Vacío)                 # Aquí caen los MP3 y TXT por unos segundos mientras se procesan, luego se auto-eliminan.
│
├── db_antigravity.sql          # 💾 RESPALDO: Estructura de tablas de la base de datos para instalaciones futuras.
└── diagnostico.php             # 🩺 HERRAMIENTA: Script para verificar si Ollama, Python y los binarios están vivos y bien configurados.
```

## 4. El Flujo de Trabajo (Paso a Paso)

Si te piden explicar **"¿Qué pasa exactamente cuando le doy al botón Analizar?"**, esta es la respuesta:

1.  **Inserción (Frontend):** `app/videos/index.php` recibe el link, lo guarda en MySQL (`tb_videos`) como *"pendiente"* y le da un toque asincrónico a `engine/trigger.php`. La página web queda libre inmediatamente.
2.  **Extracción (Backend):** `trigger.php` despierta y manda a llamar a `engine/extractor.php`. Este usa `yt-dlp` para bajar el video, `Apify` para bajar comentarios, y `Whisper` para convertir la voz a texto. Guarda todo en la BD y cambia el estado a *"Analizando con IA"*.
3.  **Inteligencia (Backend IA):** Entra en acción `core/ai_engine.php`. Junta los textos y llama a `python_ia/emotion_analyzer.py`.
4.  **Matemática y Semántica:** Python limpia las palabras basura, encuentra las repeticiones y le pasa todo a Ollama (Phi-3) pidiéndole el plan de Marketing.
5.  **Finalización:** Ollama devuelve un JSON estructurado. PHP lo atrapa, lo guarda en la base de datos y marca el estado como *"Completado"*.
6.  **Visualización (Frontend):** El usuario entra a `app/reportes/index.php`, que simplemente lee la base de datos y dibuja cajas verdes y rojas de manera amigable.
