<?php

$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    // Kết nối đến cơ sở dữ liệu MySQL
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($data['operator'] === 'del') {
        $stmt = $conn->prepare("DELETE FROM shop_cart WHERE username = :username AND product_id = :product_id");
    } else {
        $quantity = ($data['operator'] === '-') ? -1 : 1;

        $stmt = $conn->prepare("UPDATE shop_cart SET quantity = quantity + :quantity WHERE username = :username AND product_id = :product_id");
        $stmt->bindParam(':quantity', $quantity);
    }

    $stmt->bindParam(':username', $data['username']);
    $stmt->bindParam(':product_id', $data['product_id']);
    $stmt->execute();

    if ($data['operator'] === 'del') {
        $response = array("status" => 0);
    } else {
        $response = array("status" => 1);
    }
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (PDOException $e) {
    echo '<script>console.log("Lỗi: ' . $e->getMessage() . '")</script>';
}
