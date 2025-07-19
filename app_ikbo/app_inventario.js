document.addEventListener('DOMContentLoaded', () => {
    const reportTableBody = document.getElementById('reportTableBody');
    const refreshReportButton = document.getElementById('refreshReportButton');
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');

    const API_URL = 'http://localhost/inventario_api/public/';

    // Función para obtener y mostrar el reporte de inventario
// --- Código corregido para fetchAndRenderReport ---
const fetchAndRenderReport = async () => {
    reportTableBody.innerHTML = '<tr><td colspan="6" class="text-center">Cargando reporte...</td></tr>';
    try {
        const response = await fetch(`${API_URL}inventario/inventario`);

        if (!response.ok) {
            throw new Error('La respuesta de la red no fue exitosa. Código: ' + response.status);
        }

        const apiResponse = await response.json();
        
        // === VERIFICACIÓN CORREGIDA ===
        // Aquí verificamos si la clave 'data' existe y es un array
        if (apiResponse.status === "success" && Array.isArray(apiResponse.data)) {
            renderReportTable(apiResponse.data); // Llama a la función con el array correcto
        } else {
            console.error('El formato de la respuesta de la API es incorrecto:', apiResponse);
            showAlert('Error: El formato de los datos recibidos es inválido.', 'danger');
            reportTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error al cargar el reporte: formato de datos no válido.</td></tr>';
        }
    } catch (error) {
        console.error('Error al cargar el reporte:', error);
        showAlert(`Error: ${error.message}`, 'danger');
        reportTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error al cargar el reporte. Intente de nuevo más tarde.</td></tr>';
    }
};

    // La función renderReportTable no necesita cambios, ya que ahora el caller se encarga de validar los datos
    const renderReportTable = (products) => {
        reportTableBody.innerHTML = '';
        if (products.length === 0) {
            reportTableBody.innerHTML = '<tr><td colspan="6" class="text-center">No se encontraron productos en el inventario.</td></tr>';
            return;
        }

        products.forEach(product => {
            const row = document.createElement('tr');
            let statusClass = '';
            let statusIcon = '';

            //const totalStock = product.stock_actual ? product.stock_actual : 0;
            const totalStock = product.StockActual ? product.StockActual : 0;

            if (totalStock <= 0) {
                statusClass = 'table-danger';
                statusIcon = '<i class="fas fa-exclamation-circle me-1"></i> Sin Stock';
            } else if (totalStock <= product.StockMinimo) {
                statusClass = 'table-warning';
                statusIcon = '<i class="fas fa-exclamation-triangle me-1"></i> Stock Bajo';
            } else {
                statusIcon = '<i class="fas fa-check-circle me-1"></i> Ok';
            }

            //row.classList.add(statusClass);

            row.innerHTML = `
				<td>${product.SKU}</td>
                <td>${product.NombreProducto}</td>
                <td>${product.StockActual}</td>
				<td>${product.CantidadVencida}</td>
                <td>${product.StockMinimo}</td>
                <td class="text-center">${statusIcon}</td>
            `;
            reportTableBody.appendChild(row);
        });
    };

    // Event listeners
    refreshReportButton.addEventListener('click', fetchAndRenderReport);
    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        filterReport(searchInput.value);
    });

    const filterReport = (query) => {
        const rows = reportTableBody.querySelectorAll('tr');
        query = query.toLowerCase();
        rows.forEach(row => {
            const sku = row.children[0].textContent.toLowerCase();
            const nombre = row.children[1].textContent.toLowerCase();
            if (sku.includes(query) || nombre.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    };

    // Cargar el reporte al iniciar
    fetchAndRenderReport();
});