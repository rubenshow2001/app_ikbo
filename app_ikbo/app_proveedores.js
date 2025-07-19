document.addEventListener('DOMContentLoaded', () => {
    const API_BASE_URL = 'http://localhost/inventario_api/public/inventario'; // Asegúrate que esta es la URL base correcta
    const supplierForm = document.getElementById('supplierForm');
    const supplierIdInput = document.getElementById('supplierId');
    const submitButton = document.getElementById('submitButton');
    const clearFormButton = document.getElementById('clearFormButton');
    const suppliersTableBody = document.getElementById('suppliersTableBody');
    const refreshSuppliersButton = document.getElementById('refreshSuppliersButton');

    // Función para mostrar alertas (copiada del HTML para auto-contención)
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
        supplierForm.reset();
        supplierIdInput.value = '';
        submitButton.textContent = 'Crear Proveedor';
        submitButton.classList.remove('btn-warning');
        submitButton.classList.add('btn-primary');
        submitButton.querySelector('i').className = 'fas fa-save me-1'; // Reset icon
    };

    // Función para cargar los proveedores existentes
    const loadSuppliers = async () => {
        suppliersTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Cargando proveedores...</td></tr>';
        try {
            const response = await fetch(`${API_BASE_URL}/proveedores`); // Endpoint para listar proveedores
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Error HTTP! Estado: ${response.status}. Mensaje: ${errorText}`);
            }
            const data = await response.json();
            let suppliers = [];

            // Ajuste según la estructura de tu API: debería estar en 'data'
            if (data && Array.isArray(data.data)) {
                suppliers = data.data;
            } else {
                console.error("La respuesta de la API no es un array o no tiene la estructura esperada:", data);
                throw new Error("Formato de respuesta de la API inesperado.");
            }

            suppliersTableBody.innerHTML = ''; // Limpiar el contenido existente

            if (suppliers.length === 0) {
                suppliersTableBody.innerHTML = '<tr><td colspan="7" class="text-center">No hay proveedores registrados.</td></tr>';
                return;
            }

            suppliers.forEach(supplier => {
                const row = suppliersTableBody.insertRow();
                row.innerHTML = `
                    <td>${supplier.id_proveedor}</td>
                    <td>${supplier.nombre_proveedor}</td>
                    <td>${supplier.contacto_proveedor || 'N/A'}</td>
                    <td>${supplier.telefono_proveedor || 'N/A'}</td>
                    <td>${supplier.email_proveedor}</td>
                    <td>${supplier.direccion_proveedor || 'N/A'}</td>
                    <td>
                        <button class="btn btn-sm btn-info btn-action edit-btn" data-id="${supplier.id_proveedor}" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-action delete-btn" data-id="${supplier.id_proveedor}" title="Eliminar">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                `;
            });
            addEventListenersToSupplierButtons();
        } catch (error) {
            console.error('Error al cargar proveedores:', error);
            suppliersTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Error al cargar proveedores: ${error.message}</td></tr>`;
            showAlert('Error al cargar proveedores: ' + error.message, 'danger');
        }
    };

    // Función para manejar el envío del formulario (Crear/Actualizar)
    supplierForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(supplierForm);
        const supplierData = Object.fromEntries(formData.entries());

        const supplierId = supplierIdInput.value;
        let url = `${API_BASE_URL}/proveedor`; // Endpoint para crear proveedor
        let method = 'POST';

        if (supplierId) {
            // Si hay un ID, asumimos que es una actualización.
            // Tu API solo mostró POST para crear. Si la actualización
            // es vía POST, el backend debe manejar si el ID existe.
            // Lo ideal sería un PUT a /inventario/proveedor/{id}.
            supplierData.id_proveedor = parseInt(supplierId);
            // Si tu API no tiene PUT, sigue usando POST y asegúrate
            // que tu backend sepa actualizar si el ID está presente.
        }

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(supplierData),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(`Error: ${response.status} - ${errorData.message || 'Error desconocido'}`);
            }

            const result = await response.json();
            showAlert('Operación exitosa: ' + (result.message || 'Proveedor guardado con éxito.'), 'success');
            clearForm();
            loadSuppliers(); // Recargar la lista
        } catch (error) {
            console.error('Error al guardar proveedor:', error);
            showAlert('Error al guardar proveedor: ' + error.message, 'danger');
        }
    });

    // Event listener para el botón de limpiar formulario
    clearFormButton.addEventListener('click', clearForm);

    // Event listener para el botón de actualizar lista
    refreshSuppliersButton.addEventListener('click', loadSuppliers);

    // Función para añadir event listeners a los botones de editar/eliminar de la tabla
    const addEventListenersToSupplierButtons = () => {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', async (event) => {
                const supplierId = event.target.dataset.id;
                // Para precargar datos en el formulario para edición
                const row = event.target.closest('tr');
                document.getElementById('supplierId').value = supplierId;
                document.getElementById('nombre_proveedor').value = row.cells[1].textContent;
                document.getElementById('contacto_proveedor').value = row.cells[2].textContent === 'N/A' ? '' : row.cells[2].textContent;
                document.getElementById('telefono_proveedor').value = row.cells[3].textContent === 'N/A' ? '' : row.cells[3].textContent;
                document.getElementById('email_proveedor').value = row.cells[4].textContent;
                document.getElementById('direccion_proveedor').value = row.cells[5].textContent === 'N/A' ? '' : row.cells[5].textContent;

                submitButton.textContent = 'Actualizar Proveedor';
                submitButton.classList.remove('btn-primary');
                submitButton.classList.add('btn-warning');
                submitButton.querySelector('i').className = 'fas fa-sync-alt me-1'; // Change icon to refresh/update
            });
        });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', async (event) => {
                const supplierId = event.target.dataset.id;
                if (confirm('¿Estás seguro de que quieres eliminar este proveedor?')) {
                    try {
                        // Para eliminar, necesitarás un endpoint DELETE en tu API.
                        // Ejemplo: DELETE /inventario/proveedor/{id}
                        const response = await fetch(`${API_BASE_URL}/proveedor/${supplierId}`, { // Asume un endpoint DELETE por ID
                            method: 'DELETE',
                        });

                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(`Error al eliminar: ${response.status} - ${errorData.message || 'Error desconocido'}`);
                        }

                        const result = await response.json();
                        showAlert('Proveedor eliminado: ' + (result.message || 'Proveedor eliminado con éxito.'), 'success');
                        loadSuppliers(); // Recargar la lista
                    } catch (error) {
                        console.error('Error al eliminar proveedor:', error);
                        showAlert('Error al eliminar proveedor: ' + error.message, 'danger');
                    }
                }
            });
        });
    };

    // Cargar proveedores al iniciar la página
    loadSuppliers();
});