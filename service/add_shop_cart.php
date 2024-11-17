<?php

$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root . '/AVCShop/database/info_connect_db.php';
require_once $root . '/AVCShop/local/data.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    // Kết nối đến cơ sở dữ liệu MySQL
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $date = new DateTime($data['time']);
    // Chuyển đổi sang định dạng 'Y-m-d H:i:s' mà MySQL chấp nhận
    $formattedDate = $date->format('Y-m-d H:i:s');

    // Kiểm tra xem sản phẩm đã tồn tại trong giỏ hàng của người dùng chưa
    $stmt = $conn->prepare("SELECT * FROM shop_cart WHERE username = :username AND product_id = :product_id");
    $stmt->bindParam(':username', $data['username']);
    $stmt->bindParam(':product_id', $data['product_id']);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Sản phẩm đã tồn tại trong giỏ hàng, cập nhật số lượng
        $stmt = $conn->prepare("UPDATE shop_cart SET quantity = quantity + :quantity, time = :time WHERE username = :username AND product_id = :product_id");
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':time', $data['time']);
        $stmt->bindParam(':username', $formattedDate);
        $stmt->bindParam(':product_id', $data['product_id']);
        $stmt->execute();
    } else {
        // Sản phẩm chưa tồn tại trong giỏ hàng, thêm mới vào
        $stmt = $conn->prepare("INSERT INTO shop_cart (username, product_id, quantity, time) VALUES (:username, :product_id, :quantity, :time)");
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':product_id', $data['product_id']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':time', $formattedDate);
        $stmt->execute();
    }

    echo json_encode(array('message' => 'Thêm sản phẩm vào giỏ hàng thành công'));
} catch (PDOException $e) {
    echo json_encode(array('message' => $e->getMessage()));
}
