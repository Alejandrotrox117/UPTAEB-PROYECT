<?php headerAdmin($data); ?>
<!-- Main Content -->
<main class="flex-1 p-6">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Módulo de Producción</h2>
    <input type="text" placeholder="Buscar producción..." class="pl-10 pr-4 py-2 border rounded-lg text-gray-700 focus:outline-none">
  </div>
  <div class="min-h-screen mt-4">
    <h1 class="text-3xl font-bold text-gray-900">Producciones</h1>
    <p class="text-green-500 text-lg">Gestión de procesos de producción</p>
    <div class="bg-white p-6 mt-6 rounded-2xl shadow-md">
      <div class="flex justify-between items-center mb-4">
        <!-- Botón para abrir el modal de Registro -->
        <button   id="abrirModalBtn"  class="bg-green-500 text-white px-6 py-2 rounded-lg font-semibold">
          Registrar Producción
        </button>
      </div>
      <table id="TablaProduccion" class="w-full text-left border-collapse mt-6">
        <thead>
          <tr class="text-gray-500 text-sm border-b">
            <th class="py-2 px-3">ID Producción</th>
            <th class="py-2 px-3">Producto</th>
            <th class="py-2 px-3">Empleado</th>
            <th class="py-2 px-3">Cantidad</th>
            <th class="py-2 px-3">Fecha Inicio</th>
            <th class="py-2 px-3">Fecha Fin</th>
            <th class="py-2 px-3">Estado</th>
            <th class="py-2 px-3">Acciones</th>
          </tr>
        </thead>
        <tbody class="text-gray-900">
          
        </tbody>
      </table>
    </div>
  </div>
</main>


<div id="produccionModal" class="fixed inset-0 flex items-center justify-center bg-transparent backdrop-blur-[2px] opacity-0 pointer-events-none transition-opacity duration-300 z-50">
  <div class="bg-white rounded-xl shadow-lg overflow-hidden w-[95%] max-w-7xl"> <!-- Aumentamos el ancho máximo -->
    <!-- Encabezado -->
    <div class="px-8 py-6 border-b flex justify-between items-center">
      <h3 class="text-2xl font-bold text-gray-800">Registrar Producción</h3>
      <button onclick="cerrarModalProduccion()" class="text-gray-600 hover:text-gray-800 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <!-- Formulario -->
    <form id="produccionForm" class="px-8 py-6 space-y-6">
      <!-- Primera fila: Producto y Empleado -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-gray-700 font-medium mb-2">Producto</label>
          <select id="idproducto" name="idproducto" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            <option value="">Seleccione un producto</option>
            <!-- Opciones de productos se cargarán dinámicamente -->
          </select>
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-2">Empleado</label>
          <select id="idempleado" name="idempleado" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            <option value="">Cargando Empleado...</option>
          </select>
        </div>
      </div>

      <!-- Segunda fila: Cantidad, Fechas y Estado -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="block text-gray-700 font-medium mb-2">Cantidad a Producir</label>
                    <input type="hidden" id="idproduccion" name="idproduccion" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">

          <input type="number" id="cantidad_a_realizar" name="cantidad_a_realizar" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-2">Fecha de Inicio</label>
          <input type="date" id="fecha_inicio" name="fecha_inicio" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-2">Fecha de Fin</label>
          <input type="date" id="fecha_fin" name="fecha_fin" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
        </div>
      </div>

      <!-- Tercera fila: Estado -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-gray-700 font-medium mb-2">Estado</label>
          <select id="estado" name="estado" class="w-full border rounded-lg px-6 py-4 text-xl focus:outline-none">
            <option value="borrador">Borrador</option>
            <option value="en clasificacion">En Clasificación</option>
            <option value="empacando">Empacando</option>
            <option value="realizado">Realizado</option>
          </select>
        </div>
      </div>

      <!-- Tabla de Detalle de Producción -->
     <div class="max-w-[720px] mx-auto">

  



  <!-- Tabla de Detalle de Producción -->
 <!-- Botón para agregar insumos -->
<!-- Tabla de Detalle de Producción -->
<div class="max-w-[720px] mx-auto">
  <table id="TablaDetalleProduccion" class="w-full text-left table-auto border-collapse min-w-max">
    <thead>
      <tr class="border-b border-slate-300 bg-slate-50">
        <th class="p-4 text-sm font-normal leading-none text-slate-500">Producto</th>
        <th class="p-4 text-sm font-normal leading-none text-slate-500">Cantidad</th>
        <th class="p-4 text-sm font-normal leading-none text-slate-500">Cantidad Utilizada</th>
        <th class="p-4 text-sm font-normal leading-none text-slate-500">Observaciones</th>
        <th class="p-4 text-sm font-normal leading-none text-slate-500">Acciones</th>
      </tr>
    </thead>
    <tbody id="detalleProduccionBody">
      <!-- Aquí se cargarán los insumos dinámicamente -->
    </tbody>
  </table>
</div>

<!-- Botón para agregar insumos -->
<button type="button" id="agregarInsumoBtn" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition">
  + Agregar Insumo
</button>
</div>

      <!-- Botones -->
      <div class="flex justify-end space-x-6 mt-6">
        <button type="button" id="cerrarModalBtn" class="px-6 py-3 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition text-xl">
          Cancelar
        </button>
        <button type="button"  id="registrarProduccionBtn" class="px-6 py-3 bg-green-500 text-white rounded hover:bg-green-600 transition text-xl">
          Registrar
        </button>
      </div>
    </form>
  </div>
</div>
<script>
 document.getElementById("agregarInsumoBtn").addEventListener("click", () => {
    if (!productosCargados) {
        alert("Espere a que se carguen los productos...");
        return;
    }

    const tbody = document.getElementById("detalleProduccionBody");

    const tr = document.createElement("tr");
    tr.innerHTML = `
        <td>
            <select name="idproducto_insumo[]" class="w-full border rounded p-2" required>
                ${productos.map(p => `<option value="${p.idproducto}">${p.nombre}</option>`).join('')}
            </select>
        </td>
        <td>
            <input type="number" name="cantidad_insumo[]" class="w-full border rounded p-2" min="0" step="0.01" required />
        </td>
        <td>
            <input type="number" name="cantidad_utilizada[]" class="w-full border rounded p-2" min="0" step="0.01" required />
        </td>
        <td>
            <button type="button" class="eliminarInsumoBtn text-red-500"><i class="fas fa-trash"></i></button>
        </td>
    `;
    tbody.appendChild(tr);
});

// Eliminar insumo
document.addEventListener("click", function (e) {
    if (e.target.closest(".eliminarInsumoBtn")) {
        e.target.closest("tr").remove();
    }
});
let productos = []; // Variable global para almacenar productos
let productosCargados = false;

function cargarProductosParaInsumos() {
    fetch("produccion/getProductos")
        .then(res => res.json())
        .then(data => {
            if (data.status) {
                productos = data.data;
                productosCargados = true;
            } else {
                alert("No se pudieron cargar productos para insumos.");
            }
        })
        .catch(err => {
            console.error("Error al cargar productos:", err);
            alert("Error al cargar productos.");
        });
}

// Llama a la función al cargar la página
cargarProductosParaInsumos();
const formData = new FormData(document.getElementById("produccionForm"));
const data = {};
formData.forEach((value, key) => {
    data[key] = value;
});

// Obtener insumos
const insumos = [];
const filas = document.querySelectorAll("#detalleProduccionBody tr");

filas.forEach(fila => {
    const idproducto = fila.querySelector("select[name='idproducto_insumo[]']").value;
    const cantidad = fila.querySelector("input[name='cantidad_insumo[]']").value;
    const cantidad_utilizada = fila.querySelector("input[name='cantidad_utilizada[]']").value;

    if (idproducto && cantidad && cantidad_utilizada) {
        insumos.push({
            idproducto,
            cantidad,
            cantidad_utilizada
        });
    }
});

data.insumos = insumos;
</script>

<?php footerAdmin($data); ?>