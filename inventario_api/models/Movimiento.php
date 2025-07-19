<?php
// models/Movimiento.php

class Movimiento {
    private $conn;
    private $table = 'Movimientos_Inventario';

    public $id_movimiento;
    public $id_lote;
    public $tipo_movimiento;
    public $cantidad_movida;
    public $fecha_movimiento;
    public $razon_movimiento;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Registrar un nuevo movimiento
    public function create() {
        $query = "INSERT INTO " . $this->table . " (id_lote, tipo_movimiento, cantidad_movida, razon_movimiento) VALUES (:id_lote, :tipo_movimiento, :cantidad_movida, :razon_movimiento)";
        $stmt = $this->conn->prepare($query);

        $this->razon_movimiento = htmlspecialchars(strip_tags($this->razon_movimiento));

        $stmt->bindParam(':id_lote', $this->id_lote);
        $stmt->bindParam(':tipo_movimiento', $this->tipo_movimiento);
        $stmt->bindParam(':cantidad_movida', $this->cantidad_movida);
        $stmt->bindParam(':razon_movimiento', $this->razon_movimiento);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
	
	
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY fecha_movimiento ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
	
}

?>