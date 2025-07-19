<?php
// models/Producto.php

class Producto {
    private $conn;
    private $table = 'Productos';

    public $id_producto;
    public $nombre_producto;
    public $descripcion_producto;
    public $sku;
    public $precio_unitario;
    public $unidad_medida;
    public $stock_minimo;
    public $fecha_creacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener un producto por SKU
    public function getBySku($sku) {
        $query = "SELECT * FROM " . $this->table . " WHERE sku = :sku LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sku', $sku);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id_producto = $row['id_producto'];
            $this->nombre_producto = $row['nombre_producto'];
            $this->descripcion_producto = $row['descripcion_producto'];
            $this->sku = $row['sku'];
            $this->precio_unitario = $row['precio_unitario'];
            $this->unidad_medida = $row['unidad_medida'];
            $this->stock_minimo = $row['stock_minimo'];
            $this->fecha_creacion = $row['fecha_creacion'];
            return true;
        }
        return false;
    }

    // Listar todos los productos
    public function getAll() {
        $query = "SELECT p.*, SUM(sa.cantidad_actual) as stock_total_general
                  FROM " . $this->table . " p
                  LEFT JOIN Lotes l ON p.id_producto = l.id_producto
                  LEFT JOIN Stock_Actual sa ON l.id_lote = sa.id_lote
                  GROUP BY p.id_producto
                  ORDER BY p.nombre_producto ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Crear un nuevo producto (útil si la API lo permite)
    public function create() {
        $query = "INSERT INTO " . $this->table . " (nombre_producto, descripcion_producto, sku, precio_unitario, unidad_medida, stock_minimo) VALUES (:nombre_producto, :descripcion_producto, :sku, :precio_unitario, :unidad_medida, :stock_minimo)";
        $stmt = $this->conn->prepare($query);

        $this->nombre_producto = htmlspecialchars(strip_tags($this->nombre_producto));
        $this->descripcion_producto = htmlspecialchars(strip_tags($this->descripcion_producto));
        $this->sku = htmlspecialchars(strip_tags($this->sku));
        $this->unidad_medida = htmlspecialchars(strip_tags($this->unidad_medida));

        $stmt->bindParam(':nombre_producto', $this->nombre_producto);
        $stmt->bindParam(':descripcion_producto', $this->descripcion_producto);
        $stmt->bindParam(':sku', $this->sku);
        $stmt->bindParam(':precio_unitario', $this->precio_unitario);
        $stmt->bindParam(':unidad_medida', $this->unidad_medida);
        $stmt->bindParam(':stock_minimo', $this->stock_minimo);

        if ($stmt->execute()) {
            $this->id_producto = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
}