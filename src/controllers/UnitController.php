<?php

include_once __DIR__ ."/../config/DatabaseConnection.php";
include __DIR__ ."/../models/Unit.php";

class UnitController {
    private $connection;

    public function __construct() {
        $db = new DatabaseConnection();
        $this->connection = $db->getConnection();
    }

    public function getAllUnits() {
        $sql = "SELECT * FROM UNITS";
        $stmt = $this->connection->prepare($sql);

        if( $stmt->execute() ) {
            
            $result = $stmt->get_result();
            $units = [];
            while ($row = $result->fetch_assoc()) {
                $units[] = new Unit($row['ID'], $row['TYPE']);
            }
            
            return $units;
            
        }
        return null;
    }

    public function getUnitById($id) {
        $sql = "SELECT * FROM UNITS WHERE ID = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            return new Unit($row['ID'], $row['TYPE'], $row['DESCRIPTION']);
        }

        return null;
    }

    public function createUnit($unitName, $unitDescription) {
        
        $sql = "INSERT INTO UNITS (TYPE, DESCRIPTION) VALUES (?, ?)";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("ss", $unitName, $unitDescription);
        if( $stmt->execute() ) {
        
            return true;
        }
        return false;
    }

    public function updateUnit($unitId, $unitName, $unitDescription) {
        $sql = "UPDATE UNITS SET TYPE = ? , DESCRIPTION = ? WHERE ID = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("ssi", $unitName, $unitDescription, $unitId);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function deleteUnit($id) {
        // Bắt đầu transaction
        $this->connection->begin_transaction();
    
        try {
            // Cập nhật các bảng liên quan, đặt UNITID = NULL
            $tablesToUpdate = ['PRODUCTS', 'RECIPEDETAILS', 'INGREDIENTS', 'IMPORTDETAILS'];
            foreach ($tablesToUpdate as $table) {
                $sql = "UPDATE $table SET UNITID = NULL WHERE UNITID = ?";
                $stmt = $this->connection->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
    
            // Xóa khỏi bảng UNITS
            $deleteSql = "DELETE FROM UNITS WHERE ID = ?";
            $deleteStmt = $this->connection->prepare($deleteSql);
            $deleteStmt->bind_param("i", $id);
            $deleteStmt->execute();
    
            // Commit nếu mọi thứ thành công
            $this->connection->commit();
            return true;
    
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $this->connection->rollback();
            error_log("Error deleting unit: " . $e->getMessage());
            return false;
        }
    }
    
}

?>
