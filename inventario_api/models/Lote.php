<?php
// models/Lote.php

class Lote {
    private $conn;
    private $table = 'Lotes';

    public $id_lote;
    public $id_producto;
    public $id_proveedor;
    public $numero_lote;
    public $cantidad_ingresada;
    public $fecha_entrada;
    public $fecha_vencimiento;
    public $ubicacion_almacen;
    public $costo_compra_unitario;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Crear un nuevo lote
    public function create() {
        $query = "INSERT INTO " . $this->table . " (id_producto, id_proveedor, numero_lote, cantidad_ingresada, fecha_entrada, fecha_vencimiento, ubicacion_almacen, costo_compra_unitario) VALUES (:id_producto, :id_proveedor, :numero_lote, :cantidad_ingresada, :fecha_entrada, :fecha_vencimiento, :ubicacion_almacen, :costo_compra_unitario)";
        $stmt = $this->conn->prepare($query);

        $this->numero_lote = htmlspecialchars(strip_tags($this->numero_lote));
        $this->ubicacion_almacen = htmlspecialchars(strip_tags($this->ubicacion_almacen));

        $stmt->bindParam(':id_producto', $this->id_producto);
        $stmt->bindParam(':id_proveedor', $this->id_proveedor);
        $stmt->bindParam(':numero_lote', $this->numero_lote);
        $stmt->bindParam(':cantidad_ingresada', $this->cantidad_ingresada);
        $stmt->bindParam(':fecha_entrada', $this->fecha_entrada);
        $stmt->bindParam(':fecha_vencimiento', $this->fecha_vencimiento);
        $stmt->bindParam(':ubicacion_almacen', $this->ubicacion_almacen);
        $stmt->bindParam(':costo_compra_unitario', $this->costo_compra_unitario);

        if ($stmt->execute()) {
            $this->id_lote = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Obtener lotes disponibles de un producto (para salidas FIFO/FEFO)
    public function getAvailableLotesByProduct($id_producto) {
        // Ordena por fecha de vencimiento (FEFO - First Expired, First Out)
        // Luego por fecha de entrada (FIFO - First In, First Out)
        $query = "SELECT l.*, sa.cantidad_actual FROM " . $this->table . " l
                  JOIN Stock_Actual sa ON l.id_lote = sa.id_lote
                  WHERE l.id_producto = :id_producto AND sa.cantidad_actual > 0
                  ORDER BY l.fecha_vencimiento ASC, l.fecha_entrada ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_producto', $id_producto);
        $stmt->execute();
        return $stmt;
    }

    // Obtener información de un lote específico
    public function getLoteById($id_lote) {
        $query = "SELECT l.*, p.nombre_producto, sa.cantidad_actual
                  FROM " . $this->table . " l
                  JOIN Productos p ON l.id_producto = p.id_producto
                  JOIN Stock_Actual sa ON l.id_lote = sa.id_lote
                  WHERE l.id_lote = :id_lote LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_lote', $id_lote);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}