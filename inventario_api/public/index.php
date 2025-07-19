<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

// public/index.php

// Incluir configuraciones y clases
require_once __DIR__ . '/../models/Proveedor.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Database.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Lote.php';
require_once __DIR__ . '/../models/Movimiento.php';
require_once __DIR__ . '/../models/Stock.php';
require_once __DIR__ . '/../controllers/InventarioController.php';

// Obtener la conexión a la base de datos
$database = Database::getInstance();
$db = $database->getConnection();

// Inicializar el controlador
$inventarioController = new InventarioController($db);

// Obtener el método de la solicitud y la ruta
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Eliminar el prefijo de la ruta si la API está en un subdirectorio
// Ej: /inventario_api/productos -> /productos
$base_path = '/inventario_api/public'; // Ajusta esto si tu API está en otro subdirectorio
if (strpos($request_uri, $base_path) === 0) {
    $request_uri = substr($request_uri, strlen($base_path));
}


// Router simple
switch ($request_uri) {
    case '/inventario/entrada':
        if ($method === 'POST') {
            $inventarioController->realizarEntrada();
        } else {
            Response::error('Método no permitido para esta ruta.', 405);
        }
        break;

    case '/inventario/salida':
        if ($method === 'POST') {
            $inventarioController->realizarSalida();
        } else {
            Response::error('Método no permitido para esta ruta.', 405);
        }
        break;

    case '/inventario/productos':
        if ($method === 'GET') {
            $inventarioController->listarProductos();
        } else {
            Response::error('Método no permitido para esta ruta.', 405);
        }
        break;


    case '/inventario/producto': // Para crear un solo producto
        if ($method === 'POST') {
            $inventarioController->crearProducto();
        } else {
            Response::error('Método no permitido. Use POST para esta ruta.', 405);
        }
        break;

    case '/inventario/proveedores': // Para listar proveedores
        if ($method === 'GET') {
            $inventarioController->listarProveedores();
        } else {
            Response::error('Método no permitido. Use GET para esta ruta.', 405);
        }
        break;

    case '/inventario/proveedor': // Para crear un solo proveedor
        if ($method === 'POST') {
            $inventarioController->crearProveedor();
        } else {
            Response::error('Método no permitido. Use POST para esta ruta.', 405);
        }
        break;

    case '/inventario/movimientos': // Para listar movimientos
        if ($method === 'GET') {
            $inventarioController->listarMovimientos();
        } else {
            Response::error('Método no permitido. Use GET para esta ruta.', 405);
        }
        break;
		
    case '/inventario/inventario': // Para listar movimientos
        if ($method === 'GET') {
            $inventarioController->StockInventario();
        } else {
            Response::error('Método no permitido. Use GET para esta ruta.', 405);
        }
        break;
		
    case '/inventario/inventarioxlote': // Para listar movimientos
        if ($method === 'GET') {
            $inventarioController->StockInventarioXLote();
        } else {
            Response::error('Método no permitido. Use GET para esta ruta.', 405);
        }
        break;


    default:
        Response::error('Ruta no encontrada. '.$request_uri, 404);
        break;
}