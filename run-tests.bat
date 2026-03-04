@echo off
REM ============================================================
REM  run-tests.bat — Ejecutar pruebas PHPUnit de forma masiva
REM  Uso:
REM    run-tests.bat              Ejecuta TODAS las pruebas
REM    run-tests.bat Categorias   Ejecuta solo la suite Categorias
REM    run-tests.bat unit         Ejecuta solo tests del grupo @unit
REM    run-tests.bat --help       Muestra ayuda
REM ============================================================

setlocal enabledelayedexpansion

set PHP=php
set PHPUNIT=vendor\bin\phpunit
set REPORT_DIR=test-reports

if not exist "%PHPUNIT%" (
    echo [ERROR] No se encontro PHPUnit en %PHPUNIT%
    echo Ejecute: composer install
    exit /b 1
)

if not exist "%REPORT_DIR%" mkdir "%REPORT_DIR%"

if "%1"=="--help" goto :help
if "%1"=="-h" goto :help

REM Sin argumentos: ejecutar todo
if "%1"=="" (
    echo.
    echo ========================================
    echo  EJECUTANDO TODAS LAS PRUEBAS
    echo ========================================
    echo.
    %PHP% %PHPUNIT% --testdox --no-progress --colors=always
    echo.
    echo Reportes generados en: %REPORT_DIR%\
    goto :end
)

REM Argumento "unit": ejecutar solo tests unitarios puros
if /i "%1"=="unit" (
    echo.
    echo ========================================
    echo  EJECUTANDO TESTS UNITARIOS PUROS
    echo ========================================
    echo.
    %PHP% %PHPUNIT% --group unit --testdox --no-progress --no-logging --no-coverage --colors=always
    goto :end
)

REM Argumento "rapido": ejecutar sin reportes, sin testdox
if /i "%1"=="rapido" (
    echo.
    echo ========================================
    echo  EJECUCION RAPIDA (sin reportes)
    echo ========================================
    echo.
    %PHP% %PHPUNIT% --no-progress --no-logging
    goto :end
)

REM Argumento "filtro:texto": filtrar por nombre de test
if "%1:~0,7%"=="filtro:" (
    set FILTRO=%1:~7%
    echo.
    echo Filtrando tests que contengan: !FILTRO!
    echo.
    %PHP% %PHPUNIT% --filter "!FILTRO!" --testdox --no-progress --colors=always
    goto :end
)

REM Cualquier otro argumento: nombre de suite
echo.
echo ========================================
echo  EJECUTANDO SUITE: %1
echo ========================================
echo.
%PHP% %PHPUNIT% --testsuite %1 --testdox --no-progress --colors=always
goto :end

:help
echo.
echo USO: run-tests.bat [opcion]
echo.
echo Opciones:
echo   (sin args)         Ejecuta TODAS las pruebas con reportes
echo   unit               Solo pruebas unitarias puras (sin BD)
echo   rapido             Ejecucion rapida sin reportes ni testdox
echo   filtro:texto       Filtra tests por nombre (ej: filtro:Categoria)
echo   [NombreSuite]      Ejecuta una suite especifica
echo   --help, -h         Muestra esta ayuda
echo.
echo Suites disponibles:
echo   Categorias, Productos, Clientes, Empleados, Proveedores,
echo   Usuarios, Login, Ventas, Romana, ProduccionProcesos,
echo   Movimientos, Compras, BcvScraper, Bitacora, Dashboard,
echo   Roles, Sueldos, Pagos, Integracion
echo.
echo Ejemplos:
echo   run-tests.bat                    Todas las pruebas
echo   run-tests.bat Categorias         Solo Categorias
echo   run-tests.bat unit               Solo unit tests puros
echo   run-tests.bat filtro:Insert      Tests que contengan "Insert"
echo   run-tests.bat rapido             Sin testdox ni reportes
echo.
goto :end

:end
endlocal
