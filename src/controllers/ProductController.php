<?php
require_once(__DIR__ . '/../config/DatabaseConnection.php');
require_once(__DIR__ . '/../models/Product.php');


class ProductController {
    private $db;
    private $conn;

    public function __construct() {
        // Khởi tạo kết nối CSDL từ lớp DatabaseConnection
        $this->db = new DatabaseConnection();
        $this->conn = $this->db->getConnection();
    }

    // Lấy danh sách tất cả sản phẩm
    public function getAllProducts() {
        $sql = "SELECT * FROM PRODUCTS";
        $result = $this->conn->query($sql);

        $products = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $product = new Product(
                    $row['ID'],
                    $row['RECIPEID'],
                    $row['PRODUCTNAME'],
                    $row['PRICE'],
                    $row['LINKIMAGE'] ?? null,
                    $row['UNITID'],
                    $row['CATEGORYID']
                );
                $products[] = $product;
            }
        }
        return $products;
    }

    // Lấy thông tin sản phẩm theo id
    public function getProductById($id) {
        $sql = "SELECT * FROM PRODUCTS WHERE ID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows == 1) {
            $row = $result->fetch_assoc();
            return new Product(
                $row['ID'],
                $row['RECIPEID'],
                $row['PRODUCTNAME'],
                $row['PRICE'],
                $row['LINKIMAGE'] ?? null,
                $row['UNITID'],
                $row['CATEGORYID']
            );
        }
        return null;
    }

    // Thêm sản phẩm mới vào CSDL
    public function createProduct($productName, $recipeId, $price, $linkImage, $unitId, $categoryId) {
        $sql = 'INSERT INTO PRODUCTS (RECIPEID, PRODUCTNAME, PRICE, LINKIMAGE, UNITID, CATEGORYID) VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isdsii", $recipeId, $productName, $price, $linkImage, $unitId, $categoryId);
        $result = $stmt->execute();
        if($result) {
            $productId = $this->conn->insert_id;
            $stmt->close();
            return $productId;
        } else {
        
            $stmt->close();
            return false;
        }
        
    }

    // Cập nhật thông tin sản phẩm
    public function updateProduct($recipeId, $productName, $price, $linkImage, $unitId, $categoryId, $id) {
        $sql = "UPDATE PRODUCTS SET RECIPEID = ?, PRODUCTNAME = ?, PRICE = ?, LINKIMAGE = ?, UNITID = ?, CATEGORYID = ? WHERE ID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isdsiii",$recipeId, $productName, $price, $linkImage, $unitId, $categoryId, $id);
        $result = $stmt->execute();
        if($result) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }

    // Xóa sản phẩm theo id (bao gồm xóa các dữ liệu liên quan trong các bảng khác)
    public function deleteProduct($id) {
        $this->conn->begin_transaction();
        try {
            // Xóa đánh giá sản phẩm trong bảng productreviews
            $stmt = $this->conn->prepare("DELETE FROM PRODUCTREVIEWS WHERE PRODUCTID = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
    
            // Xóa sản phẩm khỏi giỏ hàng trong bảng cartdetails
            $stmt = $this->conn->prepare("DELETE FROM CARTDETAILS WHERE PRODUCTID = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
    
            // Xóa sản phẩm khỏi đơn hàng trong bảng orderdetails
            $stmt = $this->conn->prepare("DELETE FROM ORDERDETAILS WHERE PRODUCTID = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
    
            // Cuối cùng, xóa sản phẩm khỏi bảng PRODUCTS
            $stmt = $this->conn->prepare("DELETE FROM PRODUCTS WHERE ID = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
    
            // Commit transaction nếu tất cả câu lệnh đều thực hiện thành công
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            // Nếu có lỗi, rollback transaction
            $this->conn->rollback();
            echo "Lỗi khi xóa sản phẩm: " . $e->getMessage();
            return false;
        }
    }

    // <-..->
    
    // Tìm sản phẩm theo từ khóa (tên + giá)
    public function searchProducts($keyword){
        $keyword = strtolower(trim(htmlspecialchars(strip_tags($keyword))));
        if(is_numeric($keyword)){
            $sql = "SELECT * FROM PRODUCTS WHERE LOWER(PRODUCTNAME) LIKE ? OR PRICE = ?";
            $stmt = $this->conn->prepare($sql);
            $like = "%$keyword%";
            $stmt->bind_param("sd", $like, $keyword);
        } else {
            $sql = "SELECT * FROM PRODUCTS WHERE LOWER(PRODUCTNAME) LIKE ?";
            $stmt = $this->conn->prepare($sql);
            $like = "%$keyword%";
            $stmt->bind_param("s",$like);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        while($row = $result->fetch_assoc()){
            $products[] = $row;
        }

        $stmt->close();
        return $products;
    }
    
    // Phân trang
    public function getPaginatedProducts($offset, $limit, $search = '', $categoryId = '') {
        $sql = "SELECT * FROM PRODUCTS WHERE 1";
        $params = [];
        $types = '';

        if (!empty($search)) {
            $sql .= " AND PRODUCTNAME LIKE ?";
            $params[] = "%" . $search . "%";
            $types .= 's';
        }

        if (!empty($categoryId)) {
            $sql .= " AND CATEGORYID = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }

        $sql .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $limit;
        $types .= 'ii';

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $result = $stmt->get_result();
        $products = [];

        while ($row = $result->fetch_assoc()) {
            $product = new Product(
                $row['ID'],
                $row['RECIPEID'],
                $row['PRODUCTNAME'],
                $row['PRICE'],
                $row['LINKIMAGE'] ?? null,
                $row['UNITID'],
                $row['CATEGORYID']
            );
            $products[] = $product;
        }

        $stmt->close();
        return $products;
    }
 

    public function getTotalPages($limit, $search = '', $categoryId = '') {
        $sql = "SELECT COUNT(*) as total FROM PRODUCTS WHERE 1";
        $params = [];
        $types = '';

        if (!empty($search)) {
            $sql .= " AND PRODUCTNAME LIKE ?";
            $params[] = "%" . $search . "%";
            $types .= 's';
        }

        if (!empty($categoryId)) {
            $sql .= " AND CATEGORYID = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return ceil($row['total'] / $limit);
    }

    public function getAllCategories() {
        require_once(__DIR__ . '/../models/Category.php');
        $sql = "SELECT * FROM CATEGORIES";
        $result = $this->conn->query($sql);

        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $category = new Category($row['ID'], $row['CATEGORYNAME']);
            $categories[] = $category;
        }
        return $categories;
    }

    // Lấy danh sách đánh giá theo ID
    public function getReviewsByProductId($productId) {
        $sql = "SELECT * FROM PRODUCTREVIEWS WHERE PRODUCTID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAverageRating($productId) {
        $sql = "SELECT AVG(RATING) as average FROM PRODUCTREVIEWS WHERE PRODUCTID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return round($result['average'] ?? 0, 1);
    }

    // Thêm sản phẩm vào giỏ hàng
    public function addToCart($productId, $quantity, $userId) {
        // Kiểm tra xem người dùng đã có giỏ hàng chưa
        $query = "SELECT * FROM CARTS WHERE USERID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            // Người dùng chưa có giỏ hàng, tạo giỏ hàng mới
            $insertCartQuery = "INSERT INTO CARTS (USERID, QUANTITY) VALUES (?, ?)";
            $stmt = $this->conn->prepare($insertCartQuery);
            $stmt->bind_param("ii", $userId, $quantity);
            $stmt->execute();
            $cartId = $stmt->insert_id; // Lấy ID của giỏ hàng mới tạo
        } else {
            // Người dùng đã có giỏ hàng, lấy ID của giỏ hàng
            $cart = $result->fetch_assoc();
            $cartId = $cart['ID'];
        }

        // Thêm sản phẩm vào CARTDETAILS
        $insertCartDetailsQuery = "INSERT INTO CARTDETAILS (CARTID, PRODUCTID, QUANTITY) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE QUANTITY = QUANTITY + ?";

        $stmt = $this->conn->prepare($insertCartDetailsQuery);
        $stmt->bind_param("iiii", $cartId, $productId, $quantity, $quantity);
        $stmt->execute();
    }
}
?>
