document.addEventListener('DOMContentLoaded', () => {
    const API_BASE_URL = 'http://localhost/inventario_api/public/inventario'; // URL base de tu API

    const movementForm = document.getElementById('movementForm');
    const skuSelect = document.getElementById('sku');
    const tipoMovimientoSelect = document.getElementById('tipo_movimiento');
    const idProveedorSelect = document.getElementById('id_proveedor');
    const cantidadMovidaInput = document.getElementById('cantidad_movida');
    const numeroLoteInput = document.getElementById('numero_lote');
    const fechaVencimientoInput = document.getElementById('fecha_vencimiento');
    const ubicacionAlmacenInput = document.getElementById('ubicacion_almacen');
    const costoCompraUnitarioInput = document.getElementById('costo_compra_unitario');
    const razonMovimientoTextarea = document.getElementById('razon_movimiento');

    const numeroLoteGroup = document.getElementById('numeroLoteGroup');
    const entrySpecificFields = document.getElementById('entrySpecificFields');
    const razonMovimientoGroup = document.getElementById('razonMovimientoGroup');

    const entryRequiredSpans = document.querySelectorAll('.entry-required');
    const exitAdjustRequiredSpans = document.querySelectorAll('.exit-adjust-required');

    const submitButton = document.getElementById('submitButton');
    const clearFormButton = document.getElementById('clearFormButton');
    const movementsTableBody = document.getElementById('movementsTableBody');
    const refreshMovementsButton = document.getElementById('refreshMovementsButton');

    // Función para mostrar alertas (puedes centralizarla si compartes un archivo JS común)
    function showAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        alertContainer.innerHTML = alertHtml;
        setTimeout(() => {
            const alertElement = alertContainer.querySelector('.alert');
            if (alertElement) {
                new bootstrap.Alert(alertElement).close();
            }
        }, 5000);
    }

    // Función para limpiar el formulario
    const clearForm = () => {
        movementForm.reset();
        toggleFieldsBasedOnMovementType(); // Reset visibility
        skuSelect.focus(); // Poner foco en el primer campo
    };

    // Cargar productos en el select
    const loadProductsIntoSelect = async () => {
        try {
            const response = await fetch(`${API_BASE_URL}/productos`);
            if (!response.ok) throw new Error('Error al cargar productos');
            const data = await response.json();
            const products = data.data; // Asumiendo que los productos vienen en 'data'

            skuSelect.innerHTML = '<option value="">Seleccione un producto</option>';
            products.forEach(product => {
                const option = document.createElement('option');
                option.value = product.sku;
                option.textContent = `${product.nombre_producto} (SKU: ${product.sku})`;
                skuSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Error cargando productos:', error);
            showAlert('No se pudieron cargar los productos para el formulario.', 'danger');
        }
    };

    // Cargar proveedores en el select
    const loadSuppliersIntoSelect = async () => {
        try {
            const response = await fetch(`${API_BASE_URL}/proveedores`);
            if (!response.ok) throw new Error('Error al cargar proveedores');
            const data = await response.json();
            const suppliers = data.data; // Asumiendo que los proveedores vienen en 'data'

            idProveedorSelect.innerHTML = '<option value="">Seleccione un proveedor</option>';
            suppliers.forEach(supplier => {
                const option = document.createElement('option');
                option.value = supplier.id_proveedor;
                option.textContent = `${supplier.nombre_proveedor} (${supplier.contacto_proveedor || 'Sin contacto'})`;
                idProveedorSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Error cargando proveedores:', error);
            showAlert('No se pudieron cargar los proveedores para el formulario.', 'danger');
        }
    };

    // Función para mostrar/ocultar campos según el tipo de movimiento
    const toggleFieldsBasedOnMovementType = () => {
        const type = tipoMovimientoSelect.value;

        // Ocultar todos los grupos por defecto
        numeroLoteGroup.style.display = 'none';
        entrySpecificFields.style.display = 'none';
        razonMovimientoGroup.style.display = 'none';

        // Remover atributos 'required'
        numeroLoteInput.removeAttribute('required');
        idProveedorSelect.removeAttribute('required');
        razonMovimientoTextarea.removeAttribute('required');

        // Ocultar indicadores de requerido específicos
        entryRequiredSpans.forEach(span => span.style.display = 'none');
        exitAdjustRequiredSpans.forEach(span => span.style.display = 'none');

        if (type === 'entrada') {
            numeroLoteGroup.style.display = 'block';
            entrySpecificFields.style.display = 'flex'; // Usar flex para la fila
            numeroLoteInput.setAttribute('required', 'required');
            idProveedorSelect.setAttribute('required', 'required');
            entryRequiredSpans.forEach(span => span.style.display = 'inline'); // Mostrar asteriscos de entrada
        } else if (type === 'salida' || type === 'ajuste') {
            razonMovimientoGroup.style.display = 'block';
            razonMovimientoTextarea.setAttribute('required', 'required');
            exitAdjustRequiredSpans.forEach(span => span.style.display = 'inline'); // Mostrar asteriscos de salida/ajuste
            
            // Opcional: mostrar lote para salidas si es relevante para el seguimiento
            numeroLoteGroup.style.display = 'block'; 
            numeroLoteInput.removeAttribute('required'); // No requerido para salidas
        }
    };

    tipoMovimientoSelect.addEventListener('change', toggleFieldsBasedOnMovementType);

    // Manejar el envío del formulario
    movementForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const type = tipoMovimientoSelect.value;
        let url = '';
        let movementData = {};

        // Validaciones básicas antes de enviar
        if (!skuSelect.value) {
            showAlert('Debe seleccionar un producto.', 'warning');
            skuSelect.focus();
            return;
        }
        if (!cantidadMovidaInput.value || parseInt(cantidadMovidaInput.value) <= 0) {
            showAlert('La cantidad debe ser un número positivo.', 'warning');
            cantidadMovidaInput.focus();
            return;
        }

        if (type === 'entrada') {
            url = `${API_BASE_URL}/entrada`;
            movementData = {
                sku: skuSelect.value,
                numero_lote: numeroLoteInput.value,
                cantidad: parseInt(cantidadMovidaInput.value),
                fecha_vencimiento: fechaVencimientoInput.value || null,
                id_proveedor: parseInt(idProveedorSelect.value),
                ubicacion_almacen: ubicacionAlmacenInput.value || null,
                costo_compra_unitario: parseFloat(costoCompraUnitarioInput.value) || null
            };
            if (!movementData.numero_lote) {
                showAlert('El número de lote es obligatorio para entradas.', 'warning');
                numeroLoteInput.focus();
                return;
            }
            if (!movementData.id_proveedor) {
                showAlert('El proveedor es obligatorio para entradas.', 'warning');
                idProveedorSelect.focus();
                return;
            }

        } else if (type === 'salida') {
            url = `${API_BASE_URL}/salida`;
            movementData = {
                sku: skuSelect.value,
                cantidad: parseInt(cantidadMovidaInput.value),
                razon_movimiento: razonMovimientoTextarea.value
            };
            if (!movementData.razon_movimiento) {
                showAlert('La razón del movimiento es obligatoria para salidas.', 'warning');
                razonMovimientoTextarea.focus();
                return;
            }

        } else if (type === 'ajuste') {
            // Asumiendo que tu API tiene un endpoint específico para ajustes o lo maneja la entrada/salida
            // Si el ajuste es una entrada o salida, usa los endpoints existentes.
            // Si es un ajuste independiente, necesitarás un endpoint específico: POST /inventario/ajuste
            url = `${API_BASE_URL}/ajuste`; // EJEMPLO: Esto ASUME que tienes un endpoint /ajuste
            movementData = {
                sku: skuSelect.value,
                cantidad: parseInt(cantidadMovidaInput.value), // Cantidad a ajustar (positiva o negativa)
                razon_movimiento: razonMovimientoTextarea.value,
                tipo_ajuste: 'positivo' // O 'negativo', o que la cantidad_movida indique la dirección
                                        // TU API DEBE DEFINIR CÓMO RECIBE UN AJUSTE
            };
             if (!movementData.razon_movimiento) {
                showAlert('La razón del movimiento es obligatoria para ajustes.', 'warning');
                razonMovimientoTextarea.focus();
                return;
            }
            showAlert('La funcionalidad de "Ajuste" requiere un endpoint específico en tu API que maneje la lógica de ajuste. Actualmente, este formulario solo maneja "Entrada" y "Salida" con los endpoints provistos.', 'info');
            return; // Detener el envío si el ajuste no está completamente implementado en el backend
        } else {
            showAlert('Debe seleccionar un tipo de movimiento.', 'warning');
            tipoMovimientoSelect.focus();
            return;
        }

        try {
            const response = await fetch(url, {
                method: 'POST', // Ambas operaciones (entrada/salida) son POST
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(movementData),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(`Error: ${response.status} - ${errorData.message || 'Error desconocido'}`);
            }

            const result = await response.json();
            showAlert('Movimiento registrado con éxito: ' + (result.message || JSON.stringify(result)), 'success');
            clearForm();
            // loadMovements(); // Recargar la lista de movimientos recientes si la tuvieras
        } catch (error) {
            console.error('Error al registrar movimiento:', error);
            showAlert('Error al registrar movimiento: ' + error.message, 'danger');
        }
    });


    // Función para cargar los movimientos recientes
    const loadMovements = async () => {
        movementsTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Cargando movimientos...</td></tr>';
        try {
            // Asegúrate que esta URL coincida con tu endpoint GET para movimientos
            const response = await fetch(`${API_BASE_URL}/movimientos`);
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Error HTTP! Estado: ${response.status}. Mensaje: ${errorText}`);
            }
            const data = await response.json();
            let movements = [];

            // Asumiendo que los movimientos vienen en 'data', similar a productos/proveedores
            if (data && Array.isArray(data.data)) {
                movements = data.data;
            } else {
                console.error("La respuesta de la API para movimientos no es un array o no tiene la estructura esperada:", data);
                throw new Error("Formato de respuesta de la API para movimientos inesperado.");
            }

            movementsTableBody.innerHTML = ''; // Limpiar el contenido existente

            if (movements.length === 0) {
                movementsTableBody.innerHTML = '<tr><td colspan="7" class="text-center">No hay movimientos registrados.</td></tr>';
                return;
            }

            movements.forEach(movement => {
                const row = movementsTableBody.insertRow();
                // Asegúrate de que los nombres de las propiedades (ej: movement.tipo_movimiento)
                // coincidan con los nombres que devuelve tu API.
                row.innerHTML = `
                    <td>${movement.id_movimiento}</td>
                    <td>${movement.sku || 'N/A'}</td> <td>${movement.tipo_movimiento}</td>
                    <td>${movement.cantidad_movida}</td>
                    <td>${new Date(movement.fecha_movimiento).toLocaleString()}</td>
                    <td>${movement.razon_movimiento || 'Sin razón'}</td>
                    <td>${movement.numero_lote || 'N/A'}</td> `;
            });
            // Si necesitas botones de acción en la tabla de movimientos, añade aquí el listener:
            // addEventListenersToMovementButtons();
        } catch (error) {
            console.error('Error al cargar movimientos:', error);
            movementsTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Error al cargar movimientos: ${error.message}</td></tr>`;
            showAlert('Error al cargar movimientos recientes: ' + error.message, 'danger');
        }
    };


/*
    // Event listeners
    clearFormButton.addEventListener('click', clearForm);
    refreshMovementsButton.addEventListener('click', () => {
        // Implementar carga de movimientos recientes aquí si tu API tiene un endpoint para ello
        showAlert('Funcionalidad de "Movimientos Recientes" pendiente de implementación en la API.', 'info');
    });
*/	

    // Cargar datos iniciales al cargar la página
    loadProductsIntoSelect();
    loadSuppliersIntoSelect();
    toggleFieldsBasedOnMovementType(); // Inicializar el estado de los campos
	loadMovements(); // <-- ¡Llamar a esta función al inicio para que se carguen!
});