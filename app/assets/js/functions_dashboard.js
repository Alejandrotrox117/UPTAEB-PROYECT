document.addEventListener("DOMContentLoaded", function () {
    initializeDashboard();
    setupEventListeners();
});

let chartVentasInstance = null;
let chartComprasInstance = null;

function setupEventListeners() {
    const btnActualizar = document.getElementById('btnActualizar');
    if (btnActualizar) {
        btnActualizar.addEventListener('click', function() {
            actualizarDatos();
        });
    }

    // Auto actualizar cada 5 minutos
    setInterval(actualizarStats, 300000);
}

async function initializeDashboard() {
    showLoading(true);
    try {
        await cargarDatosDashboard();
        showNotification('Dashboard cargado correctamente', 'success');
    } catch (error) {
        console.error('Error inicializando dashboard:', error);
        showNotification('Error al cargar el dashboard', 'error');
    } finally {
        showLoading(false);
    }
}

async function cargarDatosDashboard() {
    try {
        console.log('Cargando datos del dashboard...');
        const response = await fetch('/project/dashboard/getDashboardData');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Respuesta del servidor:', result);
        
        if (!result.status) {
            throw new Error(result.message || 'Error al obtener datos');
        }

        const data = result.data;
        
        // Actualizar estadísticas principales
        actualizarEstadisticas(data.resumen);
        
        // Actualizar alertas
        mostrarAlertas(data.alertas || []);
        
        // Actualizar tablas
        actualizarTablaCompras(data.compras || []);
        actualizarTablaVentas(data.ventas || []);
        actualizarTablaTareas(data.tareas || []);
        actualizarTablaStockBajo(data.stockBajo || []);
        
        // Actualizar gráficos
        actualizarGraficoVentas(data.ventasMensuales || []);
        actualizarGraficoCompras(data.comprasMensuales || []);
        
    } catch (error) {
        console.error('Error cargando datos:', error);
        mostrarErrorCarga();
        throw error;
    }
}

function actualizarEstadisticas(stats) {
    console.log('Actualizando estadísticas:', stats);
    
    const elementos = {
        'comprasHoy': stats.compras_hoy || 0,
        'ventasHoy': stats.ventas_hoy || 0,
        'inventarioTotal': stats.inventario_total || 0,
        'empleadosActivos': stats.empleados_activos || 0,
        'tareasActivas': stats.tareas_activas || 0
    };

    Object.entries(elementos).forEach(([id, valor]) => {
        const elemento = document.getElementById(id);
        if (elemento) {
            animateNumber(elemento, parseInt(valor));
        } else {
            console.warn(`Elemento ${id} no encontrado`);
        }
    });
}

function animateNumber(elemento, targetValue) {
    const startValue = 0;
    const duration = 1000;
    const increment = targetValue / (duration / 16);
    let currentValue = startValue;

    const timer = setInterval(() => {
        currentValue += increment;
        if (currentValue >= targetValue) {
            elemento.textContent = targetValue.toLocaleString();
            clearInterval(timer);
        } else {
            elemento.textContent = Math.floor(currentValue).toLocaleString();
        }
    }, 16);
}

function mostrarAlertas(alertas) {
    const container = document.getElementById('alertasContainer');
    if (!container) return;

    if (!alertas || alertas.length === 0) {
        container.innerHTML = '';
        return;
    }

    const alertasHtml = alertas.map(alerta => {
        const colorClass = getAlertColorClass(alerta.tipo);
        return `
            <div class="alerta ${colorClass} p-4 rounded-lg border-l-4 flex items-center mb-4">
                <i class="${alerta.icono} mr-3 text-lg"></i>
                <div class="flex-1">
                    <h4 class="font-semibold">${alerta.titulo}</h4>
                    <p class="text-sm opacity-90">${alerta.mensaje}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="ml-4 opacity-70 hover:opacity-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }).join('');

    container.innerHTML = alertasHtml;
}

function getAlertColorClass(tipo) {
    const colors = {
        'danger': 'bg-red-50 border-red-400 text-red-800',
        'warning': 'bg-yellow-50 border-yellow-400 text-yellow-800',
        'info': 'bg-blue-50 border-blue-400 text-blue-800',
        'success': 'bg-green-50 border-green-400 text-green-800'
    };
    return colors[tipo] || colors.info;
}

function actualizarTablaCompras(compras) {
    const tbody = document.getElementById('comprasBody');
    if (!tbody) return;

    if (!compras || compras.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-8 text-gray-500">
                    <i class="fas fa-inbox mr-2"></i>No hay compras recientes
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = compras.map(compra => `
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="py-3 font-medium">#${compra.nro_compra || 'N/A'}</td>
            <td class="py-3 text-gray-600">${compra.proveedor || 'Sin proveedor'}</td>
            <td class="py-3 text-gray-600">${compra.fecha || 'N/A'}</td>
            <td class="py-3 font-semibold text-purple-600">${compra.total || 'N/A'}</td>
            <td class="py-3">
                <span class="px-2 py-1 text-xs font-medium ${getStatusBadgeClass(compra.estatus_compra)} rounded-full">
                    ${compra.estatus_compra || 'N/A'}
                </span>
            </td>
        </tr>
    `).join('');
}

function actualizarTablaVentas(ventas) {
    const tbody = document.getElementById('ventasBody');
    if (!tbody) return;

    if (!ventas || ventas.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-8 text-gray-500">
                    <i class="fas fa-inbox mr-2"></i>No hay ventas recientes
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = ventas.map(venta => `
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="py-3 font-medium">#${venta.nro_venta || 'N/A'}</td>
            <td class="py-3 text-gray-600">${venta.cliente || 'Sin cliente'}</td>
            <td class="py-3 text-gray-600">${venta.fecha || 'N/A'}</td>
            <td class="py-3 font-semibold text-green-600">${venta.total || 'N/A'}</td>
        </tr>
    `).join('');
}

function actualizarTablaTareas(tareas) {
    const tbody = document.getElementById('tareasBody');
    if (!tbody) return;

    if (!tareas || tareas.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-8 text-gray-500">
                    <i class="fas fa-check-circle mr-2"></i>No hay tareas pendientes
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = tareas.map(tarea => `
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="py-3 font-medium">#${tarea.idtarea || 'N/A'}</td>
            <td class="py-3 text-gray-600">${tarea.empleado || 'Sin asignar'}</td>
            <td class="py-3 text-gray-600">${tarea.producto || 'N/A'}</td>
            <td class="py-3 text-gray-600">
                ${tarea.cantidad_realizada || 0}/${tarea.cantidad_asignada || 0}
            </td>
            <td class="py-3">
                <span class="px-2 py-1 text-xs font-medium ${getTaskStatusClass(tarea.estado)} rounded-full">
                    ${tarea.estado || 'N/A'}
                </span>
            </td>
        </tr>
    `).join('');
}

function actualizarTablaStockBajo(productos) {
    const tbody = document.getElementById('stockBajoBody');
    if (!tbody) return;

    if (!productos || productos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="3" class="text-center py-8 text-gray-500">
                    <i class="fas fa-check-circle mr-2"></i>Todos los productos tienen stock suficiente
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = productos.map(producto => `
        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="py-3 font-medium">${producto.nombre || 'N/A'}</td>
            <td class="py-3 text-gray-600">${producto.existencia || 0}</td>
            <td class="py-3">
                <span class="px-2 py-1 text-xs font-medium ${getStockStatusClass(producto.estado_stock)} rounded-full">
                    ${producto.estado_stock || 'N/A'}
                </span>
            </td>
        </tr>
    `).join('');
}

function actualizarGraficoVentas(ventasMensuales) {
    const canvas = document.getElementById('graficoVentas');
    if (!canvas) return;

    // Destruir gráfico anterior si existe
    if (chartVentasInstance) {
        chartVentasInstance.destroy();
    }

    if (!ventasMensuales || ventasMensuales.length === 0) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#9CA3AF';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('No hay datos de ventas disponibles', canvas.width / 2, canvas.height / 2);
        return;
    }

    const ctx = canvas.getContext('2d');
    const labels = ventasMensuales.map(item => item.mes_texto || item.mes);
    const valores = ventasMensuales.map(item => parseInt(item.cantidad_ventas || 0));

    chartVentasInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Ventas Mensuales',
                data: valores,
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#10B981',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#10B981',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        color: '#6B7280'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#6B7280'
                    }
                }
            }
        }
    });
}

function actualizarGraficoCompras(comprasMensuales) {
    const canvas = document.getElementById('graficoCompras');
    if (!canvas) return;

    // Destruir gráfico anterior si existe
    if (chartComprasInstance) {
        chartComprasInstance.destroy();
    }

    if (!comprasMensuales || comprasMensuales.length === 0) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#9CA3AF';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('No hay datos de compras disponibles', canvas.width / 2, canvas.height / 2);
        return;
    }

    const ctx = canvas.getContext('2d');
    const labels = comprasMensuales.map(item => item.mes_texto || item.mes);
    const valores = comprasMensuales.map(item => parseInt(item.cantidad_compras || 0));

    chartComprasInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Compras Mensuales',
                data: valores,
                backgroundColor: 'rgba(139, 92, 246, 0.8)',
                borderColor: '#8B5CF6',
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#8B5CF6',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        color: '#6B7280'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#6B7280'
                    }
                }
            }
        }
    });
}

// Funciones auxiliares para clases CSS
function getStatusBadgeClass(status) {
    const statusClasses = {
        'PAGADA': 'bg-green-100 text-green-800',
        'AUTORIZADA': 'bg-blue-100 text-blue-800',
        'PENDIENTE': 'bg-yellow-100 text-yellow-800',
        'CANCELADA': 'bg-red-100 text-red-800'
    };
    return statusClasses[status] || 'bg-gray-100 text-gray-800';
}

function getTaskStatusClass(status) {
    const statusClasses = {
        'pendiente': 'bg-yellow-100 text-yellow-800',
        'en_progreso': 'bg-blue-100 text-blue-800',
        'completada': 'bg-green-100 text-green-800',
        'cancelada': 'bg-red-100 text-red-800'
    };
    return statusClasses[status] || 'bg-gray-100 text-gray-800';
}

function getStockStatusClass(status) {
    const statusClasses = {
        'Sin stock': 'bg-red-100 text-red-800',
        'Stock crítico': 'bg-orange-100 text-orange-800',
        'Stock bajo': 'bg-yellow-100 text-yellow-800',
        'Stock normal': 'bg-green-100 text-green-800'
    };
    return statusClasses[status] || 'bg-gray-100 text-gray-800';
}

async function actualizarStats() {
    try {
        const response = await fetch('/project/dashboard/getStatsRealTime');
        const result = await response.json();
        
        if (result.status) {
            actualizarEstadisticas(result.data);
        }
    } catch (error) {
        console.error('Error actualizando estadísticas:', error);
    }
}

async function actualizarDatos() {
    const btn = document.getElementById('btnActualizar');
    const originalContent = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Actualizando...';
    btn.disabled = true;
    
    try {
        await cargarDatosDashboard();
        showNotification('Datos actualizados correctamente', 'success');
    } catch (error) {
        showNotification('Error al actualizar los datos', 'error');
    } finally {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}

function showLoading(show) {
    const indicator = document.getElementById('loadingIndicator');
    if (indicator) {
        indicator.classList.toggle('hidden', !show);
    }
}

function mostrarErrorCarga() {
    const elementos = [
        'comprasBody', 'ventasBody', 'tareasBody', 'stockBajoBody'
    ];
    
    elementos.forEach(id => {
        const elemento = document.getElementById(id);
        if (elemento) {
            const colspan = id === 'comprasBody' || id === 'tareasBody' ? '5' : 
                           id === 'ventasBody' ? '4' : '3';
            elemento.innerHTML = `
                <tr>
                    <td colspan="${colspan}" class="text-center py-8 text-red-500">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Error al cargar los datos
                    </td>
                </tr>
            `;
        }
    });
}

function showNotification(message, type = 'info') {
    // Implementar sistema de notificaciones básico
    console.log(`[${type.toUpperCase()}] ${message}`);
    
    // Crear notificación visual simple
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info'} mr-2"></i>
            ${message}
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
