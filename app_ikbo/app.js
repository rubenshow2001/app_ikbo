document.addEventListener('DOMContentLoaded', () => {
    const API_BASE_URL = 'http://localhost/inventario_api/public/'; // Asegúrate que esta es la URL base correcta
    const productForm = document.getElementById('productForm');
    const productIdInput = document.getElementById('productId');
    const submitButton = document.getElementById('submitButton');
    const clearFormButton = document.getElementById('clearFormButton');
    const productsTableBody = document.getElementById('productsTableBody');
    const refreshProductsButton = document.getElementById('refreshProductsButton');

    // Función para limpiar el formulario
    const clearForm = () => {
        productForm.reset();
        productIdInput.value = '';
        submitButton.textContent = 'Crear Producto';
        submitButton.classList.remove('btn-warning');
        submitButton.classList.add('btn-primary');
    };

    // Función para cargar los productos existentes
const loadProducts = async () => {
    productsTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Cargando productos...</td></tr>';
    try {
        const response = await fetch(`${API_BASE_URL}inventario/productos`);

        if (!response.ok) {
            // Si la respuesta HTTP no es 200 OK, lanza un error
            const errorText = await response.text(); // Intenta leer el texto del error
            throw new Error(`Error HTTP! Estado: ${response.status}. Mensaje: ${errorText}`);
        }

        const data = await response.json(); // Parsea la respuesta como JSON
        let products = [];

        // --- AQUI ES DONDE AJUSTAS SEGÚN TU API ---
        // Opción A: Si tu API devuelve directamente el array (ej: `[{}, {}]`)
        if (Array.isArray(data)) {
            products = data;
        }
        // Opción B: Si tu API devuelve un objeto con una propiedad que contiene el array (ej: `{ "productos": [{}, {}] }`)
        else if (data && Array.isArray(data.productos)) { // <-- Asume que el array está en 'productos'
            products = data.productos;
        }
        // Opción C: Si tu API devuelve un objeto con una propiedad 'data' (ej: `{ "data": [{}, {}] }`)
        else if (data && Array.isArray(data.data)) { // <-- Asume que el array está en 'data'
            products = data.data;
        }
        // Puedes añadir más `else if` según la estructura real de tu API.
        // Si ninguna de las anteriores coincide y `data` no es un array directamente, algo está mal.
        else {
            console.error("La respuesta de la API no es un array o no tiene la estructura esperada:", data);
            throw new Error("Formato de respuesta de la API inesperado.");
        }
        // --- FIN DEL AJUSTE ---

        productsTableBody.innerHTML = ''; // Limpiar el contenido existente

        if (products.length === 0) {
            productsTableBody.innerHTML = '<tr><td colspan="7" class="text-center">No hay productos registrados.</td></tr>';
            return;
        }

        products.forEach(product => {
            const row = productsTableBody.insertRow();
            row.innerHTML = `
                <td>${product.id_producto}</td>
                <td>${product.nombre_producto}</td>
                <td>${product.sku}</td>
                <td>$${parseFloat(product.precio_unitario).toFixed(2)}</td>
                <td>${product.unidad_medida || 'N/A'}</td>
                <td>${product.stock_minimo}</td>
                <td>
                    <button class="btn btn-sm btn-info edit-btn" data-id="${product.id_producto}">Editar</button>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="${product.id_producto}">Eliminar</button>
                </td>
            `;
        });
        addEventListenersToProductButtons();
    } catch (error) {
        console.error('Error al cargar productos:', error);
        productsTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Error al cargar productos: ${error.message}</td></tr>`;
    }
};

    // Función para manejar el envío del formulario (Crear/Actualizar)
    productForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(productForm);
        const productData = Object.fromEntries(formData.entries());

        // Convertir stock_minimo y precio_unitario a números
        productData.stock_minimo = parseInt(productData.stock_minimo);
        productData.precio_unitario = parseFloat(productData.precio_unitario);

        const productId = productIdInput.value;
        let url = `${API_BASE_URL}inventario/producto`;
        let method = 'POST';

        // Si hay un ID de producto, significa que estamos actualizando
        if (productId) {
            // Asumiendo que tu API soporta PUT para actualizar un producto por ID o SKU
            // Por simplicidad en este ejemplo, usaremos POST para ambos si tu API así lo requiere,
            // pero lo ideal sería PUT /inventario/producto/{id}
            // Si tu API no tiene un endpoint para PUT, esta lógica puede necesitar ajustarse
            // Para el ejemplo proporcionado, solo se mostró POST para crear, no para actualizar.
            // Para actualizar, necesitarías un endpoint como /inventario/producto/{sku} con PUT.
            // Dada tu API, la actualización sería probablemente un POST con el SKU en el cuerpo.
            url = `${API_BASE_URL}/producto`; // Todavía POST, pero el backend debe manejar la actualización si SKU existe
            // Para una actualización real se esperaría un PUT a /inventario/producto/{sku} o /inventario/producto/{id}
            // o un campo 'id_producto' en el JSON para el POST que indique actualización.
            // Aquí enviamos el 'id_producto' en el cuerpo para que el backend pueda decidir.
            productData.id_producto = parseInt(productId);
            method = 'POST'; // Asumiendo que tu API maneja PUT/PATCH si es para actualizar
                             // o un POST que inteligentemente actualice si ID/SKU existe.
                             // En el contexto que diste, solo hay POST para crear.
                             // Para la actualización REALMENTE necesitarías un endpoint PUT/PATCH.
                             // Adaptaremos esto para un POST que "simula" actualización si hay ID.
        }

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(productData),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(`Error: ${response.status} - ${errorData.message || 'Error desconocido'}`);
            }

            const result = await response.json();
            alert('Operación exitosa: ' + result.message || JSON.stringify(result));
            clearForm();
            loadProducts(); // Recargar la lista de productos
        } catch (error) {
            console.error('Error al guardar producto:', error);
            alert('Error al guardar producto: ' + error.message);
        }
    });

    // Event listener para el botón de limpiar formulario
    clearFormButton.addEventListener('click', clearForm);

    // Event listener para el botón de actualizar lista
    refreshProductsButton.addEventListener('click', loadProducts);

    // Función para añadir event listeners a los botones de editar/eliminar de la tabla
    const addEventListenersToProductButtons = () => {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', async (event) => {
                const productId = event.target.dataset.id;
                // En un escenario real, harías un GET a /inventario/producto/{id}
                // para precargar los datos. Para este ejemplo, lo haremos con los datos de la tabla.
                const row = event.target.closest('tr');
                document.getElementById('productId').value = productId;
                document.getElementById('nombre_producto').value = row.cells[1].textContent;
                document.getElementById('sku').value = row.cells[2].textContent;
                document.getElementById('precio_unitario').value = parseFloat(row.cells[3].textContent.replace('$', ''));
                document.getElementById('unidad_medida').value = row.cells[4].textContent === 'N/A' ? '' : row.cells[4].textContent;
                document.getElementById('stock_minimo').value = parseInt(row.cells[5].textContent);

                submitButton.textContent = 'Actualizar Producto';
                submitButton.classList.remove('btn-primary');
                submitButton.classList.add('btn-warning');
            });
        });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', async (event) => {
                const productId = event.target.dataset.id;
                if (confirm('¿Estás seguro de que quieres eliminar este producto?')) {
                    try {
                        // Para eliminar, necesitarías un endpoint DELETE en tu API,
                        // por ejemplo: DELETE /inventario/producto/{id} o /inventario/producto_por_sku/{sku}
                        // Tu contexto no incluye un endpoint DELETE, así que este código es solo un placeholder
                        // y NECESITARÁS IMPLEMENTARLO EN TU API.
                        const response = await fetch(`${API_BASE_URL}/producto/${productId}`, { // Ejemplo de endpoint DELETE
                            method: 'DELETE',
                        });

                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(`Error al eliminar: ${response.status} - ${errorData.message || 'Error desconocido'}`);
                        }

                        const result = await response.json();
                        alert('Producto eliminado: ' + (result.message || JSON.stringify(result)));
                        loadProducts(); // Recargar la lista de productos
                    } catch (error) {
                        console.error('Error al eliminar producto:', error);
                        alert('Error al eliminar producto: ' + error.message);
                    }
                }
            });
        });
    };

    // Cargar productos al iniciar la página
    loadProducts();
});