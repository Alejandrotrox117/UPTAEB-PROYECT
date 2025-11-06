# Guía rápida: Instalar y ejecutar PHPUnit (Windows + Composer)

Esta guía explica paso a paso cómo instalar PHPUnit en este proyecto y ejecutar la primera prueba en un entorno Windows usando Composer. Incluye soluciones a errores comunes que pueden aparecer en entornos compartidos.

Requisitos
- PHP CLI (versión usada en el proyecto: PHP 8.2.20). Compruébalo con:
  ```powershell
  php -v
  ```
- Composer (instalado globalmente). Comprueba con:
  ```powershell
  composer --version
  ```
- Acceso al proyecto (clonado) y permisos de usuario apropiados.

Instalación (pasos recomendados)

1. Abrir PowerShell en la raíz del proyecto (ej. `C:\xampp\htdocs\project`).

2. Añadir la dependencia de PHPUnit (Composer detecta tu versión de PHP y elegirá la versión adecuada). Para PHP 8.2 se recomienda PHPUnit 10.x:
  ```powershell
  composer require --dev phpunit/phpunit:^10.0 -W
  ```

  Nota: el flag `-W` (o `--with-all-dependencies`) permite que Composer actualice paquetes relacionados que estén bloqueados en `composer.lock`.

3. Si Composer falla con un error de Git tipo "detected dubious ownership" sobre rutas en la caché VCS, añade esas rutas como `safe.directory` en Git. Por ejemplo (reemplaza la ruta que te muestre el error si es distinta):
  ```powershell
  git config --global --add safe.directory C:/Users/tu_usuario/AppData/Local/Composer/vcs/https---github.com-sebastianbergmann-phpunit.git
  ```

  Para añadir todas las entradas de la caché VCS de Composer (útil en máquinas compartidas), puedes ejecutar en PowerShell:
  ```powershell
  $dirs = Get-ChildItem -Directory "$env:LOCALAPPDATA\Composer\vcs" -ErrorAction SilentlyContinue; if ($dirs) { foreach ($d in $dirs) { git config --global --add safe.directory $d.FullName } }
  ```

4. Si Composer informa que no puede descargar archivos `.zip` porque falta la extensión `zip` en la CLI o faltan herramientas unzip/7z, tienes dos opciones:

  - Habilitar la extensión zip en PHP CLI:
    1. Localiza el `php.ini` que usa tu CLI (Composer muestra la ruta cuando falla, por ejemplo `C:\php\php.ini`).
    2. Edita ese archivo y descomenta/añade la línea:
       ```ini
       extension=zip
       ```
    3. Reinicia la terminal y vuelve a ejecutar Composer.

  - O instalar 7-Zip y asegurarte de que `7z.exe` esté en el PATH del sistema para que Composer pueda usarlo.

5. Después de una instalación correcta, Composer habrá creado/actualizado `vendor/` y `composer.lock`.

Crear y ejecutar la primera prueba (ya preparada en este proyecto)

- En este repositorio ya hay una prueba de ejemplo en `tests/ExampleTest.php` y un `phpunit.xml` básico.
- Ejecuta las pruebas con Composer (usa el script que añadimos):
  ```powershell
  composer test
  ```

  o directamente con el binario:
  ```powershell
  vendor\bin\phpunit
  ```

Salida esperada (ejemplo):

  PHPUnit 10.0.0 by Sebastian Bergmann and contributors.

  .                                                                   1 / 1 (100%)

  OK (1 test, 1 assertion)

---

## Estructura de Pruebas del Proyecto

Este proyecto cuenta con dos tipos de pruebas:

### 1. Pruebas Unitarias (`tests/`)

Las pruebas unitarias validan métodos individuales de cada modelo. Están organizadas por módulo:

```
tests/
├── Compra/              # Pruebas de compras (crear, editar, eliminar, consultar)
├── Ventas/              # Pruebas de ventas
├── Produccion/          # Pruebas de producción (lotes, registros, cierre)
├── Pagos/               # Pruebas de pagos
├── Sueldos/             # Pruebas de sueldos
├── Productos/           # Pruebas de productos
├── Roles/               # Pruebas de roles y permisos
└── ...                  # Otros módulos
```

**Ejecutar todas las pruebas unitarias:**
```powershell
.\vendor\bin\phpunit tests\ --exclude-group integration --testdox
```

**Ejecutar pruebas de un módulo específico:**
```powershell
.\vendor\bin\phpunit tests\Compra\ --testdox
.\vendor\bin\phpunit tests\Produccion\ --testdox
```

### 2. Pruebas de Integración (`tests/Integration/`)

Las pruebas de integración verifican que múltiples componentes funcionen correctamente juntos, simulando flujos completos de usuario.

**Tests disponibles:**

| Módulo | Archivo | Qué prueba |
|--------|---------|-----------|
| **Compras** | `CompraFlowIntegrationTest.php` | Flujo completo: Crear compra → Pago parcial → Pago total |
| **Ventas** | `VentaFlowIntegrationTest.php` | Flujo completo: Crear venta → Pagos → Balance actualizado |
| **Producción** | `ProduccionFlowIntegrationTest.php` | Ciclo de lote: PLANIFICADO → EN_PROCESO → FINALIZADO |

**Ejecutar todas las pruebas de integración:**
```powershell
.\vendor\bin\phpunit tests\Integration\ --testdox --colors=always
```

**Ejecutar prueba específica:**
```powershell
.\vendor\bin\phpunit tests\Integration\CompraFlowIntegrationTest.php --testdox
.\vendor\bin\phpunit tests\Integration\ProduccionFlowIntegrationTest.php --testdox
```

**Para más información sobre pruebas de integración**, consulta: `tests/Integration/README.md`

### Estadísticas de Pruebas

- **Pruebas Unitarias**: 100+ tests en 27 archivos
- **Pruebas de Integración**: 7 tests en 3 archivos
- **Total de Aserciones**: 150+ validaciones
- **Cobertura**: Compras, Ventas, Producción, Pagos, Sueldos, Productos, Roles, y más

---

Buenas prácticas y notas adicionales
- Añade tus tests bajo la carpeta `tests/` con nombres `*Test.php`.
- Usa `phpunit.xml` para configurar bootstrap, cobertura y suites.
- Si quieres generar reportes de cobertura necesitas Xdebug o PCOV.
- Para integrar en CI (GitHub Actions, GitLab CI), instala PHP y Composer en el runner y ejecuta `composer install --no-interaction --prefer-dist` seguido de `composer test`.

Errores comunes y soluciones rápidas
- Error: "The zip extension and unzip/7z commands are both missing" → habilitar extensión zip o instalar 7-Zip.
- Error: "detected dubious ownership in repository" → añadir la ruta a `git config --global --add safe.directory <ruta>` o ajustar permisos.
- Error: "phpunit/phpunit requires php >=X.Y" → instala la versión de PHPUnit compatible con tu PHP (ej.: PHP 8.2 → PHPUnit 10.x, PHP 7.4 → PHPUnit 9.5).

Contacto
- Si alguna instrucción falla en tu equipo, pega la salida del error y yo o alguien del equipo te ayudamos a resolverlo.
