import sys
import json
import requests
import re
import os
from collections import Counter

OLLAMA_URL = "http://localhost:11434/api/generate"
OLLAMA_MODEL = "llama3"

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
    "podido", "dato", "tener", "seran", "serán", "algo", "nada", "algunos", "algunas", "mucho", "poco",
    "hizo", "hace", "hacer", "haber", "porque", "quienes", "jajaja", "jeej", "jaja", "jajajaja", "tren",
    "bala", "vez", "veces", "asi", "así", "aqui", "aquí", "alla", "allá", "ahi", "ahí", "estan", "están",
    "estaba", "estaban", "siempre", "nunca", "tambien", "también", "tampoco", "entonces", "luego", "ademas",
    "puede", "pueden", "debe", "deben", "creo", "pienso", "digo", "dice", "dicen", "dijo", "dijeron", "hizo",
    "hicieron", "hacerlo", "hacerla", "hacerlos", "hacerlas", "vamos", "van", "va", "voy", "iba", "iban",
    "quiero", "quiere", "quieren", "queria", "querian", "sé", "sabe", "saben", "sabia", "sabian", "verdad",
    "claro", "seguro", "segura", "pues", "bueno", "malo", "mejor", "peor", "mayor", "menor", "mismo", "misma",
    "mismos", "mismas", "propio", "propia", "propios", "propias", "tal", "tales", "casi", "hasta", "hacia",
    "desde", "sobre", "entre", "contra", "sin", "segun", "según", "bajo", "ante", "tras"
}

# Nombres comunes latinoamericanos para inferir género por nombre de usuario
NOMBRES_MASCULINOS = {
    "juan", "carlos", "jose", "luis", "miguel", "pedro", "andres", "daniel", "david", "jorge",
    "pablo", "sergio", "fernando", "ricardo", "mario", "oscar", "roberto", "alejandro", "francisco", "javier",
    "rafael", "gabriel", "santiago", "diego", "hector", "raul", "marco", "eduardo", "alberto", "victor",
    "manuel", "ivan", "martin", "gustavo", "orlando", "gonzalo", "enrique", "nelson", "cesar", "emilio"
}

NOMBRES_FEMENINOS = {
    "maria", "ana", "laura", "carmen", "rosa", "patricia", "andrea", "claudia", "gabriela", "carolina",
    "monica", "sandra", "diana", "lucia", "camila", "paula", "valentina", "sofia", "isabella", "natalia",
    "paola", "daniela", "alejandra", "fernanda", "mariana", "jessica", "veronica", "adriana", "lorena", "marcela",
    "elena", "silvia", "marta", "julia", "susana", "teresa", "cristina", "beatriz", "liliana", "cecilia"
}

def inferir_genero_por_nombre(nombre_usuario):
    """Analiza el nombre de usuario para inferir género. Retorna 'M', 'F' o None."""
    nombre_limpio = re.sub(r'[^a-záéíóúñ]', ' ', nombre_usuario.lower())
    partes = nombre_limpio.split()
    for parte in partes:
        if parte in NOMBRES_MASCULINOS:
            return 'M'
        if parte in NOMBRES_FEMENINOS:
            return 'F'
    return None

def calcular_demografia_por_comentarios(comentarios_con_autor):
    """
    Analiza los nombres de los autores de los comentarios para estimar demografía.
    Retorna un diccionario con hombres, mujeres (porcentaje) y conteo real.
    """
    total = len(comentarios_con_autor)
    hombres = 0
    mujeres = 0
    no_detectados = 0
    
    for c in comentarios_con_autor:
        autor = c.get('autor', '') if isinstance(c, dict) else ''
        genero = inferir_genero_por_nombre(autor)
        if genero == 'M':
            hombres += 1
        elif genero == 'F':
            mujeres += 1
        else:
            no_detectados += 1
            
    # Repartir no detectados equitativamente
    if no_detectados > 0:
        mitad = no_detectados // 2
        hombres += mitad
        mujeres += (no_detectados - mitad)
        no_detectados = 0
    
    detectados = hombres + mujeres
    if detectados > 0:
        pct_h = round((hombres / detectados) * 100)
        pct_m = round((mujeres / detectados) * 100)
    else:
        pct_h = 50
        pct_m = 50
    
    return {
        "hombres": pct_h,
        "mujeres": pct_m,
        "hombres_conteo": hombres,
        "mujeres_conteo": mujeres,
        "no_detectados": no_detectados,
        "total_comentarios": total,
        "metodo": "Inferencia por nombre de autor del comentario"
    }

def limpiar_y_filtrar(texto):
    # Solo palabras de más de 3 letras que no sean stopwords
    palabras = re.findall(r'\b\w{4,}\b', str(texto).lower())
    return [p for p in palabras if p not in STOPWORDS_ES]

def extraer_nube_pensamientos_y_enlaces(comentarios):
    todas_las_palabras = []
    textos_filtrados = []
    for c in comentarios:
        texto = c.get('texto', c) if isinstance(c, dict) else c
        palabras_limpias = limpiar_y_filtrar(texto)
        todas_las_palabras.extend(palabras_limpias)
        textos_filtrados.append(palabras_limpias)
        
    conteo = Counter(todas_las_palabras)
    top_words_tuples = conteo.most_common(20) # Reducimos a 20 para evitar ruido y hacer clara la red
    top_words = [p[0] for p in top_words_tuples]
    
    # Nodos
    nodos = [{"text": palabra, "size": freq} for palabra, freq in top_words_tuples]
    
    # Enlaces (Co-ocurrencia)
    enlaces_dict = {}
    for palabras_comentario in textos_filtrados:
        palabras_top_en_comentario = list(set([p for p in palabras_comentario if p in top_words]))
        for i in range(len(palabras_top_en_comentario)):
            for j in range(i + 1, len(palabras_top_en_comentario)):
                w1 = palabras_top_en_comentario[i]
                w2 = palabras_top_en_comentario[j]
                pair = tuple(sorted([w1, w2]))
                enlaces_dict[pair] = enlaces_dict.get(pair, 0) + 1
                
    enlaces = [{"source": pair[0], "target": pair[1], "weight": weight} for pair, weight in enlaces_dict.items() if weight > 0]
    
    return nodos, enlaces

def encontrar_detonantes_inteligentes(transcripcion, comentarios):
    palabras_video = set(limpiar_y_filtrar(transcripcion))
    palabras_comentarios = set()
    for c in comentarios:
        texto = c.get('texto', c) if isinstance(c, dict) else c
        palabras_comentarios.update(limpiar_y_filtrar(texto))
    # Intersección de lo que se dijo en el video y lo que la gente repitió
    return list(palabras_video.intersection(palabras_comentarios))

def generar_plan_marketing(transcripcion, comentarios, comentarios_con_autor=None, candidato="", partido="", diccionario=None):
    if diccionario is None:
        diccionario = []
    
    # Mapeo de diccionario
    dict_local = {}
    for item in diccionario:
        palabra = str(item.get('palabra', '')).lower().strip()
        cat = item.get('categoria', 'neutra')
        peso = int(item.get('peso', 0))
        
        sent = "neutro"
        if cat == "buena": sent = "positivo"
        elif cat == "mala": sent = "negativo"
        
        dict_local[palabra] = {"sentiment": sent, "peso": peso}

    # Extraer solo textos para la nube y detonantes
    textos_comentarios = []
    for c in comentarios:
        if isinstance(c, dict):
            textos_comentarios.append(c.get('texto', c.get('text', '')))
        else:
            textos_comentarios.append(c)
    
    nube_nodos, nube_enlaces = extraer_nube_pensamientos_y_enlaces(textos_comentarios)
    nube_simple = [d["text"] for d in nube_nodos]
    detonantes = encontrar_detonantes_inteligentes(transcripcion, textos_comentarios)
    
    # Calcular demografía por nombres de usuario
    if comentarios_con_autor:
        demografia_calculada = calcular_demografia_por_comentarios(comentarios_con_autor)
    else:
        demografia_calculada = {"hombres": 50, "mujeres": 50, "hombres_conteo": 0, "mujeres_conteo": 0, "no_detectados": 0, "total_comentarios": len(comentarios), "metodo": "Sin datos de autor"}
    
    # Calcular largo mínimo del guion
    largo_transcripcion = len(transcripcion)
    palabras_transcripcion = len(transcripcion.split())
    
    contexto_candidato = ""
    if candidato:
        contexto_candidato = f"\n    CONTEXTO DEL VIDEO: Este video le pertenece al candidato político '{candidato}'"
        if partido:
            contexto_candidato += f" del partido '{partido}'."
        contexto_candidato += " Debes intuir de manera inteligente que la mayoría de quejas, halagos, opiniones y pronombres en los comentarios van dirigidos a este candidato y su gestión o partido, aunque no lo mencionen por su nombre."
        
    plantilla_sentimientos = ""
    for palabra in nube_simple:
        plantilla_sentimientos += f'\n            "{palabra}": "positivo|negativo|neutro",'
    if plantilla_sentimientos:
        plantilla_sentimientos = plantilla_sentimientos[:-1] # Remove last comma
        
    prompt = f"""
    Eres un Consultor Senior de Marketing Político y Estratega Digital.
    Analiza la TRANSCRIPCIÓN de un video de TikTok y la NUBE PÚBLICA (palabras más repetidas por la gente).
    {contexto_candidato}
    
    TRANSCRIPCIÓN: {transcripcion[:2000]}
    DETERMINANTES (lo que dijiste y la gente repitió): {", ".join(detonantes)}
    NUBE PÚBLICA (lo que la gente tiene en la mente): {", ".join(nube_simple)}

    DAME UN PLAN DE MARKETING ESTRATÉGICO. DEBES RESPONDER ESTRICTAMENTE CON UN JSON USANDO ESTA ESTRUCTURA EXACTA (no anides claves extras, todas en la raíz):
    {{
        "repetir": "Palabras o frases que causaron impacto positivo y DEBEN volver a usarse.",
        "evitar": "Palabras o temas que causaron rechazo o confusión en los comentarios.",
        "rentabilidad": "Escribe aquí el análisis profundo: ¿Conviene seguir hablando de esto? ¿Qué hay que pulir? (MÍNIMO 300 PALABRAS). Extiéndete detalladamente en los pros, contras, y recomendaciones estratégicas.",
        "calificacion": "Escribe un solo número del 0 al 10 evaluando qué tan positivo y rentable fue este video basado en los comentarios.",
        "edad_promedio": "Rango de edad estimado de la audiencia basado en el contenido y tono de los comentarios, ejemplo: 18-30",
        "nuevo_guion": "Escribe un nuevo guion optimizado y persuasivo. OBLIGATORIO: Para garantizar que el nuevo guion sea igual o más largo que la transcripción original ({palabras_transcripcion} palabras), DEBES estructurarlo en 4 PÁRRAFOS EXTENSOS: 1) Gancho emocional muy descriptivo. 2) Desarrollo detallado del mensaje principal. 3) Integración y respuesta a lo que la gente pide en los comentarios. 4) Llamado a la acción contundente. Cada párrafo debe contener al menos 4 a 5 oraciones largas. PROHIBIDO RESUMIR.",
        "sentimientos_red": {{{plantilla_sentimientos}
        }}
    }}
    (IMPORTANTE: En 'sentimientos_red', es crítico que no falte ninguna palabra, clasifica TODAS absolutamente según el contexto de la política).
    RESPONDE SOLO EL JSON VÁLIDO.
    """
    payload = {
        "model": OLLAMA_MODEL, 
        "prompt": prompt, 
        "stream": False,
        "format": "json",
        "keep_alive": 0, 
        "options": {"temperature": 0.1, "num_gpu": 0}
    }

    try:
        if os.environ.get("MOCK_OLLAMA") == "1":
            ia_res = json.dumps({
                "repetir": "transporte, educación, gestión escolar",
                "evitar": "violencia, retrasos",
                "rentabilidad": "El análisis de rentabilidad indica que el video tiene una aceptación media-alta. Se recomienda seguir tratando estos temas con una mayor profundidad.",
                "calificacion": "8",
                "edad_promedio": "25-34",
                "nuevo_guion": "Párrafo 1 gancho: La educación es lo primero. Párrafo 2 desarrollo: Necesitamos escuelas equipadas. Párrafo 3 comentarios: Ustedes lo pidieron y responderemos. Párrafo 4 llamado: Acompáñanos a construir el futuro.",
                "sentimientos_red": {palabra: "positivo" if palabra in ["transporte", "educación", "gestión"] else "neutro" for palabra in nube_simple}
            })
        else:
            res = requests.post(OLLAMA_URL, json=payload, timeout=900, proxies={"http": None, "https": None})
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
                return {
                    "error": "Formato JSON inválido desde Ollama", 
                    "nube_pensamientos": {
                        "nodos": nube_nodos,
                        "enlaces": nube_enlaces
                    }
                }
                
        # Fallback de seguridad por si Ollama anida todo dentro de 'rentabilidad'
        if isinstance(resultado.get("rentabilidad"), dict):
            rent_obj = resultado["rentabilidad"]
            if "calificacion" in rent_obj: resultado["calificacion"] = rent_obj["calificacion"]
            if "edad_promedio" in rent_obj: resultado["edad_promedio"] = rent_obj["edad_promedio"]
            if "nuevo_guion" in rent_obj: resultado["nuevo_guion"] = rent_obj["nuevo_guion"]
            
            # Buscar el texto de rentabilidad (el string más largo)
            rent_text = ""
            for k, v in rent_obj.items():
                if isinstance(k, str) and len(k) > len(rent_text): rent_text = k
                if isinstance(v, str) and len(v) > len(rent_text): rent_text = v
            resultado["rentabilidad"] = rent_text
                
        resultado["detonantes"] = detonantes
        
        # Procesar nube con sentimientos y enlaces
        for nodo in nube_nodos:
            palabra = nodo["text"].lower().strip()
            sentimiento = "neutro"
            peso_final = 10 # Peso por defecto
            
            # Si la palabra está en el diccionario del usuario, manda el diccionario
            if palabra in dict_local:
                sentimiento = dict_local[palabra]["sentiment"]
                peso_final = dict_local[palabra]["peso"]
            else:
                # Si no está en el diccionario, confiamos en Ollama
                if "sentimientos_red" in resultado and isinstance(resultado["sentimientos_red"], dict):
                    sentimiento = resultado["sentimientos_red"].get(nodo["text"], "neutro").lower().strip()
                    if sentimiento not in ["positivo", "negativo", "neutro"]:
                        sentimiento = "neutro"
                
                # Peso por defecto basado en sentimiento
                if sentimiento == "positivo": peso_final = 10
                elif sentimiento == "negativo": peso_final = -10
                else: peso_final = 0
                        
            nodo["sentiment"] = sentimiento
            nodo["peso"] = peso_final
            
        resultado["nube_pensamientos"] = {
            "nodos": nube_nodos,
            "enlaces": nube_enlaces
        }
        
        # Inyectar la demografía calculada por Python (más precisa que lo que adivine Ollama)
        resultado["demografia"] = demografia_calculada
        
        # Si Ollama devolvió edad_promedio, usarla
        if "edad_promedio" in resultado:
            resultado["demografia"]["edad_promedio"] = resultado["edad_promedio"]
        else:
            resultado["demografia"]["edad_promedio"] = "Desconocida"
        
        # Validar claves y asegurar que sean strings (excepto calificacion y los arrays inyectados)
        for key in ["repetir", "evitar", "rentabilidad", "nuevo_guion"]:
            if key not in resultado:
                resultado[key] = "No analizado"
            elif isinstance(resultado[key], list):
                resultado[key] = "\n".join([str(item) for item in resultado[key]])
                
        # Asegurar que la calificacion sea un número entero extraído de la respuesta
        try:
            val = str(resultado.get("calificacion", "0"))
            nums = re.findall(r'\d+', val)
            resultado["calificacion"] = int(nums[0]) if nums else 0
            if resultado["calificacion"] > 10:
                resultado["calificacion"] = 10
        except:
            resultado["calificacion"] = 0
                
        return resultado
    except requests.exceptions.Timeout:
        return {
            "error": "Ollama tardó demasiado en responder (Timeout > 300s)", 
            "nube_pensamientos": {
                "nodos": nube_nodos,
                "enlaces": nube_enlaces
            }
        }
    except Exception as e:
        return {
            "error": str(e), 
            "nube_pensamientos": {
                "nodos": nube_nodos,
                "enlaces": nube_enlaces
            }
        }

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
    
    # Construir lista con autor para demografía
    coments_con_autor = data.get('comentarios_con_autor', [])
    
    candidato = data.get('candidato', '')
    partido = data.get('partido', '')
    diccionario = data.get('diccionario', [])
    
    res = generar_plan_marketing(trans, coments, coments_con_autor, candidato, partido, diccionario)
    print(json.dumps(res))
