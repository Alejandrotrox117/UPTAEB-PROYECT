// Verificar que el script solo se ejecute una vez
if (window.backupScriptLoaded) {
    console.warn('Script de backup ya está cargado');
} else {
    window.backupScriptLoaded = true;

// Esperar a que jQuery y DataTables estén completamente cargados
function esperarLibrerias(callback) {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        callback();
    } else {
        setTimeout(() => esperarLibrerias(callback), 100);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    esperarLibrerias(function() {
        let tablaBackups;
        let modalBackupTabla;
        let modalConfirmarRestaurar;
        let archivoParaRestaurar = '';
        let tablaInicializada = false; // Flag para controlar la inicialización

        // Inicialización
        init();

        function init() {
            configurarModales();
            configurarEventos();
            cargarListaBackups();
            cargarTablas();
            cargarEstadisticas();
        }

    function configurarModales() {
        modalBackupTabla = document.getElementById('modalBackupTabla');
        modalConfirmarRestaurar = document.getElementById('modalConfirmarRestaurar');
    }

    function configurarEventos() {
        // Botones principales
        document.getElementById('btnBackupCompleto').addEventListener('click', crearBackupCompleto);
        document.getElementById('btnBackupTabla').addEventListener('click', mostrarModalBackupTabla);
        document.getElementById('btnActualizarLista').addEventListener('click', cargarListaBackups);

        // Modal Backup por Tabla
        document.getElementById('btnCerrarModalTabla').addEventListener('click', ocultarModalBackupTabla);
        document.getElementById('btnCancelarBackupTabla').addEventListener('click', ocultarModalBackupTabla);
        document.getElementById('btnConfirmarBackupTabla').addEventListener('click', crearBackupTabla);

        // Modal Confirmar Restaurar
        document.getElementById('btnCancelarRestaurar').addEventListener('click', ocultarModalRestaurar);
        document.getElementById('btnConfirmarRestaurar').addEventListener('click', ejecutarRestauracion);

        // Cerrar modales al hacer click fuera
        window.addEventListener('click', function(event) {
            if (event.target === modalBackupTabla) {
                ocultarModalBackupTabla();
            }
            if (event.target === modalConfirmarRestaurar) {
                ocultarModalRestaurar();
            }
        });
    }

    // Funciones para mostrar/ocultar modales
    function mostrarModalBackupTabla() {
        modalBackupTabla.classList.remove('opacity-0', 'pointer-events-none');
        modalBackupTabla.querySelector('.transform').classList.remove('scale-95');
        modalBackupTabla.querySelector('.transform').classList.add('scale-100');
    }

    function ocultarModalBackupTabla() {
        modalBackupTabla.classList.add('opacity-0', 'pointer-events-none');
        modalBackupTabla.querySelector('.transform').classList.remove('scale-100');
        modalBackupTabla.querySelector('.transform').classList.add('scale-95');
        document.getElementById('formBackupTabla').reset();
    }

    function mostrarModalRestaurar(archivo) {
        archivoParaRestaurar = archivo;
        document.getElementById('archivoRestaurar').textContent = archivo;
        modalConfirmarRestaurar.classList.remove('opacity-0', 'pointer-events-none');
        modalConfirmarRestaurar.querySelector('.transform').classList.remove('scale-95');
        modalConfirmarRestaurar.querySelector('.transform').classList.add('scale-100');
    }

    function ocultarModalRestaurar() {
        modalConfirmarRestaurar.classList.add('opacity-0', 'pointer-events-none');
        modalConfirmarRestaurar.querySelector('.transform').classList.remove('scale-100');
        modalConfirmarRestaurar.querySelector('.transform').classList.add('scale-95');
        archivoParaRestaurar = '';
    }

    // Funciones de backup
    async function crearBackupCompleto() {
        const btn = document.getElementById('btnBackupCompleto');
        const btnText = btn.innerHTML;
        
        try {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creando backup...';
            
            const response = await fetch(base_url + 'backup/crearBackupCompleto', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({})
            });

            const data = await response.json();

            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Backup Creado!',
                    text: data.mensaje || 'El backup completo se ha creado exitosamente',
                    confirmButtonColor: '#10B981'
                });
                cargarListaBackups();
                cargarEstadisticas();
            } else {
                throw new Error(data.mensaje || 'Error al crear el backup');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Error al crear el backup completo',
                confirmButtonColor: '#EF4444'
            });
        } finally {
            btn.disabled = false;
            btn.innerHTML = btnText;
        }
    }

    async function crearBackupTabla() {
        const tabla = document.getElementById('selectTabla').value;
        
        if (!tabla) {
            Swal.fire({
                icon: 'warning',
                title: 'Seleccione una tabla',
                text: 'Debe seleccionar una tabla para crear el backup',
                confirmButtonColor: '#F59E0B'
            });
            return;
        }

        const btn = document.getElementById('btnConfirmarBackupTabla');
        const btnText = btn.innerHTML;
        
        try {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creando...';
            
            const response = await fetch(base_url + 'backup/crearBackupTabla', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ tabla: tabla })
            });

            const data = await response.json();

            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Backup Creado!',
                    text: `Backup de la tabla "${tabla}" creado exitosamente`,
                    confirmButtonColor: '#10B981'
                });
                ocultarModalBackupTabla();
                cargarListaBackups();
                cargarEstadisticas();
            } else {
                throw new Error(data.mensaje || 'Error al crear el backup');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Error al crear el backup de la tabla',
                confirmButtonColor: '#EF4444'
            });
        } finally {
            btn.disabled = false;
            btn.innerHTML = btnText;
        }
    }

    async function ejecutarRestauracion() {
        if (!archivoParaRestaurar) return;

        const btn = document.getElementById('btnConfirmarRestaurar');
        const btnText = btn.innerHTML;
        
        try {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Restaurando...';
            
            const response = await fetch(base_url + 'backup/restaurarBackup', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ archivo: archivoParaRestaurar })
            });

            const data = await response.json();

            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Restauración Completada!',
                    text: data.mensaje || 'La base de datos ha sido restaurada exitosamente',
                    confirmButtonColor: '#10B981'
                });
                ocultarModalRestaurar();
                cargarListaBackups();
            } else {
                throw new Error(data.mensaje || 'Error al restaurar el backup');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error en la Restauración',
                text: error.message || 'Error al restaurar el backup',
                confirmButtonColor: '#EF4444'
            });
        } finally {
            btn.disabled = false;
            btn.innerHTML = btnText;
        }
    }

    async function eliminarBackup(archivo) {
        const result = await Swal.fire({
            title: '¿Eliminar backup?',
            text: `¿Está seguro de eliminar el archivo "${archivo}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(base_url + 'backup/eliminarBackup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ archivo: archivo })
                });

                const data = await response.json();

                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado!',
                        text: 'El backup ha sido eliminado exitosamente',
                        confirmButtonColor: '#10B981'
                    });
                    cargarListaBackups();
                    cargarEstadisticas();
                } else {
                    throw new Error(data.mensaje || 'Error al eliminar el backup');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Error al eliminar el backup',
                    confirmButtonColor: '#EF4444'
                });
            }
        }
    }

    // Funciones de carga de datos
    async function cargarListaBackups() {
        const loader = document.getElementById('loaderTableBackups');
        
        try {
            loader.classList.remove('hidden');
            
            const response = await fetch(base_url + 'backup/listarBackups');
            const data = await response.json();

            if (data.status === 'success') {
                // Si la tabla ya existe, solo actualizar los datos
                if (tablaInicializada && tablaBackups && $.fn.DataTable.isDataTable('#tablaBackups')) {
                    try {
                        tablaBackups.clear();
                        if (data.backups && data.backups.length > 0) {
                            tablaBackups.rows.add(data.backups);
                        }
                        tablaBackups.draw();
                    } catch (updateError) {
                        console.error('Error al actualizar tabla:', updateError);
                        // Si falla la actualización, recargar la página
                        location.reload();
                    }
                } else {
                    // Primera inicialización
                    configurarDataTable(data.backups || []);
                }
            } else {
                throw new Error(data.mensaje || 'Error al cargar los backups');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al cargar la lista de backups',
                confirmButtonColor: '#EF4444'
            });
        } finally {
            loader.classList.add('hidden');
        }
    }

    function configurarDataTable(backups) {
        // Solo inicializar si no está ya inicializado
        if (tablaInicializada || $.fn.DataTable.isDataTable('#tablaBackups')) {
            console.warn('DataTable ya está inicializada');
            return;
        }
        
        try {
            tablaBackups = $('#tablaBackups').DataTable({
                data: backups,
                responsive: true,
                pageLength: 10,
                order: [[3, 'desc']], // Ordenar por fecha descendente
                language: {
                    "decimal": "",
                    "emptyTable": "No hay datos disponibles en la tabla",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
                    "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
                    "infoFiltered": "(filtrado de _MAX_ entradas totales)",
                    "infoPostFix": "",
                    "thousands": ",",
                    "lengthMenu": "Mostrar _MENU_ entradas",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No se encontraron registros coincidentes",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    },
                    "aria": {
                        "sortAscending": ": activar para ordenar la columna ascendente",
                        "sortDescending": ": activar para ordenar la columna descendente"
                    }
                },
                columns: [
                {
                    data: 'nombre_archivo',
                    render: function(data, type, row) {
                        return `<span class="font-mono text-sm">${data}</span>`;
                    }
                },
                {
                    data: 'tipo_backup',
                    render: function(data, type, row) {
                        const color = data === 'COMPLETO' ? 'bg-blue-100 text-blue-800' : 'bg-indigo-100 text-indigo-800';
                        const texto = data === 'COMPLETO' ? 'Completo' : 'Tabla';
                        return `<span class="px-2 py-1 text-xs font-medium rounded-full ${color}">${texto}</span>`;
                    }
                },
                {
                    data: 'tamaño_archivo',
                    render: function(data, type, row) {
                        return `<span class="text-sm">${formatearTamaño(data)}</span>`;
                    }
                },
                {
                    data: 'fecha_formato',
                    render: function(data, type, row) {
                        return `<span class="text-sm">${data}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                            <div class="flex space-x-2">
                                <button onclick="descargarBackup('${row.nombre_archivo}')" 
                                        class="px-3 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600 transition-colors duration-200"
                                        title="Descargar">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button onclick="mostrarModalRestaurar('${row.nombre_archivo}')" 
                                        class="px-3 py-1 bg-yellow-500 text-white text-xs rounded hover:bg-yellow-600 transition-colors duration-200"
                                        title="Restaurar">
                                    <i class="fas fa-undo"></i>
                                </button>
                                <button onclick="eliminarBackup('${row.nombre_archivo}')" 
                                        class="px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600 transition-colors duration-200"
                                        title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ]
        });
        
        // Marcar como inicializada
        tablaInicializada = true;
        
        } catch (error) {
            console.error('Error al configurar DataTable:', error);
            tablaInicializada = false;
        }
    }

    async function cargarTablas() {
        try {
            const response = await fetch(base_url + 'backup/obtenerTablas');
            const data = await response.json();

            if (data.status === 'success') {
                const select = document.getElementById('selectTabla');
                select.innerHTML = '<option value="">Seleccione una tabla...</option>';
                
                data.tablas.forEach(tabla => {
                    const option = document.createElement('option');
                    option.value = tabla.name;
                    option.textContent = tabla.name;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error al cargar tablas:', error);
        }
    }

    async function cargarEstadisticas() {
        try {
            const response = await fetch(base_url + 'backup/obtenerEstadisticas');
            const data = await response.json();

            if (data.status === 'success') {
                const stats = data.estadisticas;
                document.getElementById('totalBackups').textContent = stats.total || '0';
                document.getElementById('ultimoBackup').textContent = stats.ultimo || 'N/A';
                document.getElementById('espacioTotal').textContent = stats.espacio || 'N/A';
            }
        } catch (error) {
            console.error('Error al cargar estadísticas:', error);
        }
    }

    // Funciones globales
    window.descargarBackup = function(archivo) {
        window.open(base_url + 'backup/descargarBackup?archivo=' + encodeURIComponent(archivo), '_blank');
    };

    window.mostrarModalRestaurar = mostrarModalRestaurar;
    window.eliminarBackup = eliminarBackup;

    // Función para formatear tamaños de archivo
    function formatearTamaño(bytes) {
        if (bytes === 0 || !bytes) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    }); // Cierre de esperarLibrerias
}); // Cierre de DOMContentLoaded

} // Cierre del bloque principal del script