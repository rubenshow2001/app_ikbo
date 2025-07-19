# app_ikbo
Control de inventario

# Gestión de Inventario (HTML, JavaScript, CSS puro)

📦 **Visión General del Proyecto**

Este proyecto es una aplicación web básica para la **Gestión de Inventario de Productos**, desarrollada usando HTML, JavaScript y CSS. Su propósito es servir como una prueba preliminar o un prototipo rápido para interactuar con una API REST backend y realizar operaciones CRUD (Crear, Leer, Actualizar, Eliminar) en los datos de productos.

-----

✨ **Características Principales**

  * **Interfaz de Administración de Productos:** Permite la gestión básica de la información de los productos.
  * **Formulario de CRUD:** Habilita la creación y actualización de productos.
  * **Tabla de Listado:** Muestra los productos existentes con opciones para editar y eliminar.
  * **Comunicación con API REST:** Realiza peticiones `fetch` directamente a un servidor backend para todas las operaciones de datos.
  * **Estilos con Bootstrap 5:** Utiliza la CDN de Bootstrap para un diseño responsivo y predefinido.
  * **Iconos Font Awesome:** Incorpora iconos para mejorar la interfaz de usuario.
  * **Alertas Dinámicas:** Notificaciones simples para el usuario sobre el estado de las operaciones.

-----

🚀 **Cómo Empezar**

Sigue estos pasos para poner la aplicación en marcha en tu entorno local.

### Prerrequisitos

  * Un navegador web moderno (Chrome, Firefox, Edge, Safari).
  * **Base de Datos MySQL**.
  * Un servidor backend para la API de inventario. Este proyecto asume que ya tienes una API REST funcional y accesible, preferiblemente en `http://localhost/inventario_api/public/`.
      * **Importante:** Asegúrate de que tu servidor web (ej. Apache, Nginx) esté configurado para servir los archivos HTML, CSS y JS directamente, y que tu API tenga las cabeceras **CORS (Cross-Origin Resource Sharing)** configuradas correctamente para permitir solicitudes desde el origen donde sirves tu frontend (ej. `file://` para abrir directamente el HTML, o `http://localhost:port` si usas un servidor local para el frontend).

### Instalación

Solo necesitas los archivos del proyecto.

1.  **Clona el repositorio o descarga los archivos:**

    ```bash
    git clone https://github.com/tu-usuario/nombre-de-tu-repositorio.git
    cd nombre-de-tu-repositorio
    ```

    (Reemplaza `tu-usuario/nombre-de-tu-repositorio` con la URL real de tu proyecto en GitHub, o simplemente descarga el ZIP).

2.  **Verifica la estructura de archivos:**
    Asegúrate de tener los siguientes archivos en la carpeta raíz del proyecto:

    ```
    mi-proyecto-inventario-simple/
    ├── index.html
    ├── app.js
    └── (otros_html_si_existen.html)
    ```

### Configuración de la API

Abre el archivo `app.js` y verifica que la constante `API_BASE_URL` apunte a la ubicación correcta de tu API backend:

```javascript
// app.js
const API_BASE_URL = 'http://localhost/inventario_api/public/'; // <-- Verifica y ajusta si es necesario
```

### Ejecutar la Aplicación

1.  Simplemente abre el archivo `app_ikbo/index.html` en tu navegador web. Puedes hacerlo haciendo doble clic en el archivo o arrastrándolo a la ventana del navegador.
2.  Asegúrate que:
      * Tu API backend esté corriendo antes de abrir el `index.html`.
      * La estructura de la base de datos esté creada.
      * Los datos de conexión a la base de datos estén correctos en: `inventario_api/config/database.php`.

-----

📂 **Estructura del Proyecto**

La estructura del proyecto es muy simple y directa:

```
mi-proyecto-inventario-simple/
├── app_ikbo/
│   ├── app.js
│   ├── app--inventario.js
│   ├── app--inventariolot.js
│   ├── app--movimientos.js
│   ├── app--proveedores.js
│   ├── index.html
│   ├── Inventario.html
│   ├── Inventariolot.html
│   ├── movimientos.html
│   └── proveedores.html


├── inventario--api/
    ├── config/
    │   └── database.php
    ├── controllers/
    │   └── InventarioController.php
    ├── models/
    │   ├── Lote.php
    │   ├── Movimiento.php
    │   ├── Producto.php
    │   ├── Proveedor.php
    │   └── Stock.php
    ├── public/
    │   ├── .htaccess
    │   ├── index.php
    │   └── test.php
    └── utils/
        ├── Database.php
        └── Response.php
```

----
