<?php

require_once __DIR__ . '/../config/DatabaseConnection.php';
require_once __DIR__ . '/../models/CartDetail.php';

class CartDetailController
{
    private mysqli $conn;

    public function __construct()
    {
        $db = new DatabaseConnection();
        $this->conn = $db->getConnection();
    }

    public function createCartDetail($cartId, $productId, $quantity)
    {
        $sql = "INSERT INTO CARTDETAILS (CARTID, PRODUCTID, QUANTITY) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iid", $cartId, $productId, $quantity);

        if (!$stmt->execute()) {
            return null;
        }

        return new CartDetail($cartId, $productId, $quantity);
    }

    public function getCartDetail($cartId, $productId)
    {
        $sql = "SELECT * FROM CARTDETAILS WHERE CARTID = ? AND PRODUCTID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $cartId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return null;
        }

        $row = $result->fetch_assoc();

        return new CartDetail($row['CARTID'], $row['PRODUCTID'], $row['QUANTITY']);
    }

    public function getCartDetailByCartId($cartId)
    {
        $sql = "SELECT * FROM CARTDETAILS WHERE CARTID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $cartId);
        $stmt->execute();
        $result = $stmt->get_result();

        $cartDetails = [];

        while ($row = $result->fetch_assoc()) {
            $cartDetails[] = new CartDetail($row['CARTID'], $row['PRODUCTID'], $row['QUANTITY']);
        }

        return $cartDetails;
    }

    public function addItem($cartId, $productId, $quantity)
    {
        $sql = "INSERT INTO CARTDETAILS (CARTID, PRODUCTID, QUANTITY) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE QUANTITY = QUANTITY + ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iidd", $cartId, $productId, $quantity, $quantity);
        $stmt->execute();

        if ($stmt->affected_rows <= 0) {
            return false;
        }

        return $this->updateCartQuantity($cartId);
    }

    public function removeItem($cartId, $productId)
    {
        $sql = "DELETE FROM CARTDETAILS WHERE CARTID = ? AND PRODUCTID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $cartId, $productId);
        $stmt->execute();

        if ($stmt->affected_rows <= 0) {
            return false;
        }

        return $this->updateCartQuantity($cartId);
    }

    public function clearCart($cartId)
    {
        $sql = "DELETE FROM CARTDETAILS WHERE CARTID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $cartId);
        $stmt->execute();

        if ($stmt->affected_rows <= 0) {
            return false;
        }

        return $this->updateCartQuantity($cartId);
    }

    public function updateCartQuantity($cartId)
    {
        $sql = "UPDATE CARTS
                SET QUANTITY = (
                    SELECT IFNULL(SUM(QUANTITY), 0)
                    FROM CARTDETAILS
                    WHERE CARTID = ?
                )
                WHERE ID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $cartId, $cartId);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }
}
