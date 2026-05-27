<?php
include_once __DIR__ ."/../config/DatabaseConnection.php";

require_once (dirname(__FILE__) ."/../models/Recipe.php");

class RecipeController {
    private $conn;

    public function __construct() {
        $db = new DatabaseConnection();
        $this->conn = $db->getConnection();
    }
    public function updateRecipe($recipeName,$recipeid) {
        $sql = "UPDATE RECIPES SET RECIPENAME = ? WHERE ID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $recipeName, $recipeid);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }

    public function createRecipe($recipeName) {
        $sql = "INSERT INTO RECIPES (RECIPENAME) VALUES (?)";
        $stmt = $this->conn->prepare($sql);
    
        if (!$stmt) {
            // Nếu không thể chuẩn bị câu lệnh, trả về null
            return null;
        }
    
        $stmt->bind_param("s", $recipeName);
    
        if ($stmt->execute()) {
            $recipeId = $this->conn->insert_id; // Lấy ID recipe vừa được thêm vào
            $stmt->close();
            return $recipeId;
        } else {
            $stmt->close();
            return null;
        }
    }

    public function deleteRecipeById($id): bool {
        // Bắt đầu transaction để đảm bảo toàn vẹn dữ liệu
        $this->conn->begin_transaction();
    
        try {
            // 1. Xóa chi tiết công thức
            $sql1 = "DELETE FROM RECIPEDETAILS WHERE RECIPEID = ?";
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->bind_param("i", $id);
            $stmt1->execute();
    
            // 2. Xóa sản phẩm liên quan
            $sql2 = "DELETE FROM PRODUCTS WHERE RECIPEID = ?";
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
    
            // 3. Xóa công thức chính
            $sql3 = "DELETE FROM RECIPES WHERE ID = ?";
            $stmt3 = $this->conn->prepare($sql3);
            $stmt3->bind_param("i", $id);
            $stmt3->execute();
    
            // Commit nếu không có lỗi
            $this->conn->commit();
            return true;
    
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $this->conn->rollback();
            return false;
        }
    }
    

    public function getRecipebyName($recipeName) {
        $sql = "SELECT * FROM RECIPES WHERE RECIPENAME = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $recipeName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return new Recipe($row['ID'], $row['RECIPENAME']);
        } else {
            return null;
        }
    }

    public function getAllRecipes(){
        $sql = 'SELECT * FROM RECIPES';
        $stmt = $this->conn->prepare($sql);
        if($stmt->execute()){
            $result = $stmt->get_result();
            $recipes = [];
            while($row = $result->fetch_assoc()){
                $recipes[] = new Recipe($row['ID'], $row['RECIPENAME']);
            }
            return $recipes;
        }
        return null;
    }

    
    public function getRecipebyId($id) {
        $sql = "SELECT * FROM RECIPES WHERE ID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return new Recipe($row['ID'], $row['RECIPENAME']);
        } else {
            return null;
        }
    }

    // Thêm các phương thức khác như updateRecipe, deleteRecipe nếu cần
}

?>
