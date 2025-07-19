<?php
// models/Stock.php

class Stock {
    private $conn;
    private $table = 'Stock_Actual';

    public $id_stock;
    public $id_lote;
    public $cantidad_actual;
    public $fecha_ultima_actualizacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener stock actual de un lote
    public function getStockByLote($id_lote) {
        $query = "SELECT cantidad_actual FROM " . $this->table . " WHERE id_lote = :id_lote LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_lote', $id_lote);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['cantidad_actual'] : 0;
    }

    public function getStockInventario() {
        $query = "	SELECT
					p.sku AS SKU,
					p.nombre_producto AS NombreProducto,
					p.unidad_medida AS Unidad,
					p.stock_minimo AS StockMinimo,
					-- Calcula el stock total actual sumando las cantidades de todos los lotes.
					COALESCE(SUM(sa.cantidad_actual), 0) AS StockActual,
					-- Cuenta y suma la cantidad de productos de lotes cuya fecha de vencimiento ya pasó.
					COALESCE(
						SUM(CASE
							WHEN l.fecha_vencimiento IS NOT NULL AND l.fecha_vencimiento < CURRENT_DATE() THEN sa.cantidad_actual
							ELSE 0
						END), 0
					) AS CantidadVencida,
					-- Evalúa el estado del inventario, priorizando el stock vencido.
					CASE
						WHEN COALESCE(SUM(CASE WHEN l.fecha_vencimiento IS NOT NULL AND l.fecha_vencimiento < CURRENT_DATE() THEN sa.cantidad_actual ELSE 0 END), 0) > 0 THEN 'Con Stock Vencido'
						WHEN COALESCE(SUM(sa.cantidad_actual), 0) <= 0 THEN 'Sin Stock'
						WHEN COALESCE(SUM(sa.cantidad_actual), 0) <= p.stock_minimo THEN 'Bajo Stock'
						ELSE 'OK'
					END AS EstadoInventario
				FROM
					Productos AS p
				LEFT JOIN Lotes AS l ON p.id_producto = l.id_producto
				LEFT JOIN Stock_Actual AS sa ON l.id_lote = sa.id_lote
				GROUP BY p.id_producto, p.nombre_producto, p.sku, p.unidad_medida, p.stock_minimo
				HAVING COALESCE(SUM(sa.cantidad_actual), 0) > 0
				ORDER BY
					EstadoInventario DESC,
					NombreProducto;";
    
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }



    public function getStockInventarioXLote() {
        $query = "	SELECT
					p.sku AS SKU,
					p.nombre_producto AS NombreProducto,
					p.unidad_medida AS Unidad,
                    l.numero_lote as Lote,
					p.stock_minimo AS StockMinimo,
					-- Calcula el stock total actual sumando las cantidades de todos los lotes.
					COALESCE(SUM(sa.cantidad_actual), 0) AS StockActual,
					-- Cuenta y suma la cantidad de productos de lotes cuya fecha de vencimiento ya pasó.
					COALESCE(
						SUM(CASE
							WHEN l.fecha_vencimiento IS NOT NULL AND l.fecha_vencimiento < CURRENT_DATE() THEN sa.cantidad_actual
							ELSE 0
						END), 0
					) AS CantidadVencida,
					-- Evalúa el estado del inventario, priorizando el stock vencido.
					CASE
						WHEN COALESCE(SUM(CASE WHEN l.fecha_vencimiento IS NOT NULL AND l.fecha_vencimiento < CURRENT_DATE() THEN sa.cantidad_actual ELSE 0 END), 0) > 0 THEN 'Con Stock Vencido'
						WHEN COALESCE(SUM(sa.cantidad_actual), 0) <= 0 THEN 'Sin Stock'
						WHEN COALESCE(SUM(sa.cantidad_actual), 0) <= p.stock_minimo THEN 'Bajo Stock'
						ELSE 'OK'
					END AS EstadoInventario
				FROM
					Productos AS p
				LEFT JOIN Lotes AS l ON p.id_producto = l.id_producto
				LEFT JOIN Stock_Actual AS sa ON l.id_lote = sa.id_lote
				GROUP BY p.id_producto, p.nombre_producto, p.sku, p.unidad_medida, p.stock_minimo, l.numero_lote
				HAVING COALESCE(SUM(sa.cantidad_actual), 0) > 0
				ORDER BY
					EstadoInventario DESC,
					NombreProducto;";
    
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }


    // Actualizar o insertar stock de un lote
    public function updateStock($id_lote, $cantidad) {
        // Primero, verifica si ya existe una entrada para este lote
        $queryCheck = "SELECT id_stock FROM " . $this->table . " WHERE id_lote = :id_lote LIMIT 0,1";
        $stmtCheck = $this->conn->prepare($queryCheck);
        $stmtCheck->bindParam(':id_lote', $id_lote);
        $stmtCheck->execute();
        $exists = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            // Si existe, actualiza
            $query = "UPDATE " . $this->table . " SET cantidad_actual = :cantidad_actual WHERE id_lote = :id_lote";
        } else {
            // Si no existe, inserta
            $query = "INSERT INTO " . $this->table . " (id_lote, cantidad_actual) VALUES (:id_lote, :cantidad_actual)";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_lote', $id_lote);
        $stmt->bindParam(':cantidad_actual', $cantidad);

        return $stmt->execute();
    }
}