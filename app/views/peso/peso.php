<?php
headerAdmin($data);
$ultimoPeso = $data['ultimo_peso'] ?? null;
$ultimoPesoStatus = $data['ultimo_peso_status'] ?? false;
$ultimoPesoMessage = $data['ultimo_peso_message'] ?? null;
$gaugeMax = 1000;
$gaugePeso = ($ultimoPesoStatus && $ultimoPeso && isset($ultimoPeso['peso'])) ? (float) $ultimoPeso['peso'] : null;
$gaugeProgress = ($gaugePeso !== null && $gaugeMax > 0)
    ? max(0, min(100, ($gaugePeso / $gaugeMax) * 100))
    : 0;

if ($gaugeProgress >= 70) {
    $gaugeMessage = 'Carga elevada. Verificar estado de la romana.';
} elseif ($gaugeProgress >= 35) {
    $gaugeMessage = 'Carga en rango operativo normal.';
} elseif ($gaugeProgress > 0) {
    $gaugeMessage = 'Carga ligera registrada. Listo para siguiente lectura.';
} else {
    $gaugeMessage = 'Aún no hay lecturas activas.';
}
?>
<main class="flex-1 bg-slate-100 min-h-screen p-6 md:p-8">
    <div class="max-w-7xl mx-auto space-y-6">
        <header>
            <h1 class="text-3xl font-bold text-slate-800">Monitor de Romana</h1>
            <p class="text-slate-500">Visualización en tiempo real del último peso registrado.</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm p-8">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="font-semibold text-slate-700">Lectura activa</p>
                        </div>
                        <button id="btnRefrescarPeso" type="button" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                            <i class="fas fa-sync-alt"></i>
                            Actualizar
                        </button>
                    </div>
                    <div class="text-center my-12">
                        <span id="pesoValorDisplay" class="text-9xl font-bold text-green-500">
                            <?= $ultimoPesoStatus && $ultimoPeso ? number_format($ultimoPeso['peso'], 1, ',', '.') : '0,0' ?>
                        </span>
                        <span class="text-8xl font-bold text-green-500/80">kg</span>
                        <p id="pesoGaugeMessage" class="mt-4 text-slate-500"><?= $gaugeMessage ?></p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-slate-500">Último peso registrado</p>
                                <p class="text-xl font-semibold text-slate-800">
                                    <span id="pesoDetalleValor">
                                        <?= $ultimoPesoStatus && $ultimoPeso ? number_format($ultimoPeso['peso'], 2, ',', '.') : '--.--' ?>
                                    </span> kg
                                </p>
                            </div>
                            <div class="flex flex-col">
                                <span id="pesoVariacionTexto" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600">
                                    <span id="pesoTrendIcon" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs">
                                        <i class="fas fa-minus text-slate-400"></i>
                                    </span>
                                    <span id="pesoVariacionLabel">Variación estable</span>
                                </span>
                                <span class="text-xs text-slate-400 ml-8">Comparado con lectura anterior</span>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-slate-500">Fecha reportada</p>
                                <p id="pesoFechaDisplay" class="font-semibold text-slate-800">
                                    <?= $ultimoPesoStatus && $ultimoPeso ? date('d/m/Y h:i:s A', strtotime($ultimoPeso['fecha'])) : 'No disponible' ?>
                                </p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-slate-500">Estado</p>
                                    <div id="pesoEstadoBadge" class="inline-flex items-center gap-2 font-semibold text-green-700">
                                        <span id="pesoEstadoIndicador" class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
                                        <span id="pesoEstadoTexto">Activo</span>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-500">ID registro</p>
                                    <p id="pesoRegistroId" class="font-semibold text-slate-800"><?= $ultimoPeso['idromana'] ?? '—' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                     <div class="pt-4 mt-4 border-t border-slate-200">
                        <p class="text-sm text-slate-500">Última sincronización</p>
                        <p id="pesoUltimaSincronizacion" class="font-semibold text-slate-800">—</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
                <h2 class="text-lg font-bold text-slate-800">Panel Operativo</h2>
                <div class="space-y-5">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg bg-blue-100 text-blue-500">
                            <i class="fas fa-stopwatch"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-700">Frecuencia 10s</p>
                            <p class="text-sm text-slate-500">Actualización automática constante.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg bg-blue-100 text-blue-500">
                            <i class="fas fa-database"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-700">Fuente Directa</p>
                            <p class="text-sm text-slate-500">Lectura desde historial de romana.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg bg-blue-100 text-blue-500">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-700">Alertas Visuales</p>
                            <p class="text-sm text-slate-500">Color dinámico según estado del sensor.</p>
                        </div>
                    </div>
                    <div class="pt-4 mt-2 border-t border-slate-200">
                         <div class="flex items-center gap-4">
                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                <i class="fas fa-hand-pointer"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-700">Última actualización manual</p>
                                <p id="pesoUltimaActualizacion" class="text-sm text-slate-500">No se ha realizado.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<div id="ultimoPesoDataset"
     data-status="<?= $ultimoPesoStatus ? '1' : '0' ?>"
     data-peso="<?= $ultimoPesoStatus && $ultimoPeso ? $ultimoPeso['peso'] : '' ?>"
     data-fecha="<?= $ultimoPesoStatus && $ultimoPeso ? $ultimoPeso['fecha'] : '' ?>"
     data-fecha-creacion="<?= $ultimoPesoStatus && $ultimoPeso ? $ultimoPeso['fecha_creacion'] : '' ?>"
     data-estatus="<?= $ultimoPeso['estatus'] ?? '' ?>"
     data-id="<?= $ultimoPeso['idromana'] ?? '' ?>"
    data-gauge-max="<?= $gaugeMax ?>"
     style="display:none"></div>
<?php footerAdmin($data); ?>
