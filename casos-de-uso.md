Aquí tienes la lista con el estado actual de cada caso de uso basado en el código existente en tu directorio. He mantenido el formato para WhatsApp y añadido **\*TERMINADO\*** o **\*FALTA\*** a cada ítem:

*🎭 ACTORES DEL SISTEMA*
1. *Administrador:* Responsable del control de acceso, configuración y mantenimiento técnico de la plataforma.
2. *Usuario (Analista Político / Marketer):* Persona que utiliza la plataforma en su día a día para ingresar videos, leer las analíticas, revisar predicciones y descargar guiones.
3. *Sistema (Motor en Segundo Plano / IA):* La entidad automatizada (Backend, Scripts, Ollama, Whisper) que realiza de forma invisible el trabajo pesado de descarga, cálculos y predicciones.

================================

*📋 LISTA DEFINITIVA DE CASOS DE USO (CU)*

*Módulo de Administración y Seguridad*
- *CU1: Gestionar Roles.* [*FALTA*] (Actor: Administrador) El administrador puede crear, editar, eliminar y configurar los permisos de los diferentes roles del sistema.
- *CU2: Gestionar Usuarios.* [*FALTA*] (Actor: Administrador) El administrador puede dar de alta, modificar, suspender o eliminar cuentas de usuarios, asignándoles un rol específico.
- *CU3: Ejecutar Diagnóstico de Entorno.* [*TERMINADO*] (Actor: Administrador) El administrador puede correr un chequeo para verificar que las herramientas de IA (Ollama, Python) y los extractores (yt-dlp, FFmpeg) funcionen correctamente.

*Módulo de Dashboard e Interacción*
- *CU4: Solicitar Análisis de Video.* [*TERMINADO*] (Actor: Usuario) El usuario ingresa la URL (TikTok/YouTube) y el sistema lo encola para su procesamiento.
- *CU5: Consultar Historial de Videos.* [*TERMINADO*] (Actor: Usuario) El usuario visualiza la lista de videos subidos y monitorea si están "En cola", "Analizando" o "Completados".
- *CU6: Visualizar Reporte Base de IA.* [*TERMINADO*] (Actor: Usuario) El usuario accede al detalle de un video para ver el semáforo (Repetir/Evitar) y la nube de palabras.
- *CU7: Consultar Demografía de Audiencia.* [*FALTA*] (Actor: Usuario) El usuario visualiza en el dashboard gráficas que indican el *género* y el *rango de edad* predominante de la audiencia de un video. _(Nuevo)_
- *CU8: Visualizar Predicción de Apoyo.* [*FALTA*] (Actor: Usuario) El usuario consulta un panel comparativo en el dashboard que indica, basado en el análisis de videos subidos, qué candidato del mismo partido tiene mayor apoyo en ese momento. _(Nuevo)_
- *CU9: Consultar Nuevo Guion Generado.* [*FALTA*] (Actor: Usuario) El usuario puede leer, copiar o descargar un nuevo guion optimizado que el sistema ha creado basándose en un video anterior. _(Nuevo)_

*Módulo de Procesamiento Automatizado (Workers / Inteligencia Artificial)*
- *CU10: Extraer Multimedia.* [*TERMINADO*] (Actor: Sistema) Descargar automáticamente el video fuente y separar su pista de audio.
- *CU11: Transcribir Audio a Texto.* [*TERMINADO*] (Actor: Sistema) El sistema convierte la voz del video extraído a texto puro utilizando motores de IA (Whisper).
- *CU12: Recolectar Comentarios (Scraping).* [*TERMINADO*] (Actor: Sistema) El sistema se conecta a APIs de terceros (Apify) para recolectar masivamente los comentarios del video.
- *CU13: Analizar Semántica y Demografía.* [*FALTA*] (Actor: Sistema) La IA filtra el texto para detectar emociones, palabras clave e inferir o extraer datos sobre género y edad de los participantes/comentaristas. _(Actualizado con la nueva regla)_
- *CU14: Calcular Predicción Electoral.* [*FALTA*] (Actor: Sistema) El sistema cruza la métrica de apoyo y sentimiento de múltiples videos pertenecientes a candidatos de un mismo partido para calcular el porcentaje de favorabilidad de cada uno. _(Nuevo)_
- *CU15: Generar Estrategia de Marketing.* [*TERMINADO*] (Actor: Sistema) La IA local (Ollama) formula un plan de acción basado en las métricas de éxito del video.
- *CU16: Generar Nuevo Guion Optimizado.* [*FALTA*] (Actor: Sistema) A partir de la transcripción y el análisis de lo que funcionó en un video, la IA (Ollama) redacta y guarda un nuevo guion para ser utilizado por el candidato. _(Nuevo)_

================================

*📊 ESTADO DE AVANCE DEL PROYECTO: 50% COMPLETADO*

De acuerdo con la lista definitiva de *16 Casos de Uso* que definimos para el sistema, el porcentaje actual de avance es el siguiente:

- *Casos de Uso Totales:* 16
- *Casos de Uso TERMINADO:* 8
- *Casos de Uso FALTA:* 8

================================

*✅ TERMINADO (8 CU):*
- *CU3:* Ejecutar Diagnóstico de Entorno
- *CU4:* Solicitar Análisis de Video
- *CU5:* Consultar Historial de Videos
- *CU6:* Visualizar Reporte Base de IA
- *CU10:* Extraer Multimedia
- *CU11:* Transcribir Audio a Texto
- *CU12:* Recolectar Comentarios (Scraping)
- *CU15:* Generar Estrategia de Marketing

*❌ FALTA (8 CU):*
- *CU1:* Gestionar Roles
- *CU2:* Gestionar Usuarios
- *CU7:* Consultar Demografía de Audiencia
- *CU8:* Visualizar Predicción de Apoyo
- *CU9:* Consultar Nuevo Guion Generado
- *CU13:* Analizar Semántica y Demografía
- *CU14:* Calcular Predicción Electoral
- *CU16:* Generar Nuevo Guion Optimizado

