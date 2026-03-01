#!/bin/bash
# ============================================================
#  run-tests.sh — Ejecutar pruebas PHPUnit de forma masiva
#  Uso:
#    ./run-tests.sh              Ejecuta TODAS las pruebas
#    ./run-tests.sh Categorias   Ejecuta solo la suite Categorias
#    ./run-tests.sh unit         Ejecuta solo tests del grupo @unit
#    ./run-tests.sh --help       Muestra ayuda
# ============================================================

PHP="php"
PHPUNIT="vendor/bin/phpunit"
REPORT_DIR="test-reports"

if [ ! -f "$PHPUNIT" ]; then
    echo "[ERROR] No se encontró PHPUnit en $PHPUNIT"
    echo "Ejecute: composer install"
    exit 1
fi

mkdir -p "$REPORT_DIR"

case "${1}" in
    --help|-h)
        echo ""
        echo "USO: ./run-tests.sh [opcion]"
        echo ""
        echo "Opciones:"
        echo "  (sin args)         Ejecuta TODAS las pruebas con reportes"
        echo "  unit               Solo pruebas unitarias puras (sin BD)"
        echo "  rapido             Ejecución rápida sin reportes ni testdox"
        echo "  filtro:texto       Filtra tests por nombre (ej: filtro:Categoria)"
        echo "  [NombreSuite]      Ejecuta una suite específica"
        echo "  --help, -h         Muestra esta ayuda"
        echo ""
        echo "Suites disponibles:"
        echo "  Categorias, Productos, Clientes, Empleados, Proveedores,"
        echo "  Usuarios, Login, Ventas, Romana, ProduccionProcesos,"
        echo "  Movimientos, Compras, BcvScraper, Bitacora, Dashboard,"
        echo "  Roles, Sueldos, Pagos, Integracion"
        echo ""
        echo "Ejemplos:"
        echo "  ./run-tests.sh                    Todas las pruebas"
        echo "  ./run-tests.sh Categorias         Solo Categorias"
        echo "  ./run-tests.sh unit               Solo unit tests puros"
        echo "  ./run-tests.sh filtro:Insert       Tests que contengan 'Insert'"
        echo "  ./run-tests.sh rapido             Sin testdox ni reportes"
        echo ""
        ;;
    "")
        echo ""
        echo "========================================"
        echo " EJECUTANDO TODAS LAS PRUEBAS"
        echo "========================================"
        echo ""
        $PHP $PHPUNIT --testdox --no-progress --colors=always
        echo ""
        echo "Reportes generados en: $REPORT_DIR/"
        ;;
    unit)
        echo ""
        echo "========================================"
        echo " EJECUTANDO TESTS UNITARIOS PUROS"
        echo "========================================"
        echo ""
        $PHP $PHPUNIT --group unit --testdox --no-progress --no-logging --no-coverage --colors=always
        ;;
    rapido)
        echo ""
        echo "========================================"
        echo " EJECUCIÓN RÁPIDA (sin reportes)"
        echo "========================================"
        echo ""
        $PHP $PHPUNIT --no-progress --no-logging
        ;;
    filtro:*)
        FILTRO="${1#filtro:}"
        echo ""
        echo "Filtrando tests que contengan: $FILTRO"
        echo ""
        $PHP $PHPUNIT --filter "$FILTRO" --testdox --no-progress --colors=always
        ;;
    *)
        echo ""
        echo "========================================"
        echo " EJECUTANDO SUITE: $1"
        echo "========================================"
        echo ""
        $PHP $PHPUNIT --testsuite "$1" --testdox --no-progress --colors=always
        ;;
esac
