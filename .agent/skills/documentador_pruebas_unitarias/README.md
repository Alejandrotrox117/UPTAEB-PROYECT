# Documentador de Pruebas Unitarias

Skill para generar documentación estructurada y completa de las pruebas unitarias del proyecto.

## 📋 Descripción

Esta skill automatiza el proceso de documentación de pruebas unitarias, generando documentos estructurados que incluyen:

- Objetivos de las pruebas
- Técnicas utilizadas
- Código completo de las pruebas
- Casos de prueba detallados
- Resultados de ejecución real
- Observaciones y análisis

## 🎯 Casos de Uso

### Uso básico
```
Usuario: "Documenta las pruebas de Compras"
Usuario: "Genera la documentación para el módulo de Pagos"
Usuario: "Documenta las pruebas unitarias de Productos"
```

### Uso con código de requisito
```
Usuario: "Documenta Compras como RF03"
Usuario: "Genera documentación de Pagos (RF05)"
```

### Documentación múltiple
```
Usuario: "Documenta Compras y después Pagos"
Usuario: "Necesito documentar los módulos de Productos, Proveedores y Pagos"
```

## 📁 Estructura de Archivos

```
.agent/skills/documentador_pruebas_unitarias/
├── SKILL.md                          # Instrucciones principales
├── README.md                         # Este archivo
├── templates/
│   └── plantilla_documentacion.md   # Plantilla de referencia
└── examples/
    └── ejemplo_compras.md           # Ejemplo completo
```

## 🔧 Prerrequisitos

- PHPUnit configurado en el proyecto
- Pruebas unitarias en `tests/unitTest/<Modulo>/`
- PHP disponible en la línea de comandos

## 📊 Estructura de la Documentación Generada

Cada módulo genera un documento con:

1. **Cuadro Nº X**: Encabezado con nombre del módulo y código RF
2. **Objetivos de la prueba**: Qué se valida y qué debe rechazar el sistema
3. **Técnicas**: Metodología de prueba utilizada
4. **Código Involucrado**: Código completo de todas las pruebas
5. **Caso de prueba**: Tipo, objetivo, descripción, entradas y salidas esperadas
6. **Resultado**: Salida real de la ejecución de PHPUnit
7. **Observaciones**: Análisis de los resultados

## 💡 Módulos Disponibles

Módulos con pruebas unitarias en el proyecto:

- Compra
- Movimientos
- Pagos
- Produccion
- Productos
- Proveedores
- Roles

## ⚙️ Comandos PHPUnit Útiles

```bash
# Ejecutar pruebas de un módulo específico
php vendor/bin/phpunit tests/unitTest/Compra/

# Ejecutar con configuración
php vendor/bin/phpunit --configuration phpunit.xml tests/unitTest/Pagos/

# Ver salida detallada
php vendor/bin/phpunit --testdox tests/unitTest/Productos/

# Ejecutar todo
php vendor/bin/phpunit tests/unitTest/
```

## 📝 Ejemplo de Flujo

1. **Usuario solicita**: "Documenta las pruebas de Pagos"

2. **El agente ejecuta**:
   - Lista archivos en `tests/unitTest/Pagos/`
   - Lee todos los archivos `.php`
   - Une el código
   - Ejecuta `php vendor/bin/phpunit tests/unitTest/Pagos/`
   - Analiza los resultados

3. **Genera documentación** siguiendo la plantilla establecida

4. **Usuario recibe**: Documento completo listo para copiar y pegar

## 🎓 Mejores Prácticas

- Documenta un módulo a la vez para mayor claridad
- Revisa que las pruebas pasen antes de documentar
- Usa códigos RF secuenciales para múltiples módulos
- Mantén la numeración de cuadros consistente

## 🔍 Solución de Problemas

**No se encuentran pruebas**
- Verifica que exista el directorio `tests/unitTest/<Modulo>/`
- Confirma que hay archivos `.php` en el directorio

**Errores al ejecutar PHPUnit**
- El agente incluirá los errores en la documentación
- Revisa la configuración de PHPUnit
- Verifica que todas las dependencias estén instaladas

**Formato incorrecto**
- La skill sigue el formato establecido automáticamente
- Si necesitas personalizar, edita la plantilla en `templates/`

## 📚 Referencias

- [Plantilla de documentación](templates/plantilla_documentacion.md)
- [Ejemplo: Módulo de Compras](examples/ejemplo_compras.md)
- [PHPUnit Documentation](https://phpunit.de/)

---

**Versión:** 1.0
**Fecha:** 5 de marzo de 2026
**Autor:** Generado por Creador de Skills
