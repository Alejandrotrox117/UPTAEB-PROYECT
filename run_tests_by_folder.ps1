<#
PowerShell script: run_tests_by_folder.ps1
Descripción: ejecuta PHPUnit por cada subcarpeta dentro de tests/.
Uso:
  - Dry run (por defecto): muestra los comandos que se ejecutarían
    .\run_tests_by_folder.ps1

  - Ejecutar realmente las pruebas:
    .\run_tests_by_folder.ps1 -Run

  - Ejecutar sólo una carpeta:
    .\run_tests_by_folder.ps1 -Folder Clientes -Run

Salida: crea logs en tests\logs\<carpeta>.log
#>

param(
    [switch]$Run,
    [string]$Folder
)

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Definition
$testsRoot = Join-Path $projectRoot 'tests'
$phpunit = Join-Path $projectRoot 'vendor\bin\phpunit'
$logsDir = Join-Path $testsRoot 'logs'

if (-not (Test-Path $testsRoot)) {
    Write-Error "No existe la carpeta $testsRoot"
    exit 1
}

if (-not (Test-Path $phpunit)) {
    Write-Warning "No se encontró phpunit en: $phpunit. Asegúrate de haber instalado las dependencias (composer)."
}

if (-not (Test-Path $logsDir)) { New-Item -ItemType Directory -Path $logsDir | Out-Null }

# Obtener subdirectorios (solo 1 nivel) o filtrar por -Folder
# Excluir la carpeta 'logs' y sólo incluir carpetas que contengan archivos .php
$folders = @()
Get-ChildItem -Path $testsRoot -Directory | Where-Object { $_.Name -ne 'logs' } | ForEach-Object {
    $dir = $_.FullName
    $hasPhp = Get-ChildItem -Path $dir -Recurse -Filter *.php -File -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($hasPhp) { $folders += $_.Name }
}
if ($Folder) {
    if ($folders -contains $Folder) { $folders = @($Folder) } else { Write-Error "Carpeta '$Folder' no existe en tests/"; exit 1 }
}

Write-Host "Carpetas detectadas en tests/:" -ForegroundColor Cyan
$folders | ForEach-Object { Write-Host "  - $_" }
Write-Host "`nDry-run: mostrando comandos. Pasa -Run para ejecutar." -ForegroundColor Yellow

foreach ($f in $folders) {
    $testPath = Join-Path $testsRoot $f
    $logFile = Join-Path $logsDir ("$f.log")
    $cmd = "`"$phpunit`" `"$testPath`" --testdox"

    if ($Run) {
        Write-Host "Ejecutando tests en: $f" -ForegroundColor Green
        # Ejecutar y guardar salida
        if (Test-Path $phpunit) {
            # En Windows vendor\bin\phpunit no es ejecutable directamente; ejecutarlo vía php
            # Recolectar los archivos PHP dentro de la carpeta y pasarlos como argumentos
            $files = Get-ChildItem -Path $testPath -Filter *.php -File -ErrorAction SilentlyContinue | ForEach-Object { $_.FullName }
            if (-not $files) {
                Write-Host "No se encontraron archivos PHP en $testPath" -ForegroundColor Yellow
                continue
            }
            & php $phpunit $files --testdox 2>&1 | Tee-Object -FilePath $logFile
            $exit = $LASTEXITCODE
            if ($exit -ne 0) {
                Write-Host "PHPUnit devolvió código $exit para $f (ver $logFile)" -ForegroundColor Red
            } else {
                Write-Host "Completado $f (log: $logFile)" -ForegroundColor Green
            }
        } else {
            # Fallback: intentar ejecutar 'php vendor\bin\phpunit'
            $files = Get-ChildItem -Path $testPath -Filter *.php -File -ErrorAction SilentlyContinue | ForEach-Object { $_.FullName }
            if (-not $files) {
                Write-Host "No se encontraron archivos PHP en $testPath (fallback)" -ForegroundColor Yellow
                continue
            }
            & php (Join-Path $projectRoot 'vendor\bin\phpunit') $files --testdox 2>&1 | Tee-Object -FilePath $logFile
            $exit = $LASTEXITCODE
            Write-Host "Comando ejecutado (fallback), ver log: $logFile"
        }
    } else {
        Write-Host "[DRY] $cmd"
    }
}

Write-Host "`nHecho." -ForegroundColor Cyan
