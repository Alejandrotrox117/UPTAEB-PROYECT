let permisosUsuario = {
    ver: false,
    crear: false,
    editar: false,
    eliminar: false,
    exportar: false,
    acceso_total: false
};


function obtenerPermisos() {
    if (window.permisosCompras && typeof window.permisosCompras === 'object' && window.permisosCompras.ver !== undefined) {
        permisosUsuario = {
            ver: window.permisosCompras.ver || false,
            crear: window.permisosCompras.crear || false,
            editar: window.permisosCompras.editar || false,
            eliminar: window.permisosCompras.eliminar || false,
            exportar: window.permisosCompras.exportar || false,
            acceso_total: window.permisosCompras.acceso_total || false
        };
    } else {
        console.warn('No se encontraron permisos de compras en window.permisosCompras');
        try {
            const permisoVer = document.getElementById('permisoVer');
            const permisoCrear = document.getElementById('permisoCrear');
            const permisoEditar = document.getElementById('permisoEditar');
            const permisoEliminar = document.getElementById('permisoEliminar');
            
            if (permisoVer || permisoCrear || permisoEditar || permisoEliminar) {
                permisosUsuario = {
                    ver: permisoVer ? permisoVer.value === '1' : false,
                    crear: permisoCrear ? permisoCrear.value === '1' : false,
                    editar: permisoEditar ? permisoEditar.value === '1' : false,
                    eliminar: permisoEliminar ? permisoEliminar.value === '1' : false,
                    exportar: false,
                    acceso_total: false
                };
            }
        } catch (e) {
            console.error('Error al obtener permisos:', e);
        }
    }
}

function generarBotonesAccionConPermisos(data, type, row) {
    var idCompra = row.idcompra || "";
    var nroCompra = row.nro_compra || "Sin número";
    var estadoActual = row.estatus_compra || "";
    var botones = [];

    if (permisosUsuario.ver || permisosUsuario.acceso_total) {
        botones.push(`
            <button
                class="ver-compra-btn text-green-600 hover:text-green-700 p-1 transition-colors duration-150"
                data-idcompra="${idCompra}"
                title="Ver detalles"
            >
                <i class="fas fa-eye fa-fw text-base"></i>
            </button>
        `);
    }

    if ((permisosUsuario.editar || permisosUsuario.acceso_total) && estadoActual.toUpperCase() === "BORRADOR") {
        botones.push(`
            <button
                class="editar-compra-btn text-blue-600 hover:text-blue-700 p-1 transition-colors duration-150"
                data-idcompra="${idCompra}"
                title="Editar"
            >
                <i class="fas fa-edit fa-fw text-base"></i>
            </button>
        `);
    }

    // Botones de cambio de estado
    switch (estadoActual.toUpperCase()) {
        case "BORRADOR":
            // Quien puede crear o eliminar puede enviar a autorización
            if (permisosUsuario.crear || permisosUsuario.eliminar || permisosUsuario.acceso_total) {
                botones.push(`
                    <button
                        class="cambiar-estado-btn text-blue-500 hover:text-blue-700 p-1 transition-colors duration-150"
                        data-idcompra="${idCompra}"
                        data-nuevo-estado="POR_AUTORIZAR"
                        title="Enviar a Autorización"
                    >
                        <i class="fas fa-paper-plane fa-fw text-base"></i>
                    </button>
                `);
            }
            break;
        case "POR_AUTORIZAR":
            // Solo quien puede eliminar puede autorizar o devolver a borrador
            if (permisosUsuario.eliminar || permisosUsuario.acceso_total) {
                botones.push(`
                    <button
                        class="cambiar-estado-btn text-green-500 hover:text-green-700 p-1 transition-colors duration-150"
                        data-idcompra="${idCompra}"
                        data-nuevo-estado="AUTORIZADA"
                        title="Autorizar Compra"
                    >
                        <i class="fas fa-check fa-fw text-base"></i>
                    </button>
                    <button
                        class="cambiar-estado-btn text-yellow-500 hover:text-yellow-700 p-1 transition-colors duration-150"
                        data-idcompra="${idCompra}"
                        data-nuevo-estado="BORRADOR"
                        title="Devolver a Borrador"
                    >
                        <i class="fas fa-undo fa-fw text-base"></i>
                    </button>
                `);
            }
            break;
    }

    if (permisosUsuario.editar || permisosUsuario.acceso_total) {
        const estadosConPago = ["AUTORIZADA", "POR_PAGAR", "PAGO_FRACCIONADO"];
        if (estadosConPago.includes(estadoActual.toUpperCase())) {
            botones.push(`
                <button
                    class="ir-pagos-btn text-green-600 hover:text-green-800 p-1 transition-colors duration-150"
                    data-idcompra="${idCompra}"
                    title="Ir a Pagos"
                >
                    <i class="fas fa-credit-card fa-fw text-base"></i>
                </button>
            `);
        }
    }

    if ((permisosUsuario.eliminar || permisosUsuario.acceso_total) && estadoActual.toUpperCase() === "BORRADOR") {
        botones.push(`
            <button
                class="eliminar-compra-btn text-red-600 hover:text-red-700 p-1 transition-colors duration-150"
                data-idcompra="${idCompra}"
                data-nro="${nroCompra}"
                title="Eliminar"
            >
                <i class="fas fa-trash-alt fa-fw text-base"></i>
            </button>
        `);
    }

    return `<div class="inline-flex items-center space-x-1">${botones.join('')}</div>`;
}

function verificarPermiso(accion) {
    switch (accion) {
        case 'crear':
            if (!permisosUsuario.crear && !permisosUsuario.acceso_total) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acceso Denegado',
                    text: 'No tiene permisos para registrar compras.',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }
            break;
        case 'editar':
            if (!permisosUsuario.editar && !permisosUsuario.acceso_total) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acceso Denegado',
                    text: 'No tiene permisos para editar compras.',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }
            break;
        case 'eliminar':
            if (!permisosUsuario.eliminar && !permisosUsuario.acceso_total) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acceso Denegado',
                    text: 'No tiene permisos para eliminar compras.',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }
            break;
        case 'cambiarEstado':
            if (!permisosUsuario.crear && !permisosUsuario.eliminar && !permisosUsuario.acceso_total) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acceso Denegado',
                    text: 'No tiene permisos para cambiar el estado de las compras.',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }
            break;
        case 'ver':
            if (!permisosUsuario.ver && !permisosUsuario.acceso_total) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acceso Denegado',
                    text: 'No tiene permisos para ver las compras.',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }
            break;
        case 'exportar':
            if (!permisosUsuario.exportar && !permisosUsuario.acceso_total) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acceso Denegado',
                    text: 'No tiene permisos para exportar compras.',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }
            break;
        default:
            return true;
    }
    return true;
}

document.addEventListener("DOMContentLoaded", function () {
    obtenerPermisos();

    if (!permisosUsuario.ver && !permisosUsuario.crear && !permisosUsuario.editar && !permisosUsuario.eliminar) {
        setTimeout(function() {
            obtenerPermisos();
        }, 100);
    }
});

window.permisosCompras = window.permisosCompras || {};

const permisosOriginales = window.permisosCompras;

window.permisosCompras = Object.assign({}, permisosOriginales, {
    obtenerPermisos: obtenerPermisos,
    generarBotonesAccionConPermisos: generarBotonesAccionConPermisos,
    verificarPermiso: verificarPermiso,
    permisosUsuario: permisosUsuario
});

window.verificarPermiso = verificarPermiso;
