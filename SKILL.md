---
name: Creador de Skills
description: Skill para asistir en la creación de nuevas skills dentro del workspace.
---

# Creador de Skills

Esta skill proporciona instrucciones y una guía paso a paso para que el agente cree nuevas skills de manera correcta y consistente.

## Estructura de una Skill

Todas las skills deben residir en la carpeta `.agent/skills/`. Cada skill tiene su propio subdirectorio, por ejemplo `.agent/skills/mi_nueva_skill/`.

El archivo más importante es `SKILL.md`, que debe estar en la raíz del directorio de la skill.

## Proceso de Creación

Para crear una nueva skill, sigue estos pasos:

1.  **Recopilar Información**: Asegúrate de tener:
    *   **Nombre de la Skill**: Un nombre legible para humanos (ej. "Gestor de Base de Datos").
    *   **Slug de la Skill**: Un identificador único para la carpeta, en minúsculas y usando guiones bajos (ej. `gestor_db`).
    *   **Descripción**: Una descripción breve de qué hace la skill.
    *   **Instrucciones**: Los pasos detallados que el agente debe seguir al usar la skill.

2.  **Crear Directorio**:
    *   Crea la carpeta `.agent/skills/<slug_de_la_skill>`.

3.  **Crear Archivo SKILL.md**:
    *   Crea el archivo `.agent/skills/<slug_de_la_skill>/SKILL.md`.
    *   El archivo **DEBE** incluir el frontmatter YAML al principio:
        ```yaml
        ---
        name: AQUI_EL_NOMBRE
        description: AQUI_LA_DESCRIPCION
        ---
        ```
    *   A continuación del frontmatter, escribe las instrucciones en formato Markdown.

4.  **Recursos Adicionales (Opcional)**:
    *   Si la skill necesita scripts, plantillas o ejemplos, crea las carpetas `scripts/`, `templates/` o `examples/` dentro del directorio de la skill y agrega los archivos correspondientes.

## Reglas Importantes

*   **Idioma**: A menos que el usuario especifique lo contrario, crea las instrucciones de la nueva skill en Español.
*   **Claridad**: Las instrucciones deben ser claras y directas para que el agente pueda ejecutarlas sin ambigüedad.
*   **Formato de Archivo**: Siempre usa `.md` para el archivo de definición de la skill.
*   **Rutas**: Usa rutas absolutas o relativas desde la raíz del workspace `.agent/skills/...`.

## Ejemplo

Si el usuario pide una skill para saludar:

Archivo: `.agent/skills/saludador/SKILL.md`

```markdown
---
name: Saludador Pro
description: Saluda al usuario de forma entusiasta.
---

# Instrucciones para Saludador Pro

1.  Saluda al usuario con un "¡Hola!" muy enérgico.
2.  Ofrece ayuda inmediata.
```
