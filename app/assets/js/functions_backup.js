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
        let modalImportarDB;
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
        modalImportarDB = document.getElementById('modalImportarDB');
    }

    function configurarEventos() {
        // Botones principales
        const btnBackupCompleto = document.getElementById('btnBackupCompleto');
        if (btnBackupCompleto) {
            btnBackupCompleto.addEventListener('click', crearBackupCompleto);
        }
        
        const btnBackupTabla = document.getElementById('btnBackupTabla');
        if (btnBackupTabla) {
            btnBackupTabla.addEventListener('click', mostrarModalBackupTabla);
        }
        
        const btnImportarDB = document.getElementById('btnImportarDB');
        if (btnImportarDB) {
            btnImportarDB.addEventListener('click', mostrarModalImportar);
        }
        
        const btnActualizarLista = document.getElementById('btnActualizarLista');
        if (btnActualizarLista) {
            btnActualizarLista.addEventListener('click', cargarListaBackups);
        }

        // Modal Backup por Tabla
        const btnCerrarModalTabla = document.getElementById('btnCerrarModalTabla');
        if (btnCerrarModalTabla) {
            btnCerrarModalTabla.addEventListener('click', ocultarModalBackupTabla);
        }
        
        const btnCancelarBackupTabla = document.getElementById('btnCancelarBackupTabla');
        if (btnCancelarBackupTabla) {
            btnCancelarBackupTabla.addEventListener('click', ocultarModalBackupTabla);
        }
        
        const btnConfirmarBackupTabla = document.getElementById('btnConfirmarBackupTabla');
        if (btnConfirmarBackupTabla) {
            btnConfirmarBackupTabla.addEventListener('click', crearBackupTabla);
        }

        // Modal Importar DB
        const btnCerrarModalImportar = document.getElementById('btnCerrarModalImportar');
        if (btnCerrarModalImportar) {
            btnCerrarModalImportar.addEventListener('click', ocultarModalImportar);
        }
        
        const btnCancelarImportar = document.getElementById('btnCancelarImportar');
        if (btnCancelarImportar) {
            btnCancelarImportar.addEventListener('click', ocultarModalImportar);
        }
        
        const formImportarDB = document.getElementById('formImportarDB');
        if (formImportarDB) {
            formImportarDB.addEventListener('submit', ejecutarImportacion);
        }

        // Event delegation para botones de la tabla
        document.addEventListener('click', function(e) {
            const button = e.target.closest('button[data-action]');
            if (!button) return;
            
            const action = button.getAttribute('data-action');
            const archivo = button.getAttribute('data-archivo');
            
            if (action === 'descargar') {
                descargarBackup(archivo);
            } else if (action === 'eliminar') {
                eliminarBackup(archivo);
            }
        });

        // Cerrar modales al hacer click fuera
        window.addEventListener('click', function(event) {
            if (event.target === modalBackupTabla) {
                ocultarModalBackupTabla();
            }
            if (event.target === modalImportarDB) {
                ocultarModalImportar();
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
        if (modalBackupTabla) {
            modalBackupTabla.classList.add('opacity-0', 'pointer-events-none');
            const transform = modalBackupTabla.querySelector('.transform');
            if (transform) {
                transform.classList.remove('scale-100');
                transform.classList.add('scale-95');
            }
        }
        const formBackupTabla = document.getElementById('formBackupTabla');
        if (formBackupTabla) {
            formBackupTabla.reset();
        }
    }

    // Funciones para mostrar/ocultar modal importar
    function mostrarModalImportar() {
        if (modalImportarDB) {
            modalImportarDB.classList.remove('opacity-0', 'pointer-events-none');
            const transform = modalImportarDB.querySelector('.transform');
            if (transform) {
                transform.classList.remove('scale-95');
                transform.classList.add('scale-100');
            }
        }
    }

    function ocultarModalImportar() {
        if (modalImportarDB) {
            modalImportarDB.classList.add('opacity-0', 'pointer-events-none');
            const transform = modalImportarDB.querySelector('.transform');
            if (transform) {
                transform.classList.remove('scale-100');
                transform.classList.add('scale-95');
            }
        }
        const formImportarDB = document.getElementById('formImportarDB');
        if (formImportarDB) {
            formImportarDB.reset();
        }
    }

    // Funciones de backup
    async function crearBackupCompleto() {
        const btn = document.getElementById('btnBackupCompleto');
        if (!btn) return;
        
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
        const selectTabla = document.getElementById('selectTabla');
        if (!selectTabla) return;
        
        const tabla = selectTabla.value;
        
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
        if (!btn) return;
        
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

    function descargarBackup(archivo) {
        window.open(base_url + 'backup/descargarBackup?archivo=' + encodeURIComponent(archivo), '_blank');
    }

    async function ejecutarImportacion(e) {
        e.preventDefault();
        
        const archivoInput = document.getElementById('archivoSQL');
        if (!archivoInput) return;
        
        const archivo = archivoInput.files[0];
        const selectBaseDatos = document.getElementById('selectBaseDatos');
        if (!selectBaseDatos) return;
        
        const baseDatos = selectBaseDatos.value;
        
        if (!archivo) {
            Swal.fire({
                icon: 'warning',
                title: 'Seleccione un archivo',
                text: 'Debe seleccionar un archivo SQL para importar',
                confirmButtonColor: '#F59E0B'
            });
            return;
        }
        
        if (!archivo.name.toLowerCase().endsWith('.sql')) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo inválido',
                text: 'Solo se permiten archivos con extensión .sql',
                confirmButtonColor: '#EF4444'
            });
            return;
        }

        // Confirmación adicional
        const confirmResult = await Swal.fire({
            title: '¿Confirmar importación?',
            html: `
                <div class="text-left">
                    <p><strong>Archivo:</strong> ${archivo.name}</p>
                    <p><strong>Tamaño:</strong> ${formatearTamaño(archivo.size)}</p>
                    <p><strong>Base de datos:</strong> ${baseDatos}</p>
                    <br>
                    <div class="bg-red-50 border border-red-200 rounded p-3">
                        <p class="text-red-700 text-sm"><strong>¡ADVERTENCIA!</strong></p>
                        <p class="text-red-600 text-sm">Esta acción sobrescribirá todos los datos actuales de la base de datos seleccionada.</p>
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sí, importar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmResult.isConfirmed) return;

        const btn = document.getElementById('btnConfirmarImportar');
        if (!btn) return;
        
        const btnText = btn.innerHTML;
        
        try {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Importando...';
            
            const formData = new FormData();
            formData.append('archivo', archivo);
            formData.append('base_datos', baseDatos);
            
            const response = await fetch(base_url + 'backup/importarDB', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                // Preparar mensaje de éxito con detalles
                let mensaje = data.mensaje || 'La base de datos ha sido importada exitosamente';
                
                // Si hay detalles adicionales, mostrarlos
                if (data.detalles) {
                    mensaje += "\n\nDetalles de la importación:";
                    if (data.detalles.bd_pda && data.detalles.bd_pda.detalles) {
                        mensaje += "\n\nBD Principal:";
                        mensaje += `\n- Ejecutadas: ${data.detalles.bd_pda.detalles.ejecutadas || 0}`;
                        mensaje += `\n- Omitidas: ${data.detalles.bd_pda.detalles.omitidas || 0}`;
                        if (data.detalles.bd_pda.detalles.advertencias && data.detalles.bd_pda.detalles.advertencias.length > 0) {
                            mensaje += `\n- Advertencias: ${data.detalles.bd_pda.detalles.advertencias.length}`;
                        }
                    }
                    if (data.detalles.bd_pda_seguridad && data.detalles.bd_pda_seguridad.detalles) {
                        mensaje += "\n\nBD Seguridad:";
                        mensaje += `\n- Ejecutadas: ${data.detalles.bd_pda_seguridad.detalles.ejecutadas || 0}`;
                        mensaje += `\n- Omitidas: ${data.detalles.bd_pda_seguridad.detalles.omitidas || 0}`;
                        if (data.detalles.bd_pda_seguridad.detalles.advertencias && data.detalles.bd_pda_seguridad.detalles.advertencias.length > 0) {
                            mensaje += `\n- Advertencias: ${data.detalles.bd_pda_seguridad.detalles.advertencias.length}`;
                        }
                    }
                }
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Importación Completada!',
                    text: mensaje,
                    confirmButtonColor: '#10B981'
                });
                ocultarModalImportar();
                cargarListaBackups();
                cargarEstadisticas();
            } else {
                // Mostrar error con más detalles
                let mensajeError = data.mensaje || 'Error al importar la base de datos';
                
                if (data.detalles && data.detalles.errores && data.detalles.errores.length > 0) {
                    mensajeError += "\n\nPrimeros errores encontrados:";
                    data.detalles.errores.slice(0, 3).forEach(error => {
                        mensajeError += "\n- " + error;
                    });
                    
                    if (data.detalles.errores.length > 3) {
                        mensajeError += `\n... y ${data.detalles.errores.length - 3} errores más.`;
                    }
                }
                
                throw new Error(mensajeError);
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error en la Importación',
                text: error.message || 'Error al importar la base de datos',
                confirmButtonColor: '#EF4444'
            });
        } finally {
            btn.disabled = false;
            btn.innerHTML = btnText;
        }
    }

    // Funciones de carga de datos
    async function cargarListaBackups() {
        const loader = document.getElementById('loaderTableBackups');
        
        try {
            if (loader) {
                loader.classList.remove('hidden');
            }
            
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
            if (loader) {
                loader.classList.add('hidden');
            }
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
                                <button data-action="descargar" data-archivo="${row.nombre_archivo}" 
                                        class="px-3 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600 transition-colors duration-200"
                                        title="Descargar">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button data-action="eliminar" data-archivo="${row.nombre_archivo}" 
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
                if (select) {
                    select.innerHTML = '<option value="">Seleccione una tabla...</option>';
                    
                    data.tablas.forEach(tabla => {
                        const option = document.createElement('option');
                        option.value = tabla.name;
                        option.textContent = tabla.name;
                        select.appendChild(option);
                    });
                }
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
                const totalBackupsEl = document.getElementById('totalBackups');
                if (totalBackupsEl) {
                    totalBackupsEl.textContent = stats.total || '0';
                }
                const ultimoBackupEl = document.getElementById('ultimoBackup');
                if (ultimoBackupEl) {
                    ultimoBackupEl.textContent = stats.ultimo || 'N/A';
                }
                const espacioTotalEl = document.getElementById('espacioTotal');
                if (espacioTotalEl) {
                    espacioTotalEl.textContent = stats.espacio || 'N/A';
                }
            }
        } catch (error) {
            console.error('Error al cargar estadísticas:', error);
        }
    }

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