<?php
// models/Proveedor.php

class Proveedor {
    private $conn;
    private $table = 'Proveedores';

    public $id_proveedor;
    public $nombre_proveedor;
    public $contacto_proveedor;
    public $telefono_proveedor;
    public $email_proveedor;
    public $direccion_proveedor;
    public $fecha_registro;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Crea un nuevo proveedor en la base de datos.
     * @return bool True si el proveedor fue creado con éxito, false en caso contrario.
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . " (nombre_proveedor, contacto_proveedor, telefono_proveedor, email_proveedor, direccion_proveedor)
                  VALUES (:nombre_proveedor, :contacto_proveedor, :telefono_proveedor, :email_proveedor, :direccion_proveedor)";
        $stmt = $this->conn->prepare($query);

        // Sanea los datos
        $this->nombre_proveedor = htmlspecialchars(strip_tags($this->nombre_proveedor));
        $this->contacto_proveedor = htmlspecialchars(strip_tags($this->contacto_proveedor));
        $this->telefono_proveedor = htmlspecialchars(strip_tags($this->telefono_proveedor));
        $this->email_proveedor = htmlspecialchars(strip_tags($this->email_proveedor));
        $this->direccion_proveedor = htmlspecialchars(strip_tags($this->direccion_proveedor));

        // Vincula los parámetros
        $stmt->bindParam(':nombre_proveedor', $this->nombre_proveedor);
        $stmt->bindParam(':contacto_proveedor', $this->contacto_proveedor);
        $stmt->bindParam(':telefono_proveedor', $this->telefono_proveedor);
        $stmt->bindParam(':email_proveedor', $this->email_proveedor);
        $stmt->bindParam(':direccion_proveedor', $this->direccion_proveedor);

        if ($stmt->execute()) {
            $this->id_proveedor = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     * Obtiene un proveedor por su ID.
     * @param int $id_proveedor El ID del proveedor.
     * @return array|false Un array asociativo con la información del proveedor si se encuentra, o false si no.
     */
    public function getById($id_proveedor) {
        $query = "SELECT * FROM " . $this->table . " WHERE id_proveedor = :id_proveedor LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_proveedor', $id_proveedor);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista todos los proveedores existentes.
     * @return PDOStatement La sentencia PDO ejecutada con los resultados.
     */
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY nombre_proveedor ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}

?>