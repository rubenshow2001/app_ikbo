<?php
// controllers/InventarioController.php

class InventarioController {
    private $db;
    private $productoModel;
    private $loteModel;
    private $movimientoModel;
    private $stockModel;
	private $proveedorModel;

    public function __construct($db) {
        $this->db = $db;
        $this->productoModel = new Producto($db);
        $this->loteModel = new Lote($db);
        $this->movimientoModel = new Movimiento($db);
        $this->stockModel = new Stock($db);
		$this->proveedorModel = new Proveedor($db);
		
    }

    /**
     * Realiza una entrada de inventario para un producto y lote específicos.
     *
     * Requiere:
     * - sku: SKU del producto
     * - numero_lote: Número de lote
     * - cantidad: Cantidad a ingresar (entero > 0)
     * - fecha_vencimiento (opcional): Fecha de vencimiento (YYYY-MM-DD)
     * - id_proveedor (opcional): ID del proveedor
     * - ubicacion_almacen (opcional): Ubicación en el almacén
     * - costo_compra_unitario (opcional): Costo unitario de compra
     */
    public function realizarEntrada() {
        $data = json_decode(file_get_contents("php://input"));

        // Validaciones básicas
        if (empty($data->sku) || empty($data->numero_lote) || !isset($data->cantidad) || !is_numeric($data->cantidad) || $data->cantidad <= 0) {
            Response::error('Parámetros SKU, número de lote y cantidad (entero positivo) son requeridos para la entrada.', 400);
        }

        $this->db->beginTransaction();
        try {
            // 1. Obtener ID del producto o crearlo si no existe (opcional, aquí solo lo obtiene)
            $this->productoModel->getBySku($data->sku);
            if (!$this->productoModel->id_producto) {
                // Aquí podrías añadir lógica para crear el producto si no existe
                // Por ahora, asumimos que el producto ya existe.
                Response::error('Producto con SKU ' . $data->sku . ' no encontrado.', 404);
            }
            $id_producto = $this->productoModel->id_producto;

            // 2. Crear el nuevo lote
            $this->loteModel->id_producto = $id_producto;
            $this->loteModel->id_proveedor = $data->id_proveedor ?? null; // Asume que id_proveedor es opcional
            $this->loteModel->numero_lote = $data->numero_lote;
            $this->loteModel->cantidad_ingresada = $data->cantidad;
            $this->loteModel->fecha_entrada = date('Y-m-d'); // Fecha actual
            $this->loteModel->fecha_vencimiento = $data->fecha_vencimiento ?? null; // Opcional
            $this->loteModel->ubicacion_almacen = $data->ubicacion_almacen ?? null;
            $this->loteModel->costo_compra_unitario = $data->costo_compra_unitario ?? null;

            if (!$this->loteModel->create()) {
                throw new Exception("No se pudo crear el lote.");
            }
            $id_lote = $this->loteModel->id_lote;

            // 3. Registrar el movimiento de entrada
            $this->movimientoModel->id_lote = $id_lote;
            $this->movimientoModel->tipo_movimiento = 'entrada';
            $this->movimientoModel->cantidad_movida = $data->cantidad;
            $this->movimientoModel->razon_movimiento = 'Entrada de inventario por compra/producción';

            if (!$this->movimientoModel->create()) {
                throw new Exception("No se pudo registrar el movimiento de entrada.");
            }

            // 4. Actualizar el stock actual del lote
            $current_stock = $this->stockModel->getStockByLote($id_lote);
            $new_stock = $current_stock + $data->cantidad;

            if (!$this->stockModel->updateStock($id_lote, $new_stock)) {
                throw new Exception("No se pudo actualizar el stock del lote.");
            }

            $this->db->commit();
            Response::success('Entrada de inventario realizada con éxito.', [
                'id_producto' => $id_producto,
                'id_lote' => $id_lote,
                'cantidad_ingresada' => $data->cantidad,
                'stock_actual_lote' => $new_stock
            ], 201);

        } catch (Exception $e) {
            $this->db->rollBack();
            Response::error('Error al realizar la entrada de inventario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Realiza una salida de inventario, controlando el stock por lote (FEFO/FIFO).
     *
     * Requiere:
     * - sku: SKU del producto
     * - cantidad: Cantidad a retirar (entero > 0)
     * - razon_movimiento (opcional): Razón de la salida (ej. "Venta", "Merma")
     */
    public function realizarSalida() {
        $data = json_decode(file_get_contents("php://input"));

        // Validaciones básicas
        if (empty($data->sku) || !isset($data->cantidad) || !is_numeric($data->cantidad) || $data->cantidad <= 0) {
            Response::error('Parámetros SKU y cantidad (entero positivo) son requeridos para la salida.', 400);
        }

        $cantidad_requerida = $data->cantidad;

        $this->db->beginTransaction();
        try {
            // 1. Obtener el ID del producto
            $this->productoModel->getBySku($data->sku);
            if (!$this->productoModel->id_producto) {
                Response::error('Producto con SKU ' . $data->sku . ' no encontrado.', 404);
            }
            $id_producto = $this->productoModel->id_producto;

            // 2. Obtener lotes disponibles ordenados por FEFO/FIFO
            $stmt_lotes = $this->loteModel->getAvailableLotesByProduct($id_producto);
            $lotes_disponibles = $stmt_lotes->fetchAll(PDO::FETCH_ASSOC);

            if (empty($lotes_disponibles)) {
                throw new Exception("No hay stock disponible para el producto " . $data->sku);
            }

            $stock_total_producto = 0;
            foreach ($lotes_disponibles as $lote) {
                $stock_total_producto += $lote['cantidad_actual'];
            }

            if ($stock_total_producto < $cantidad_requerida) {
                throw new Exception("Stock insuficiente para el producto " . $data->sku . ". Disponible: " . $stock_total_producto . ", Requerido: " . $cantidad_requerida);
            }

            $cantidad_procesada = 0;
            $movimientos_realizados = [];

            foreach ($lotes_disponibles as $lote) {
                if ($cantidad_procesada >= $cantidad_requerida) {
                    break; // Ya hemos cubierto la cantidad requerida
                }

                $id_lote = $lote['id_lote'];
                $stock_actual_lote = $lote['cantidad_actual'];
                $cantidad_a_retirar_de_lote = min($cantidad_requerida - $cantidad_procesada, $stock_actual_lote);

                if ($cantidad_a_retirar_de_lote > 0) {
                    // 3. Registrar el movimiento de salida para este lote
                    $this->movimientoModel->id_lote = $id_lote;
                    $this->movimientoModel->tipo_movimiento = 'salida';
                    $this->movimientoModel->cantidad_movida = $cantidad_a_retirar_de_lote;
                    $this->movimientoModel->razon_movimiento = $data->razon_movimiento ?? 'Salida de inventario';

                    if (!$this->movimientoModel->create()) {
                        throw new Exception("No se pudo registrar el movimiento de salida para el lote " . $id_lote);
                    }

                    // 4. Actualizar el stock actual del lote
                    $new_stock_lote = $stock_actual_lote - $cantidad_a_retirar_de_lote;
                    if (!$this->stockModel->updateStock($id_lote, $new_stock_lote)) {
                        throw new Exception("No se pudo actualizar el stock del lote " . $id_lote);
                    }

                    $cantidad_procesada += $cantidad_a_retirar_de_lote;
                    $movimientos_realizados[] = [
                        'id_lote' => $id_lote,
                        'cantidad_retirada' => $cantidad_a_retirar_de_lote,
                        'stock_actual_lote' => $new_stock_lote
                    ];
                }
            }

            $this->db->commit();
            Response::success('Salida de inventario realizada con éxito.', [
                'id_producto' => $id_producto,
                'cantidad_requerida' => $cantidad_requerida,
                'cantidad_procesada' => $cantidad_procesada,
                'detalles_lotes_afectados' => $movimientos_realizados
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            Response::error('Error al realizar la salida de inventario: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lista todos los productos existentes en el inventario con su stock total.
     */
    public function listarProductos() {
        try {
            $stmt = $this->productoModel->getAll();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($productos) {
                Response::success('Listado de productos.', $productos);
            } else {
                Response::success('No hay productos registrados en el inventario.', []);
            }
        } catch (Exception $e) {
            Response::error('Error al listar productos: ' . $e->getMessage(), 500);
        }
    }
	
	
	
	
	
	
/**
     * Crea un nuevo producto en el inventario.
     * Espera un JSON en el cuerpo de la solicitud (POST).
     *
     * Requiere:
     * - nombre_producto: Nombre del producto (string)
     * - sku: SKU único del producto (string)
     * - precio_unitario: Precio unitario (decimal, mayor que 0)
     * - unidad_medida (opcional): Unidad de medida (string)
     * - descripcion_producto (opcional): Descripción (string)
     * - stock_minimo (opcional): Nivel de stock mínimo (entero, >= 0)
     */
    public function crearProducto() {
        $data = json_decode(file_get_contents("php://input"));

        // Validaciones
        if (empty($data->nombre_producto) || empty($data->sku) || !isset($data->precio_unitario) || !is_numeric($data->precio_unitario) || $data->precio_unitario <= 0) {
            Response::error('Parámetros requeridos faltantes o inválidos: nombre_producto, sku, precio_unitario (número positivo).', 400);
        }

        // Validar si el SKU ya existe
        if ($this->productoModel->getBySku($data->sku)) {
            Response::error('El SKU "' . $data->sku . '" ya existe para otro producto.', 409); // 409 Conflict
        }

        $this->productoModel->nombre_producto = $data->nombre_producto;
        $this->productoModel->descripcion_producto = $data->descripcion_producto ?? null;
        $this->productoModel->sku = $data->sku;
        $this->productoModel->precio_unitario = $data->precio_unitario;
        $this->productoModel->unidad_medida = $data->unidad_medida ?? null;
        $this->productoModel->stock_minimo = $data->stock_minimo ?? 0; // Por defecto 0 si no se envía

        try {
            if ($this->productoModel->create()) {
                Response::success('Producto creado con éxito.', [
                    'id_producto' => $this->productoModel->id_producto,
                    'nombre_producto' => $this->productoModel->nombre_producto,
                    'sku' => $this->productoModel->sku
                ], 201);
            } else {
                throw new Exception("No se pudo crear el producto.");
            }
        } catch (Exception $e) {
            Response::error('Error al crear el producto: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lista todos los proveedores existentes.
     * No requiere parámetros, se accede vía GET.
     */
    public function listarProveedores() {
        try {
            $stmt = $this->proveedorModel->getAll();
            $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($proveedores) {
                Response::success('Listado de proveedores.', $proveedores);
            } else {
                Response::success('No hay proveedores registrados.', []);
            }
        } catch (Exception $e) {
            Response::error('Error al listar proveedores: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Crea un nuevo proveedor.
     * Espera un JSON en el cuerpo de la solicitud (POST).
     *
     * Requiere:
     * - nombre_proveedor: Nombre del proveedor (string)
     * - contacto_proveedor (opcional): Nombre de contacto (string)
     * - telefono_proveedor (opcional): Teléfono (string)
     * - email_proveedor (opcional): Correo electrónico (string, debe ser único)
     * - direccion_proveedor (opcional): Dirección (string)
     */
    public function crearProveedor() {
        $data = json_decode(file_get_contents("php://input"));

        // Validaciones
        if (empty($data->nombre_proveedor)) {
            Response::error('El nombre_proveedor es un parámetro requerido.', 400);
        }

        // Opcional: Validar si el email ya existe si se proporciona
        if (!empty($data->email_proveedor)) {
            // Implementa lógica para verificar si el email ya existe en la DB
            // Por ejemplo, añadiendo un método `getByEmail` en Proveedor.php
            // $existingProvider = $this->proveedorModel->getByEmail($data->email_proveedor);
            // if ($existingProvider) {
            //     Response::error('El correo electrónico ya está registrado para otro proveedor.', 409);
            // }
        }

        $this->proveedorModel->nombre_proveedor = $data->nombre_proveedor;
        $this->proveedorModel->contacto_proveedor = $data->contacto_proveedor ?? null;
        $this->proveedorModel->telefono_proveedor = $data->telefono_proveedor ?? null;
        $this->proveedorModel->email_proveedor = $data->email_proveedor ?? null;
        $this->proveedorModel->direccion_proveedor = $data->direccion_proveedor ?? null;

        try {
            if ($this->proveedorModel->create()) {
                Response::success('Proveedor creado con éxito.', [
                    'id_proveedor' => $this->proveedorModel->id_proveedor,
                    'nombre_proveedor' => $this->proveedorModel->nombre_proveedor
                ], 201);
            } else {
                throw new Exception("No se pudo crear el proveedor.");
            }
        } catch (Exception $e) {
            Response::error('Error al crear el proveedor: ' . $e->getMessage(), 500);
        }
    }
	
	
    public function listarMovimientos() {
        try {
            $stmt = $this->movimientoModel->getAll();
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($movimientos) {
                Response::success('Listado de movimientos.', $movimientos);
            } else {
                Response::success('No hay movimientos registrados.', []);
            }
        } catch (Exception $e) {
            Response::error('Error al listar movimientos: ' . $e->getMessage(), 500);
        }
    }
	
	
    public function StockInventario() {
        try {
            $stmt = $this->stockModel->getStockInventario();
            $stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($stock) {
                Response::success('Stock inventario.', $stock);
            } else {
                Response::success('No hay registrados.', []);
            }
        } catch (Exception $e) {
            Response::error('Error al listar inventario: ' . $e->getMessage(), 500);
        }
    }
	
    public function StockInventarioXLote() {
        try {
            $stmt = $this->stockModel->getStockInventarioXLote();
            $stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($stock) {
                Response::success('Stock inventario.', $stock);
            } else {
                Response::success('No hay registrados.', []);
            }
        } catch (Exception $e) {
            Response::error('Error al listar inventario: ' . $e->getMessage(), 500);
        }
    }
	
}
?>