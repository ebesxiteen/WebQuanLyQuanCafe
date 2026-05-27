<?php

require_once __DIR__ . '/../config/DatabaseConnection.php';
require_once __DIR__ . '/../models/Cart.php';

class CartController
{
    private mysqli $conn;

    public function __construct()
    {
        $db = new DatabaseConnection();
        $this->conn = $db->getConnection();
    }

    public function createCart($userId, $quantity)
    {
        $sql = "INSERT INTO CARTS (USERID, QUANTITY) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("id", $userId, $quantity);

        if (!$stmt->execute()) {
            return null;
        }

        return new Cart($this->conn->insert_id, $userId, $quantity);
    }

    public function getCart($id)
    {
        $sql = "SELECT * FROM CARTS WHERE ID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();

        return new Cart($row['ID'], $row['USERID'], $row['QUANTITY']);
    }

    public function getCartByUser($userId)
    {
        $sql = "SELECT * FROM CARTS WHERE USERID = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();

        return new Cart($row['ID'], $row['USERID'], $row['QUANTITY']);
    }

    public function getAllCarts()
    {
        $sql = "SELECT * FROM CARTS";
        $result = $this->conn->query($sql);
        $carts = [];

        while ($row = $result->fetch_assoc()) {
            $carts[] = new Cart($row['ID'], $row['USERID'], $row['QUANTITY']);
        }

        return $carts;
    }

    public function updateCartQuantity($cartId, $totalQuantity)
    {
        $sql = "UPDATE CARTS SET QUANTITY = ? WHERE ID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("di", $totalQuantity, $cartId);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }

    public function deleteCart($cartId)
    {
        $this->conn->begin_transaction();

        try {
            $sqlDetails = "DELETE FROM CARTDETAILS WHERE CARTID = ?";
            $stmtDetails = $this->conn->prepare($sqlDetails);
            $stmtDetails->bind_param("i", $cartId);
            $stmtDetails->execute();

            $sqlCart = "DELETE FROM CARTS WHERE ID = ?";
            $stmtCart = $this->conn->prepare($sqlCart);
            $stmtCart->bind_param("i", $cartId);
            $stmtCart->execute();

            $deleted = $stmtCart->affected_rows > 0;
            $this->conn->commit();

            return $deleted;
        } catch (Throwable $exception) {
            $this->conn->rollback();
            error_log('Delete cart failed: ' . $exception->getMessage());

            return false;
        }
    }
}
