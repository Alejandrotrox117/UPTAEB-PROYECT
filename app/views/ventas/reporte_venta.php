<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Venta #<?php echo $arrVenta['venta']['nro_venta'] ?? 'N/A'; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .document-title {
            font-size: 18px;
            color: #34495e;
            margin-bottom: 10px;
        }
        
        .venta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .info-section {
            flex: 1;
            min-width: 250px;
            margin-right: 20px;
        }
        
        .info-title {
            font-weight: bold;
            font-size: 14px;
            color: #2c3e50;
            margin-bottom: 10px;
            border-bottom: 1px solid #bdc3c7;
            padding-bottom: 5px;
        }
        
        .info-item {
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        
        .productos-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .productos-table th,
        .productos-table td {
            border: 1px solid #bdc3c7;
            padding: 10px;
            text-align: left;
        }
        
        .productos-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .productos-table td.numero {
            text-align: right;
        }
        
        .totales {
            margin-top: 20px;
            text-align: right;
        }
        
        .total-row {
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .total-final {
            font-size: 16px;
            font-weight: bold;
            color: #27ae60;
            border-top: 2px solid #27ae60;
            padding-top: 10px;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #7f8c8d;
            border-top: 1px solid #bdc3c7;
            padding-top: 15px;
        }
        
        .estado-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .estado-borrador { background-color: #f39c12; color: white; }
        .estado-por_pagar { background-color: #e74c3c; color: white; }
        .estado-pagada { background-color: #27ae60; color: white; }
        .estado-anulada { background-color: #95a5a6; color: white; }
        
        @media print {
            body { padding: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <?php if (isset($arrVenta['status']) && $arrVenta['status']): ?>
        <!-- Encabezado del Reporte -->
        <div class="header">
            <div class="company-name">Sistema de Gestión de Ventas</div>
            <div class="document-title">REPORTE DE VENTA</div>
            <div style="font-size: 14px; color: #7f8c8d;">
                Generado el <?php echo date('d/m/Y H:i'); ?>
            </div>
        </div>

        <!-- Información de la Venta -->
        <div class="venta-info">
            <div class="info-section">
                <div class="info-title">Información de la Venta</div>
                <div class="info-item">
                    <span class="info-label">Número de Venta:</span>
                    <?php echo htmlspecialchars($arrVenta['venta']['nro_venta'] ?? 'N/A'); ?>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha:</span>
                    <?php echo date('d/m/Y', strtotime($arrVenta['venta']['fecha_venta'] ?? 'now')); ?>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado:</span>
                    <?php 
                        $estado = strtolower($arrVenta['venta']['estatus'] ?? 'borrador');
                        $estadoTexto = [
                            'borrador' => 'Borrador',
                            'por_pagar' => 'Por Pagar',
                            'pagada' => 'Pagada',
                            'anulada' => 'Anulada'
                        ];
                    ?>
                    <span class="estado-badge estado-<?php echo $estado; ?>">
                        <?php echo $estadoTexto[$estado] ?? 'N/A'; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Moneda:</span>
                    <?php echo htmlspecialchars($arrVenta['venta']['codigo_moneda'] ?? 'VES'); ?> - 
                    <?php echo htmlspecialchars($arrVenta['venta']['nombre_moneda'] ?? 'Bolívares'); ?>
                </div>
            </div>
            
            <div class="info-section">
                <div class="info-title">Información del Cliente</div>
                <div class="info-item">
                    <span class="info-label">Cliente:</span>
                    <?php echo htmlspecialchars($arrVenta['venta']['cliente_nombre'] ?? 'N/A'); ?>
                </div>
            </div>
        </div>

        <!-- Productos de la Venta -->
        <div class="info-title">Productos</div>
        <?php if (!empty($arrVenta['detalles'])): ?>
            <table class="productos-table">
                <thead>
                    <tr>
                        <th style="width: 5%">#</th>
                        <th style="width: 35%">Producto</th>
                        <th style="width: 20%">Categoría</th>
                        <th style="width: 10%">Cantidad</th>
                        <th style="width: 15%">Precio Unit.</th>
                        <th style="width: 15%">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $contador = 1;
                    $totalGeneral = 0;
                    foreach ($arrVenta['detalles'] as $detalle): 
                        $subtotal = $detalle['cantidad'] * $detalle['precio_unitario_venta'];
                        $totalGeneral += $subtotal;
                    ?>
                        <tr>
                            <td class="numero"><?php echo $contador++; ?></td>
                            <td><?php echo htmlspecialchars($detalle['nombre_producto'] ?? 'Producto sin nombre'); ?></td>
                            <td><?php echo htmlspecialchars($detalle['nombre_categoria'] ?? 'Sin categoría'); ?></td>
                            <td class="numero"><?php echo number_format($detalle['cantidad'], 0); ?></td>
                            <td class="numero"><?php echo number_format($detalle['precio_unitario_venta'], 2); ?></td>
                            <td class="numero"><?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #7f8c8d; font-style: italic; padding: 20px;">
                No hay productos registrados en esta venta.
            </p>
        <?php endif; ?>

        <!-- Totales -->
        <div class="totales">
            <div class="total-final">
                <strong>Total General: <?php echo number_format($arrVenta['venta']['total_general'] ?? 0, 2); ?> 
                <?php echo htmlspecialchars($arrVenta['venta']['codigo_moneda'] ?? 'VES'); ?></strong>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Este es un documento generado automáticamente por el Sistema de Gestión de Ventas</p>
            <p>Fecha y hora de generación: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>

        <!-- Botones de acción (no se imprimen) -->
        <div class="no-print" style="margin-top: 30px; text-align: center;">
            <button onclick="window.print()" style="background-color: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">
                🖨️ Imprimir
            </button>
            <button onclick="window.close()" style="background-color: #95a5a6; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
                ❌ Cerrar
            </button>
        </div>

    <?php else: ?>
        <div class="header">
            <div class="company-name">Sistema de Gestión de Ventas</div>
            <div class="document-title">ERROR</div>
        </div>
        
        <div style="text-align: center; color: #e74c3c; font-size: 16px; margin-top: 50px;">
            <p>❌ No se pudo cargar la información de la venta.</p>
            <p style="font-size: 14px; margin-top: 10px;">
                <?php echo htmlspecialchars($arrVenta['message'] ?? 'Error desconocido'); ?>
            </p>
        </div>
        
        <div class="no-print" style="margin-top: 30px; text-align: center;">
            <button onclick="window.close()" style="background-color: #95a5a6; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
                ❌ Cerrar
            </button>
        </div>
    <?php endif; ?>
</body>
</html>
