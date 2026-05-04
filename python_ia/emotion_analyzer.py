import sys
import json
import requests
import re
import os
from collections import Counter

OLLAMA_URL = "http://localhost:11434/api/generate"
OLLAMA_MODEL = "phi3"

# Lista expandida para limpiar la nube de "ruido"
STOPWORDS_ES = {
    "el", "la", "los", "las", "un", "una", "unos", "unas", "de", "del", "a", "al", "y", "e", "o", "u", 
    "en", "con", "por", "para", "si", "no", "ni", "pero", "que", "es", "son", "fue", "era", "ser", 
    "este", "esta", "esto", "estos", "estas", "ese", "esa", "eso", "esos", "esas", "aquel", "aquella", 
    "me", "te", "se", "nos", "os", "mi", "tu", "su", "mis", "tus", "sus", "como", "cuando", "donde", 
    "quien", "cual", "muy", "mas", "todo", "todos", "tan", "solo", "ya", "ahora", "despues", "antes",
    "estaba", "habia", "tiene", "tienen", "tenia", "nosotros", "ustedes", "ellos", "ella", "ellos",
    "esto", "esta", "estos", "estas", "este", "ese", "esa", "esos", "esas", "sticker", "gracias", 
    "saludos", "hola", "video", "tiktok", "comentario", "comentarios", "buen", "buena", "bien",
    "cada", "solos", "toda", "primera", "segunda", "super", "súper", "completo", "dado", "habian", "habían",
    "podido", "dato", "tener", "seran", "serán", "algo", "nada", "algunos", "algunas", "mucho", "poco"
}

def limpiar_y_filtrar(texto):
    # Solo palabras de más de 3 letras que no sean stopwords
    palabras = re.findall(r'\b\w{4,}\b', str(texto).lower())
    return [p for p in palabras if p not in STOPWORDS_ES]

def extraer_nube_pensamientos(comentarios):
    todas_las_palabras = []
    for c in comentarios:
        todas_las_palabras.extend(limpiar_y_filtrar(c))
    conteo = Counter(todas_las_palabras)
    # Devolvemos el formato que D3.js espera en el frontend
    return [{"text": palabra, "size": freq} for palabra, freq in conteo.most_common(50)]

def encontrar_detonantes_inteligentes(transcripcion, comentarios):
    palabras_video = set(limpiar_y_filtrar(transcripcion))
    palabras_comentarios = set()
    for c in comentarios:
        palabras_comentarios.update(limpiar_y_filtrar(c))
    # Intersección de lo que se dijo en el video y lo que la gente repitió
    return list(palabras_video.intersection(palabras_comentarios))

def generar_plan_marketing(transcripcion, comentarios):
    nube_data = extraer_nube_pensamientos(comentarios)
    nube_simple = [d["text"] for d in nube_data[:15]]
    detonantes = encontrar_detonantes_inteligentes(transcripcion, comentarios)
    
    prompt = f"""
    Eres un Consultor Senior de Marketing Político y Estratega Digital.
    Analiza la TRANSCRIPCIÓN de un video de TikTok y la NUBE PÚBLICA (palabras más repetidas por la gente).
    
    TRANSCRIPCIÓN: {transcripcion[:2000]}
    DETERMINANTES (lo que dijiste y la gente repitió): {", ".join(detonantes)}
    NUBE PÚBLICA (lo que la gente tiene en la mente): {", ".join(nube_simple)}

    DAME UN PLAN DE MARKETING ESTRATÉGICO EN JSON CON LAS SIGUIENTES CLAVES EXACTAS:
    {{
        "repetir": "Palabras o frases que causaron impacto positivo y DEBEN volver a usarse.",
        "evitar": "Palabras o temas que causaron rechazo o confusión en los comentarios.",
        "rentabilidad": "Análisis profundo: ¿Conviene seguir hablando de esto? ¿Qué hay que pulir? (Máximo 3 líneas)",
        "calificacion": 0-10
    }}
    RESPONDE SOLO EL JSON VÁLIDO.
    """
    payload = {
        "model": OLLAMA_MODEL, 
        "prompt": prompt, 
        "stream": False,
        "format": "json",
        "keep_alive": 0, 
        "options": {"temperature": 0.1}
    }

    try:
        res = requests.post(OLLAMA_URL, json=payload, timeout=300)
        res.raise_for_status()
        ia_res = res.json().get("response", "{}")
        
        # Como usamos format="json", la respuesta debería ser un JSON directo.
        try:
            resultado = json.loads(ia_res)
        except json.JSONDecodeError:
            # Fallback a regex si por alguna razón falla el parseo directo
            match = re.search(r'\{.*\}', ia_res, re.DOTALL)
            if match:
                resultado = json.loads(match.group())
            else:
                return {"error": "Formato JSON inválido desde Ollama", "nube_pensamientos": nube_data}
                
        resultado["detonantes"] = detonantes
        resultado["nube_pensamientos"] = nube_data
        
        # Validar claves y asegurar que sean strings (excepto calificacion y los arrays inyectados)
        for key in ["repetir", "evitar", "rentabilidad"]:
            if key not in resultado:
                resultado[key] = "No analizado"
            elif isinstance(resultado[key], list):
                resultado[key] = "\n".join([str(item) for item in resultado[key]])
                
        if "calificacion" not in resultado:
            resultado["calificacion"] = 0
        elif isinstance(resultado["calificacion"], list) and len(resultado["calificacion"]) > 0:
            resultado["calificacion"] = resultado["calificacion"][0]
                
        return resultado
    except requests.exceptions.Timeout:
        return {"error": "Ollama tardó demasiado en responder (Timeout > 300s)", "nube_pensamientos": nube_data}
    except Exception as e:
        return {"error": str(e), "nube_pensamientos": nube_data}

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No se proporcionó archivo de entrada"}))
        sys.exit(1)
        
    archivo_entrada = sys.argv[1]
    if not os.path.exists(archivo_entrada):
        print(json.dumps({"error": "Archivo no encontrado"}))
        sys.exit(1)
        
    with open(archivo_entrada, 'r', encoding='utf-8') as f:
        data = json.load(f)
        
    trans = data.get('transcripcion', '')
    coments = data.get('comentarios', [])
    
    print(json.dumps(generar_plan_marketing(trans, coments)))
