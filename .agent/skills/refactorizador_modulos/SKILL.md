---
name: Refactorizador de Módulos (Dual-Instance Pattern)
description: Refactoriza controladores y modelos para usar un patrón de doble instancia con nomenclatura específica y carga diferida.
---

# Refactorizador de Módulos

Esta skill permite refactorizar un módulo completo (Controlador y Modelo) siguiendo un patrón de diseño de "doble instancia" (proxy) y una nomenclatura estandarizada.

## Parámetros de Entrada
*   **Nombre del Módulo**: El nombre identificador del módulo (ej. `Proveedores`, `Clientes`, `Productos`).

## Instrucciones de Refactorización

### 1. En el Controlador (`app/Controllers/<Modulo>.php`)

*   **Función de Fábrica**: Agregue o modifique la función para obtener el modelo. Debe llamarse `get<Modulo>Model()`.
    ```php
    function get<Modulo>Model() {
        return new <Modulo>Model();
    }
    ```
*   **Nomenclatura de Objetos**: En cada función del controlador que use el modelo:
    1.  Cree una variable local llamada `$obj<Modulo>`.
    2.  Instánciela usando la función de fábrica: `$obj<Modulo> = get<Modulo>Model();`.
    3.  Reemplace todas las llamadas al modelo usando esta nueva variable.

### 2. En el Modelo (`app/Models/<Modulo>Model.php`)

*   **Propiedad de Instancia**: Agregue una propiedad privada llamada `$obj<Modulo>Model` (o el nombre específico del modelo).
*   **Gestión de Instancias (Lazy Load)**: Agregue un método privado `getInstanciaModel()` que asegure la coexistencia de dos instancias:
    ```php
    private function getInstanciaModel() {
        if ($this->obj<Modulo>Model == null) {
            $this->obj<Modulo>Model = new <Modulo>Model();
        }
        return $this->obj<Modulo>Model;
    }
    ```
*   **Métodos Públicos (Proxies)**: Todas las funciones públicas deben:
    1.  Obtener la instancia interna: `$obj<Modulo>Model = $this->getInstanciaModel();`.
    2.  Delegar la ejecución a un método privado correspondiente.
    3.  No contener lógica de base de datos directa.
*   **Métodos Privados (Trabajadores)**:
    1.  Mueva toda la lógica de acceso a datos a métodos privados.
    2.  Siga la nomenclatura: `ejecutar<Accion><Entidad>` (ej. `ejecutarBusquedaProveedor`, `ejecutarInsercionProveedor`).
    3.  Asegure el cierre de conexiones en el bloque `finally`.

## Reglas de Seguridad
*   Cada método privado debe abrir y cerrar su propia conexión a la base de datos (usando `new Conexion()`).
*   No heredar de la clase `Mysql` (eliminar `extends Mysql`).
*   No usar el constructor para inicializar conexiones globales.
