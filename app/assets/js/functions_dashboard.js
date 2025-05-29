
document.addEventListener("DOMContentLoaded", function () {
    // Cargar datos iniciales
    fetch('dashboard/getDashboardData')
        .then(response => response.json())
        .then(data => {
            document.getElementById('ventasHoy').textContent = data.resumen.ventas_totales;
            document.getElementById('comprasHoy').textContent = data.resumen.compras_totales;
            document.getElementById('inventarioTotal').textContent = data.resumen.total_inventario;
            document.getElementById('empleadosActivos').textContent = data.resumen.empleados_activos;

            // Llenar tabla de ventas
            const ventasBody = document.getElementById('ventasBody');
            data.ventas.forEach(v => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-4 py-2">${v.nro_venta}</td>
                    <td class="px-4 py-2">${v.cliente}</td>
                    <td class="px-4 py-2">${v.fecha_venta}</td>
                    <td class="px-4 py-2">${v.total_general}</td>
                `;
                ventasBody.appendChild(tr);
            });

            // Llenar tabla de tareas
            const tareasBody = document.getElementById('tareasBody');
            data.tareas.forEach(t => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-4 py-2">${t.idtarea}</td>
                    <td class="px-4 py-2">${t.nombre_empleado}</td>
                    <td class="px-4 py-2">${t.cantidad_asignada}</td>
                    <td class="px-4 py-2">${t.estado}</td>
                `;
                tareasBody.appendChild(tr);
            });

           
            graficarVentasMensuales(data.ventasMensuales);
        });
});

function graficarVentasMensuales(datos) {
    const labels = datos.map(d => d.mes);
    const valores = datos.map(d => d.ventas_totales);

    new Chart(document.getElementById('graficoVentas'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Ventas Mensuales',
                data: valores,
                borderColor: '#4F46E5',
                backgroundColor: 'rgba(79, 70, 229, 0.2)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}
