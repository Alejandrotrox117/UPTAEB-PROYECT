import serial
import serial.tools.list_ports
import time
import sys
import re
import json
import os
from datetime import datetime
import signal
from collections import deque

# Variables globales para manejo de señales
running = True

def signal_handler(sig, frame):
    """Maneja la señal Ctrl+C para cerrar el programa limpiamente"""
    global running
    print('\n\nRecibida señal de interrupción (Ctrl+C)')
    print('Cerrando programa...')
    running = False

def read_weight_continuous():
    """
    Lector continuo de peso que detecta el peso más estable
    """
    global running
    
    # Configuración del puerto serial para balanza
    PORT = 'COM4'
    BAUDRATE = 9600
    TIMEOUT = 0.1
    
    # Configuración de estabilidad
    LECTURAS_PARA_ESTABILIDAD = 8  # Número de lecturas consecutivas para considerar estable
    VENTANA_ESTABILIDAD = 10       # Ventana de lecturas para analizar
    TOLERANCIA_VARIACION = 2       # Tolerancia en kg para considerar peso estable
    TIEMPO_ESPERA_ESTABILIDAD = 3  # Segundos mínimos entre registros de peso
    
    # Configuración de archivos
    DATA_FOLDER = 'C:/com_data/'
    if not os.path.exists(DATA_FOLDER):
        os.makedirs(DATA_FOLDER)
    
    # Archivos de salida
    PESO_TXT_FILE = os.path.join(DATA_FOLDER, 'peso_actual.txt')
    PESO_MYSQL_FILE = os.path.join(DATA_FOLDER, 'peso_mysql.txt')  # Archivo para MySQL
    PESO_HISTORICO_FILE = os.path.join(DATA_FOLDER, 'historial_pesos.txt')
    LOG_FILE = os.path.join(DATA_FOLDER, 'lector_peso.log')
    
    def log_message(message):
        timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        log_entry = f"[{timestamp}] {message}"
        print(log_entry)
        try:
            with open(LOG_FILE, 'a', encoding='utf-8') as f:
                f.write(log_entry + '\n')
        except:
            pass
    
    def extraer_peso_numerico(texto):
        """Extrae el valor numérico del peso desde el texto"""
        try:
            # Buscar patrones como: 1234.5kg, 1234kg, 1234.5 kg, etc.
            patron = r'(\d+(?:\.\d+)?)\s*kg'
            match = re.search(patron, texto.lower())
            if match:
                return float(match.group(1))
            
            # Buscar solo números
            patron = r'(\d+(?:\.\d+)?)'
            match = re.search(patron, texto)
            if match:
                return float(match.group(1))
            
            return None
        except:
            return None
    
    def guardar_peso_estable(peso_texto, peso_numerico, estado_estabilidad):
        """Guarda el peso estable con información adicional"""
        try:
            timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            with open(PESO_TXT_FILE, 'w', encoding='utf-8') as f:
                f.write(f"PESO ESTABLE: {peso_texto}\n")
                f.write(f"PESO NUMERICO: {peso_numerico:.1f} kg\n")
                f.write(f"ESTADO: {estado_estabilidad}\n")
                f.write(f"FECHA: {timestamp}\n")
                f.write(f"LECTURAS CONSECUTIVAS: {LECTURAS_PARA_ESTABILIDAD}\n")
            return True
        except Exception as e:
            log_message(f"Error al guardar peso estable: {e}")
            return False
    
    def guardar_peso_mysql(peso_texto, peso_numerico, estado_estabilidad):
        """Guarda el peso en formato compatible con MySQL/PHP"""
        try:
            timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            
            # Crear datos en formato delimitado por pipes (|) para fácil lectura en PHP
            datos_mysql = f"{timestamp}|{peso_numerico:.2f}|{peso_texto}|{estado_estabilidad}|PROCESADO"
            
            # Sobrescribir archivo para MySQL (siempre el último peso estable)
            with open(PESO_MYSQL_FILE, 'w', encoding='utf-8') as f:
                f.write(datos_mysql)
            
            log_message(f"Peso guardado para MySQL: {datos_mysql}")
            return True
        except Exception as e:
            log_message(f"Error al guardar peso MySQL: {e}")
            return False
    
    def guardar_peso_mysql_json(peso_texto, peso_numerico, estado_estabilidad):
        """Guarda el peso en formato JSON para MySQL/PHP (alternativa)"""
        try:
            timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            
            # Crear JSON con los datos
            datos_json = {
                "fecha_hora": timestamp,
                "peso_numerico": round(peso_numerico, 2),
                "peso_texto": peso_texto,
                "estado": estado_estabilidad,
                "variacion": estado_estabilidad.split(",")[0].replace("Var:", "").replace("kg", "").strip() if "Var:" in estado_estabilidad else "0",
                "promedio": round(peso_numerico, 2),
                "status": "PENDIENTE",
                "procesado": False
            }
            
            # Guardar JSON
            with open(PESO_MYSQL_FILE.replace('.txt', '.json'), 'w', encoding='utf-8') as f:
                json.dump(datos_json, f, indent=2, ensure_ascii=False)
            
            return True
        except Exception as e:
            log_message(f"Error al guardar peso MySQL JSON: {e}")
            return False
    
    def agregar_a_historico(peso_texto, peso_numerico, estado):
        """Agrega el peso al archivo histórico"""
        try:
            timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            with open(PESO_HISTORICO_FILE, 'a', encoding='utf-8') as f:
                f.write(f"{timestamp} | {peso_texto} | {peso_numerico:.1f}kg | {estado}\n")
            return True
        except Exception as e:
            log_message(f"Error al agregar al histórico: {e}")
            return False
    
    def es_peso_valido(linea):
        """Verifica si la línea contiene un peso válido"""
        if not linea or len(linea.strip()) == 0:
            return False
        
        # Filtrar líneas como '0kg G' o '0kg N'
        linea_limpia = linea.lower().strip()
        patron_exclusion = r'^0kg\s+[gnt]\s*$'
        if re.match(patron_exclusion, linea_limpia):
            return False
        
        # Verificar que contenga números
        peso_numerico = extraer_peso_numerico(linea)
        if peso_numerico is not None and peso_numerico > 0:
            return True
        
        return False
    
    def analizar_estabilidad(ventana_pesos):
        """Analiza si los pesos en la ventana son estables"""
        if len(ventana_pesos) < LECTURAS_PARA_ESTABILIDAD:
            return False, 0, "Insuficientes lecturas"
        
        # Obtener los últimos N pesos para análisis
        ultimos_pesos = list(ventana_pesos)[-LECTURAS_PARA_ESTABILIDAD:]
        
        if not ultimos_pesos:
            return False, 0, "Sin datos"
        
        # Calcular promedio y variación
        promedio = sum(ultimos_pesos) / len(ultimos_pesos)
        max_peso = max(ultimos_pesos)
        min_peso = min(ultimos_pesos)
        variacion = max_peso - min_peso
        
        # Verificar estabilidad
        estable = variacion <= TOLERANCIA_VARIACION
        
        estado = f"Var:{variacion:.1f}kg, Prom:{promedio:.1f}kg"
        
        return estable, promedio, estado
    
    # Configurar manejador de señales
    signal.signal(signal.SIGINT, signal_handler)
    
    print("=" * 70)
    print("🔧 LECTOR DE PESO ESTABLE - PUERTO COM5")
    print("=" * 70)
    print(f"📁 Archivos generados en: {DATA_FOLDER}")
    print(f"📄 Peso actual: {PESO_TXT_FILE}")
    print(f"🗄️  Peso MySQL: {PESO_MYSQL_FILE}")
    print(f"📋 Historial: {PESO_HISTORICO_FILE}")
    print(f"📝 Log: {LOG_FILE}")
    print("=" * 70)
    print("⚙️  CONFIGURACIÓN DE ESTABILIDAD:")
    print(f"   • Lecturas para estabilidad: {LECTURAS_PARA_ESTABILIDAD}")
    print(f"   • Tolerancia de variación: ±{TOLERANCIA_VARIACION} kg")
    print(f"   • Tiempo mínimo entre registros: {TIEMPO_ESPERA_ESTABILIDAD}s")
    print("=" * 70)
    print("⚖️  Iniciando lectura de balanza...")
    print("🛑 Presiona Ctrl+C para detener")
    print("=" * 70)
    
    # Limpiar archivos al iniciar
    for archivo in [PESO_TXT_FILE, PESO_MYSQL_FILE]:
        if os.path.exists(archivo):
            try:
                os.remove(archivo)
                log_message(f"Archivo {archivo} anterior eliminado")
            except:
                log_message(f"No se pudo eliminar {archivo}")
    
    # Variables de estado para estabilidad
    ventana_pesos = deque(maxlen=VENTANA_ESTABILIDAD)  # Ventana deslizante de pesos
    ultimo_peso_registrado = None
    ultimo_tiempo_registro = 0
    contador_lecturas = 0
    pesos_detectados = 0
    pesos_estables_registrados = 0
    buffer_recepcion = ''
    
    try:
        # Inicializar conexión serial
        ser = serial.Serial(
            port=PORT,
            baudrate=BAUDRATE,
            bytesize=serial.EIGHTBITS,
            parity=serial.PARITY_NONE,
            stopbits=serial.STOPBITS_ONE,
            timeout=TIMEOUT
        )
        
        log_message(f"Puerto {PORT} abierto exitosamente a {BAUDRATE} baudios")
        
        # Bucle principal
        while running:
            contador_lecturas += 1
            
            # Mostrar progreso cada 50 lecturas
            if contador_lecturas % 50 == 0:
                ventana_info = f"Ventana: {len(ventana_pesos)} pesos"
                if ventana_pesos:
                    promedio_ventana = sum(ventana_pesos) / len(ventana_pesos)
                    ventana_info += f", Promedio: {promedio_ventana:.1f}kg"
                
                print(f"📊 Lecturas: {contador_lecturas} | Detectados: {pesos_detectados} | Estables: {pesos_estables_registrados}")
                print(f"   {ventana_info}")
            
            # Leer del puerto
            if ser.in_waiting > 0:
                try:
                    datos_nuevos = ser.read(ser.in_waiting).decode('utf-8', errors='ignore')
                    if datos_nuevos:
                        buffer_recepcion += datos_nuevos
                        
                        # Procesar mensajes completos
                        while '\r\n' in buffer_recepcion:
                            pos = buffer_recepcion.find('\r\n')
                            mensaje = buffer_recepcion[:pos].strip()
                            buffer_recepcion = buffer_recepcion[pos + 2:]
                            
                            if mensaje and es_peso_valido(mensaje):
                                peso_numerico = extraer_peso_numerico(mensaje)
                                
                                if peso_numerico is not None:
                                    # Agregar peso a la ventana
                                    ventana_pesos.append(peso_numerico)
                                    pesos_detectados += 1
                                    
                                    # Mostrar peso actual
                                    if contador_lecturas % 25 == 0:  # Mostrar cada 25 lecturas
                                        print(f"⚖️  Peso actual: {mensaje} ({peso_numerico:.1f} kg)")
                                    
                                    # Analizar estabilidad
                                    estable, promedio, estado = analizar_estabilidad(ventana_pesos)
                                    
                                    if estable:
                                        tiempo_actual = time.time()
                                        tiempo_transcurrido = tiempo_actual - ultimo_tiempo_registro
                                        
                                        # Verificar si ha pasado suficiente tiempo desde el último registro
                                        if tiempo_transcurrido >= TIEMPO_ESPERA_ESTABILIDAD:
                                            # Verificar si el peso es significativamente diferente al último registrado
                                            if (ultimo_peso_registrado is None or 
                                                abs(promedio - ultimo_peso_registrado) > TOLERANCIA_VARIACION):
                                                
                                                log_message(f"🎯 PESO ESTABLE DETECTADO: {mensaje} (Promedio: {promedio:.1f}kg)")
                                                print(f"🎯 PESO ESTABLE REGISTRADO: {mensaje}")
                                                print(f"   📈 Promedio de estabilidad: {promedio:.1f} kg")
                                                print(f"   📊 {estado}")
                                                
                                                # Guardar peso estable (formato normal)
                                                if guardar_peso_estable(mensaje, promedio, estado):
                                                    ultimo_peso_registrado = promedio
                                                    ultimo_tiempo_registro = tiempo_actual
                                                    pesos_estables_registrados += 1
                                                    
                                                    # Guardar peso para MySQL
                                                    if guardar_peso_mysql(mensaje, promedio, estado):
                                                        print(f"💾 PESO GUARDADO PARA MYSQL: {promedio:.2f} kg")
                                                    
                                                    # Guardar peso JSON para MySQL (alternativa)
                                                    guardar_peso_mysql_json(mensaje, promedio, estado)
                                                    
                                                    # Agregar al historial
                                                    agregar_a_historico(mensaje, promedio, "ESTABLE")
                                                    
                                                    print(f"💾 PESO ESTABLE GUARDADO: {promedio:.1f} kg")
                                                else:
                                                    print(f"❌ Error al guardar peso estable")
                                            else:
                                                if contador_lecturas % 100 == 0:  # Mostrar ocasionalmente
                                                    print(f"🔄 Peso estable similar al anterior: {promedio:.1f} kg")
                                        else:
                                            tiempo_restante = TIEMPO_ESPERA_ESTABILIDAD - tiempo_transcurrido
                                            if contador_lecturas % 100 == 0:  # Mostrar ocasionalmente
                                                print(f"⏳ Peso estable detectado, esperando {tiempo_restante:.1f}s más")
                            
    
                except Exception as e:
                    log_message(f"Error al leer datos: {e}")
            
            # Pausa corta entre lecturas
            time.sleep(0.1)
            
    except serial.SerialException as e:
        log_message(f"❌ Error de puerto serial: {e}")
        print(f"\n❌ ERROR DE CONEXIÓN:")
        print(f"   No se pudo conectar al puerto {PORT}")
        print(f"   Verifica que:")
        print(f"   - La balanza esté conectada")
        print(f"   - El puerto {PORT} sea correcto")
        print(f"   - No haya otra aplicación usando el puerto")
        
        # Mostrar puertos disponibles
        try:
            ports = serial.tools.list_ports.comports()
            if ports:
                print(f"\n📋 Puertos disponibles:")
                for port in ports:
                    print(f"   - {port.device}: {port.description}")
            else:
                print("\n📋 No se encontraron puertos COM disponibles")
        except Exception as ex:
            print(f"Error al listar puertos: {ex}")
            
    except KeyboardInterrupt:
        log_message("Programa interrumpido por el usuario (Ctrl+C)")
        print("\n👋 Programa detenido por el usuario")
        
    except Exception as e:
        log_message(f"❌ Error inesperado: {e}")
        print(f"❌ Error inesperado: {e}")
        
    finally:
        # Cerrar conexión
        if 'ser' in locals() and ser.is_open:
            ser.close()
            log_message("Conexión serial cerrada")
        
        # Resumen final
        print("\n" + "=" * 70)
        print("📊 RESUMEN DE SESIÓN - DETECCIÓN DE PESO ESTABLE")
        print("=" * 70)
        print(f"📈 Total de lecturas realizadas: {contador_lecturas}")
        print(f"⚖️  Total de pesos detectados: {pesos_detectados}")
        print(f"🎯 Total de pesos estables registrados: {pesos_estables_registrados}")
        print(f"📄 Último peso estable: {ultimo_peso_registrado:.1f} kg" if ultimo_peso_registrado else "📄 Sin pesos estables registrados")
        print(f"📁 Archivos generados en: {DATA_FOLDER}")
        if os.path.exists(PESO_TXT_FILE):
            print(f"✅ Archivo de peso actual: {PESO_TXT_FILE}")
        if os.path.exists(PESO_MYSQL_FILE):
            print(f"✅ Archivo MySQL: {PESO_MYSQL_FILE}")
        if os.path.exists(PESO_HISTORICO_FILE):
            print(f"✅ Archivo de historial: {PESO_HISTORICO_FILE}")
        print("=" * 70)
        print("👋 Programa finalizado")

if __name__ == "__main__":
    # Ejecutar automáticamente el lector continuo
    read_weight_continuous()