# Guía de Instalación y Despliegue: Antigravity AI Emotion

Este manual describe los pasos necesarios para levantar el sistema **Antigravity AI Emotion** en un entorno local para su desarrollo o ejecución.

## 1. Entorno de Servidor (XAMPP)

El proyecto está diseñado para correr bajo un servidor web con soporte PHP y MySQL.

1. Descarga e instala **XAMPP**.
2. Descomprime (o clona) los archivos de este proyecto dentro del directorio `htdocs` de XAMPP. La ruta recomendada es:
   `C:\xampp\htdocs\antigravity\ai_emotion\`
3. Inicia los módulos **Apache** y **MySQL** desde el panel de control de XAMPP.

## 2. Configuración de Base de Datos

1. Entra a **phpMyAdmin** (usualmente `http://localhost/phpmyadmin/`).
2. Crea una nueva base de datos llamada `db_antigravity`.
3. Importa el archivo `db_antigravity.sql` (que se encuentra en la raíz del proyecto) para crear todas las tablas necesarias.
4. Verifica tus credenciales de conexión. Si necesitas editarlas, abre el archivo `core/colossus.php` y modifica las **líneas 5 a la 8**:
   - **Línea 5 (Host):** `define('DB_HOST', 'localhost');`
   - **Línea 6 (Base de datos):** `define('DB_NAME', 'db_antigravity');`
   - **Línea 7 (Usuario):** `define('DB_USER', 'root');`
   - **Línea 8 (Contraseña):** `define('DB_PASS', '');` *(Si tu XAMPP tiene contraseña, ponla entre las comillas)*

## 3. Binarios Requeridos (`bin/`)

El sistema utiliza herramientas externas para la descarga y procesamiento de videos. Asegúrate de que la carpeta `bin/` contenga los siguientes ejecutables de Windows (`.exe`):

- **yt-dlp.exe** (Para descargar videos de TikTok/YouTube)
- **ffmpeg.exe** (Para extraer el audio)
- **ffprobe.exe** (Para análisis de medios)
- **ffplay.exe** (Opcional, reproductor)

*Nota: Debido al límite de tamaño de GitHub, es posible que estos archivos no se suban al repositorio. Si la carpeta `bin/` está vacía al clonar, deberás descargar [FFmpeg](https://ffmpeg.org/download.html) y [yt-dlp](https://github.com/yt-dlp/yt-dlp/releases), y poner sus `.exe` ahí.*

## 4. API de Apify (Scraping de Comentarios)

El sistema utiliza Apify para extraer hasta 500 comentarios de TikTok de manera automatizada.

1. Crea una cuenta en [Apify](https://apify.com/).
2. Obtén tu **API Token** personal en la configuración de tu cuenta.
3. Abre el archivo `core/colossus.php`.
4. Ve exactamente a la **Línea 9** y reemplaza el texto `"TU_TOKEN_DE_APIFY_AQUI"` por tu token personal:
   ```php
   define('APIFY_TOKEN', 'tu_token_de_apify_aqui');
   ```

## 5. Inteligencia Artificial (Ollama y Python)

El análisis de emociones, nube de pensamientos y el plan de marketing se ejecutan de manera local usando **Ollama** conectado a través de **Python**.

### Instalación de Python
1. Asegúrate de tener **Python** instalado en tu sistema (añadido al PATH).
2. Abre una terminal y ejecuta el siguiente comando para instalar la dependencia necesaria (`requests`):
   ```bash
   pip install requests
   ```

### Instalación de Ollama y el Modelo Phi3
1. Descarga e instala **Ollama** desde su [página oficial](https://ollama.com/).
2. Una vez instalado, abre una terminal y descarga el modelo **Phi-3** (el que usa nuestro sistema para ser rápido y eficiente):
   ```bash
   ollama run phi3
   ```
3. *(Opcional)* Si el proceso te deja dentro del chat de Ollama, puedes salir escribiendo `/bye`.
4. **Importante:** Ollama debe estar ejecutándose en segundo plano (normalmente en `http://localhost:11434`) para que el motor de IA del sistema funcione.
5. *(Opcional)* Si tu equipo usa otro puerto o quieres probar un modelo diferente a Phi-3, abre el archivo `python_ia/emotion_analyzer.py` y edita las **líneas 8 y 9**:
   - **Línea 8:** `OLLAMA_URL = "http://localhost:11434/api/generate"`
   - **Línea 9:** `OLLAMA_MODEL = "phi3"`
## 6. Permisos de Carpetas

Asegúrate de que las siguientes carpetas tengan permisos de escritura (el sistema guarda ahí los archivos procesados):
- `temp/`
- `scratch/`

## 7. Ejecución

Una vez completados los pasos, puedes entrar a tu navegador web a la siguiente dirección:
`http://localhost/antigravity/ai_emotion/app/videos/`

¡El sistema está listo para procesar videos y extraer inteligencia de marketing!
